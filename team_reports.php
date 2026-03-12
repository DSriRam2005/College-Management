<?php
session_start();
include 'db.php';

// ✅ Only allow PR role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

// --- FILTER GROUPS (Class IDs) ---
$filters = [
    "2022" => [
        "22KTAIDDA","22KTAIDDB","22KTAIDH",
        "22KTCAID","22KTCAIH",
        "22KTCSCD","22KTCSCH","22KTCSDDH",
        "22KTCSMDA","22KTCSMDB","22KTCSMH",
        "22KWAIDA","22KWAIDB",
        "22KWCAID","22KWCAIH",
        "22KWCSMD","22KWCSMH"
    ],
    "2023" => [
        "23KTAIDD","23KTAIDH",
        "23KTCAID","23KTCAIH",
        "23KTCSCD","23KTCSCH","23KTCSDD","23KTCSDH",
        "23KTCSMD","23KTCSMH",
        "23KWAIDD","23KWAIDH",
        "23KWCAID","23KWCAIH",
        "23KWCSMD","23KWCSMH"
    ],
    "2024" => [
        "24KTAIDD","24KTAIDH",
        "24KTCAID","24KTCAIH",
        "24KTCSCD","24KTCSCH","24KTCSDD","24KTCSDH",
        "24KTCSMD","24KTCSMH",
        "24KWAIDD","24KWAIDH",
        "24KWCAID","24KWCAIH",
        "24KWCSMD","24KWCSMH"
    ],
    "DIP"   => ["23KTCMEA","23KWCMEB","24KTCMEA","24KWCMEB"],
    "MBA"   => ["24MBA"],
    "MCA"   => ["24MCA"],
    "MTECH" => ["23MTECH","24MTECH"]
];

$selected_filter = $_GET['filter'] ?? '';
$view = $_GET['view'] ?? 'clean';

// --- Fetch Team-wise Dues + Payments ---
if ($view === 'clean') {
    $res = $conn->query("
        SELECT s.teamid, s.classid,
               SUM(CASE WHEN s.tfdue_12_9 > 0 THEN s.tfdue_12_9 ELSE 0 END) as tf_12_9,
               SUM(CASE WHEN s.otdues_12_9 > 0 THEN s.otdues_12_9 ELSE 0 END) as ot_12_9,
               SUM(CASE WHEN s.busdue_12_9 > 0 THEN s.busdue_12_9 ELSE 0 END) as bus_12_9,
               SUM(CASE WHEN s.hosdue_12_9 > 0 THEN s.hosdue_12_9 ELSE 0 END) as hos_12_9,
               SUM(CASE WHEN s.olddue_12_9 > 0 THEN s.olddue_12_9 ELSE 0 END) as old_12_9,

               SUM(CASE WHEN s.tfdue_today > 0 THEN s.tfdue_today ELSE 0 END) as tf_today,
               SUM(CASE WHEN s.otdues_today > 0 THEN s.otdues_today ELSE 0 END) as ot_today,
               SUM(CASE WHEN s.busdue_today > 0 THEN s.busdue_today ELSE 0 END) as bus_today,
               SUM(CASE WHEN s.hosdue_today > 0 THEN s.hosdue_today ELSE 0 END) as hos_today,
               SUM(CASE WHEN s.olddue_today > 0 THEN s.olddue_today ELSE 0 END) as old_today
        FROM STUDENTS s
        WHERE s.teamid IS NOT NULL AND s.teamid <> ''
        GROUP BY s.teamid
    ");
} else {
    $res = $conn->query("
        SELECT s.teamid, s.classid,
               SUM(s.tfdue_12_9) AS tf_12_9,
               SUM(s.otdues_12_9) AS ot_12_9,
               SUM(s.busdue_12_9) AS bus_12_9,
               SUM(s.hosdue_12_9) AS hos_12_9,
               SUM(s.olddue_12_9) AS old_12_9,
               SUM(s.tfdue_today) AS tf_today,
               SUM(s.otdues_today) AS ot_today,
               SUM(s.busdue_today) AS bus_today,
               SUM(s.hosdue_today) AS hos_today,
               SUM(s.olddue_today) AS old_today,
               COALESCE(SUM(p.total_paid),0) AS total_paid
        FROM STUDENTS s
        LEFT JOIN (
            SELECT htno,
                   SUM(paid_tf + paid_ot + paid_bus + paid_hos + paid_old) AS total_paid
            FROM PAYMENTS
            GROUP BY htno
        ) p ON p.htno = s.htno
        WHERE s.teamid IS NOT NULL AND s.teamid <> ''
        GROUP BY s.teamid
    ");
}

$data = [];
$grand = ['old_total'=>0,'paid'=>0,'today'=>0];

while($row = $res->fetch_assoc()){
    if (empty($row['teamid'])) continue;

    // ✅ Apply filter based on CLASSID before including teamid
    if ($selected_filter && isset($filters[$selected_filter]) && !in_array($row['classid'], $filters[$selected_filter])) {
        continue;
    }

    $total_12_9 = $row['tf_12_9'] + $row['ot_12_9'] + $row['bus_12_9'] + $row['hos_12_9'] + $row['old_12_9'];
    $total_today = $row['tf_today'] + $row['ot_today'] + $row['bus_today'] + $row['hos_today'] + $row['old_today'];

    if ($view === 'clean') {
        $total_paid = $total_12_9 - $total_today;
    } else {
        $total_paid = $row['total_paid'];
    }

    $paid_percent = ($total_12_9 > 0) ? ($total_paid / $total_12_9) * 100 : 0;

    $data[] = [
        'teamid' => $row['teamid'],
        'total_12_9' => $total_12_9,
        'total_paid' => $total_paid,
        'total_today' => $total_today,
        'paid_percent' => $paid_percent
    ];

    $grand['old_total'] += $total_12_9;
    $grand['paid'] += $total_paid;
    $grand['today'] += $total_today;
}

$grand['paid_percent'] = ($grand['old_total'] > 0) ? ($grand['paid'] / $grand['old_total']) * 100 : 0;

usort($data, function($a, $b) {
    return $b['paid_percent'] <=> $a['paid_percent'];
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Team-wise Dues Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="p-3">

<div class="container-fluid">
    <h2 class="mb-4">Team-wise Dues Report (<?php echo ucfirst($view); ?> View)</h2>

    <!-- ✅ Toggle Buttons -->
    <div class="mb-3">
        <a href="?view=clean&filter=<?php echo $selected_filter; ?>" class="btn btn-<?php echo $view=='clean'?'primary':'secondary'; ?>">Clean View</a>
        <a href="?view=actual&filter=<?php echo $selected_filter; ?>" class="btn btn-<?php echo $view=='actual'?'primary':'secondary'; ?>">Actual View</a>
    </div>

    <!-- ✅ Filter Dropdown -->
    <form method="get" class="mb-3">
        <input type="hidden" name="view" value="<?php echo $view; ?>">
        <label for="filter" class="fw-bold">Filter by Class Group:</label>
        <select name="filter" id="filter" class="form-select w-auto d-inline">
            <option value="">-- All --</option>
            <?php foreach($filters as $fname => $classids): ?>
                <option value="<?php echo $fname; ?>" <?php if($fname == $selected_filter) echo "selected"; ?>>
                    <?php echo $fname; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

    <table id="reportTable" class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>Rank</th>
                <th>Team ID</th>
                <th>ON 19-01-2026 Total</th>
                <th>Paid Amount</th>
                <th>Today Total</th>
                <th>Paid %</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            foreach($data as $row): 
            ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td>
                        <a href="<?php echo ($view=='clean'?'view_sem_dues.php':'view_due_reports.php'); ?>?teamid=<?php echo $row['teamid']; ?>">
                            <?php echo $row['teamid']; ?>
                        </a>
                    </td>
                    <td><?php echo number_format($row['total_12_9'],2); ?></td>
                    <td class="text-primary fw-bold"><?php echo number_format($row['total_paid'],2); ?></td>
                    <td><?php echo number_format($row['total_today'],2); ?></td>
                    <td class="fw-bold text-success"><?php echo number_format($row['paid_percent'],2); ?>%</td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-warning fw-bold not-searchable">
                <td>-</td>
                <td>Grand Total</td>
                <td><?php echo number_format($grand['old_total'],2); ?></td>
                <td class="text-primary"><?php echo number_format($grand['paid'],2); ?></td>
                <td><?php echo number_format($grand['today'],2); ?></td>
                <td class="text-success"><?php echo number_format($grand['paid_percent'],2); ?>%</td>
            </tr>
        </tbody>
    </table>
    <a href="pr_home.php" class="btn btn-secondary mt-3">Back to Home</a>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    let table = $('#reportTable').DataTable({
        "paging": false,
        "info": false,
        "searching": true,
        "ordering": false,
        "drawCallback": function(settings) {
            let lastRow = $('#reportTable tbody tr.table-warning');
            if (lastRow.length) {
                $('#reportTable tbody').append(lastRow);
            }
        },
        "rowCallback": function(row, data, index) {
            if ($(row).hasClass('not-searchable')) {
                $(row).show();
            }
        }
    });
});
</script>

</body>
</html>
