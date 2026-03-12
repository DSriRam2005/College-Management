<?php
session_start();
include 'db.php';

// ✅ Only allow PR role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

// --- Filters ---
$selectedProg = $_GET['prog'] ?? 'B.TECH';
$selectedYear = $_GET['year'] ?? '';
$view = $_GET['view'] ?? 'clean'; // clean | actual

// --- WHERE conditions ---
$where = "WHERE s.ZONE IS NOT NULL AND s.ZONE<>'' AND (s.debarred=0 OR s.debarred IS NULL)";
$where .= " AND s.prog = '" . $conn->real_escape_string($selectedProg) . "'";
if (!empty($selectedYear)) {
    $where .= " AND s.year = '" . $conn->real_escape_string($selectedYear) . "'";
}

// --- Query based on view ---
if ($view === 'clean') {
    $res = $conn->query("
        SELECT s.ZONE,
               SUM(CASE WHEN s.tfdue_12_9>0 THEN s.tfdue_12_9 ELSE 0 END) AS tf_12_9,
               SUM(CASE WHEN s.otdues_12_9>0 THEN s.otdues_12_9 ELSE 0 END) AS ot_12_9,
               SUM(CASE WHEN s.busdue_12_9>0 THEN s.busdue_12_9 ELSE 0 END) AS bus_12_9,
               SUM(CASE WHEN s.hosdue_12_9>0 THEN s.hosdue_12_9 ELSE 0 END) AS hos_12_9,
               SUM(CASE WHEN s.olddue_12_9>0 THEN s.olddue_12_9 ELSE 0 END) AS old_12_9,
               SUM(CASE WHEN s.tfdue_today>0 THEN s.tfdue_today ELSE 0 END) AS tf_today,
               SUM(CASE WHEN s.otdues_today>0 THEN s.otdues_today ELSE 0 END) AS ot_today,
               SUM(CASE WHEN s.busdue_today>0 THEN s.busdue_today ELSE 0 END) AS bus_today,
               SUM(CASE WHEN s.hosdue_today>0 THEN s.hosdue_today ELSE 0 END) AS hos_today,
               SUM(CASE WHEN s.olddue_today>0 THEN s.olddue_today ELSE 0 END) AS old_today
        FROM STUDENTS s
        $where
        GROUP BY s.ZONE
    ");
} else {
    $res = $conn->query("
        SELECT s.ZONE,
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
                   SUM(paid_tf+paid_ot+paid_bus+paid_hos+paid_old) AS total_paid
            FROM PAYMENTS
            GROUP BY htno
        ) p ON p.htno = s.htno
        $where
        GROUP BY s.ZONE
    ");
}

// --- Process data ---
$data = [];
$grand = ['old_total'=>0,'paid'=>0,'today'=>0];

while($row = $res->fetch_assoc()) {
    if (empty($row['ZONE'])) continue;

    $total_12_9 = $row['tf_12_9'] + $row['ot_12_9'] + $row['bus_12_9'] + $row['hos_12_9'] + $row['old_12_9'];
    $total_today = $row['tf_today'] + $row['ot_today'] + $row['bus_today'] + $row['hos_today'] + $row['old_today'];
    $total_paid = ($view === 'clean') ? ($total_12_9 - $total_today) : $row['total_paid'];
    $paid_percent = ($total_12_9>0) ? ($total_paid/$total_12_9)*100 : 0;

    $data[] = [
        'ZONE'=>$row['ZONE'],
        'total_12_9'=>$total_12_9,
        'total_paid'=>$total_paid,
        'total_today'=>$total_today,
        'paid_percent'=>$paid_percent
    ];

    $grand['old_total'] += $total_12_9;
    $grand['paid'] += $total_paid;
    $grand['today'] += $total_today;
}

$grand['paid_percent'] = ($grand['old_total']>0) ? ($grand['paid']/$grand['old_total'])*100 : 0;

// ✅ Sort zones by Paid %
usort($data, fn($a,$b)=> $b['paid_percent'] <=> $a['paid_percent']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Zone-wise Dues Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="p-3">

<div class="container-fluid">
    <h2 class="mb-4">Zone-wise Dues Report (<?php echo ucfirst($view); ?> View)</h2>

    <!-- Filter Form -->
    <form method="get" class="row g-3 mb-3">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
        <div class="col-md-3">
            <label class="form-label fw-bold">Program</label>
            <select name="prog" class="form-select" onchange="this.form.submit()">
                <option value="B.TECH" <?php if($selectedProg=='B.TECH') echo 'selected'; ?>>B.TECH</option>
                <option value="DIP" <?php if($selectedProg=='DIP') echo 'selected'; ?>>DIP</option>
                <option value="M.TECH" <?php if($selectedProg=='M.TECH') echo 'selected'; ?>>M.TECH</option>
                <option value="MBA" <?php if($selectedProg=='MBA') echo 'selected'; ?>>MBA</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Year</label>
            <select name="year" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="25" <?php if($selectedYear=='25') echo 'selected'; ?>>1</option>
                <option value="24" <?php if($selectedYear=='24') echo 'selected'; ?>>2</option>
                <option value="23" <?php if($selectedYear=='23') echo 'selected'; ?>>3</option>
                <option value="22" <?php if($selectedYear=='22') echo 'selected'; ?>>4</option>
            </select>
        </div>
    </form>

    <!-- Toggle Buttons -->
    <div class="mb-3">
        <a href="?view=clean&prog=<?php echo urlencode($selectedProg); ?>&year=<?php echo urlencode($selectedYear); ?>" class="btn btn-<?php echo $view=='clean'?'primary':'secondary'; ?>">Clean View</a>
        <a href="?view=actual&prog=<?php echo urlencode($selectedProg); ?>&year=<?php echo urlencode($selectedYear); ?>" class="btn btn-<?php echo $view=='actual'?'primary':'secondary'; ?>">Actual View</a>
    </div>

    <!-- Table -->
    <table id="reportTable" class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>Rank</th>
                <th>Zone</th>
                <th>19-01-2026 Total</th>
                <th>Paid Amount</th>
                <th>Today Total</th>
                <th>Paid %</th>
            </tr>
        </thead>
        <tbody>
        <?php $rank=1; foreach($data as $row): ?>
            <tr>
                <td><?php echo $rank++; ?></td>
                <td><?php echo htmlspecialchars($row['ZONE']); ?></td>
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
    $('#reportTable').DataTable({
        "paging": false,
        "info": false,
        "searching": true,
        "ordering": false,
        "rowCallback": function(row, data) {
            if ($(row).hasClass('not-searchable')) $(row).show();
        },
        "drawCallback": function() {
            let lastRow = $('#reportTable tbody tr.table-warning');
            if (lastRow.length) $('#reportTable tbody').append(lastRow);
        }
    });
});
</script>
</body>
</html>
