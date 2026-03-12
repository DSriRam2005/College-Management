<?php
session_start();
include 'db.php';

// ✅ Only allow PR role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

$view = $_GET['view'] ?? 'clean'; // clean | actual
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM STUDENTS WHERE (debarred = 0 OR debarred IS NULL)")->fetch_assoc()['cnt'] ?? 0;

$total_classes  = $conn->query("SELECT COUNT(DISTINCT classid) as cnt 
                                FROM STUDENTS 
                                WHERE (debarred = 0 OR debarred IS NULL)")
                                ->fetch_assoc()['cnt'] ?? 0;

$total_teams    = $conn->query("SELECT COUNT(DISTINCT teamid) as cnt 
                                FROM STUDENTS 
                                WHERE (debarred = 0 OR debarred IS NULL)")
                                ->fetch_assoc()['cnt'] ?? 0;

// ✅ Clean vs Actual dues & paid
if ($view === 'clean') {
    $res = $conn->query("
        SELECT 
            SUM(CASE WHEN tfdue_12_9 > 0 THEN tfdue_12_9 ELSE 0 END) +
            SUM(CASE WHEN otdues_12_9 > 0 THEN otdues_12_9 ELSE 0 END) +
            SUM(CASE WHEN busdue_12_9 > 0 THEN busdue_12_9 ELSE 0 END) +
            SUM(CASE WHEN hosdue_12_9 > 0 THEN hosdue_12_9 ELSE 0 END) +
            SUM(CASE WHEN olddue_12_9 > 0 THEN olddue_12_9 ELSE 0 END) as total_12,
            
            SUM(CASE WHEN tfdue_today > 0 THEN tfdue_today ELSE 0 END) +
            SUM(CASE WHEN otdues_today > 0 THEN otdues_today ELSE 0 END) +
            SUM(CASE WHEN busdue_today > 0 THEN busdue_today ELSE 0 END) +
            SUM(CASE WHEN hosdue_today > 0 THEN hosdue_today ELSE 0 END) +
            SUM(CASE WHEN olddue_today > 0 THEN olddue_today ELSE 0 END) as total_today
        FROM STUDENTS
        WHERE (debarred = 0 OR debarred IS NULL)
    ")->fetch_assoc();

    $total_12_9 = $res['total_12'] ?? 0;
    $today_dues = $res['total_today'] ?? 0;
    $total_paid = $total_12_9 - $today_dues; // ✅ Clean paid
} else {
    $res = $conn->query("
        SELECT 
            SUM(tfdue_12_9) + SUM(otdues_12_9) + SUM(busdue_12_9) +
            SUM(hosdue_12_9) + SUM(olddue_12_9) as total_12,
            
            SUM(tfdue_today) + SUM(otdues_today) + SUM(busdue_today) +
            SUM(hosdue_today) + SUM(olddue_today) as total_today
        FROM STUDENTS
        WHERE (debarred = 0 OR debarred IS NULL)
    ")->fetch_assoc();

    $total_12_9 = $res['total_12'] ?? 0;
    $today_dues = $res['total_today'] ?? 0;

    $total_paid = $conn->query("
        SELECT COALESCE(SUM(paid_tf+paid_ot+paid_bus+paid_hos+paid_old),0) as paid FROM PAYMENTS
    ")->fetch_assoc()['paid'] ?? 0; // ✅ Actual paid
}

// --- Categories ---
$categories = [
    "Tuition Fee" => ["tfdue_12_9", "tfdue_today", "paid_tf"],
    "Other Fee"   => ["otdues_12_9", "otdues_today", "paid_ot"],
    "Bus Fee"     => ["busdue_12_9", "busdue_today", "paid_bus"],
    "Hostel Fee"  => ["hosdue_12_9", "hosdue_today", "paid_hos"],
    "Old Dues"    => ["olddue_12_9", "olddue_today", "paid_old"],
];

$data = [];
foreach ($categories as $label => $cols) {
    [$col12, $colToday, $colPaid] = $cols;

    if ($view === 'clean') {
        $res = $conn->query("
            SELECT 
                SUM(CASE WHEN $col12 > 0 THEN $col12 ELSE 0 END) as due12,
                SUM(CASE WHEN $colToday > 0 THEN $colToday ELSE 0 END) as dueToday
            FROM STUDENTS
            WHERE (debarred = 0 OR debarred IS NULL)
        ")->fetch_assoc();

        $due12 = $res['due12'] ?? 0;
        $today = $res['dueToday'] ?? 0;
        $paid  = $due12 - $today; // ✅ Clean paid
    } else {
        $res = $conn->query("
            SELECT SUM($col12) as due12, SUM($colToday) as dueToday FROM STUDENTS
        ")->fetch_assoc();

        $paid = $conn->query("
            SELECT COALESCE(SUM($colPaid),0) as paid FROM PAYMENTS
        ")->fetch_assoc()['paid'] ?? 0;

        $due12 = $res['due12'] ?? 0;
        $today = $res['dueToday'] ?? 0;
    }

    $data[] = [
        "category" => $label,
        "due12"    => $due12,
        "today"    => $today,
        "paid"     => $paid
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PR Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">

<div class="container-fluid">

    <!-- ✅ Toggle Buttons -->
    <div class="mb-3">
        <a href="?view=clean" class="btn btn-<?php echo $view=='clean'?'primary':'secondary'; ?>">Clean View</a>
        <a href="?view=actual" class="btn btn-<?php echo $view=='actual'?'primary':'secondary'; ?>">Actual View</a>
    </div>

    <!-- Cards Row -->
    <div class="row text-center mb-4">
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Students</h6>
                    <h4><?php echo $total_students; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Classes</h6>
                    <h4><?php echo $total_classes; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Teams</h6>
                    <h4><?php echo $total_teams; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>ON 19-01-2026 Total</h6>
                    <h4>₹<?php echo number_format($total_12_9,2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Paid</h6>
                    <h4 class="text-success">
                        <a href="list.php?category=all&col=paid&view=<?php echo $view; ?>" 
                           class="text-decoration-none text-success">
                           ₹<?php echo number_format($total_paid,2); ?>
                        </a>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Today’s Dues</h6>
                    <h4>₹<?php echo number_format($today_dues,2); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Category-wise Dues & Paid (<?php echo ucfirst($view); ?> View)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Category</th>
                        <th>ON 19-01-2026 Dues</th>
                        <th>Today Dues</th>
                        <th>Paid</th>
                    </tr>
                </thead>
                <tbody>
    <?php 
    $grand12 = 0; 
    $grandToday = 0; 
    $grandPaid = 0;

    foreach($data as $row): 
        $grand12 += $row['due12'];
        $grandToday += $row['today'];
        $grandPaid += $row['paid'];
    ?>
        <tr>
            <td><?php echo $row['category']; ?></td>
            <td><?php echo number_format($row['due12'],2); ?></td>
            <td><?php echo number_format($row['today'],2); ?></td>
            <td class="fw-bold text-success">
                <a href="list.php?category=<?php echo urlencode($row['category']); ?>&col=paid&view=<?php echo $view; ?>" 
                   class="text-decoration-none text-success">
                    <?php echo number_format($row['paid'],2); ?>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr class="table-warning fw-bold">
        <td>Grand Total</td>
        <td><?php echo number_format($grand12,2); ?></td>
        <td><?php echo number_format($grandToday,2); ?></td>
        <td class="text-success">
            <a href="list.php?category=all&col=paid&view=<?php echo $view; ?>" 
               class="text-decoration-none text-success">
                <?php echo number_format($grandPaid,2); ?>
            </a>
        </td>
    </tr>
</tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
