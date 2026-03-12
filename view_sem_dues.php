<?php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Kolkata');

// ✅ Allow PR, CPTO, HTPO only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','CPTO','HTPO'])) {
    header("Location: index.php");
    exit();
}

$classid = null;

// ✅ Determine classid
if ($_SESSION['role'] === 'CPTO') {
    $classid = $_SESSION['classid'] ?? null;
} elseif (in_array($_SESSION['role'], ['PR','HTPO'])) {
    $classid = $_GET['classid'] ?? null;
}

// ✅ Helper function for coloring dues
function dueClass($amount) {
    return ($amount > 0) ? 'positive' : 'zero';
}

// ✅ Mark whole team application issued
if (isset($_POST['issue_team']) && !empty($_POST['teamid'])) {
    $teamid = $_POST['teamid'];
    if ($classid) {
        $stmt = $conn->prepare("UPDATE STUDENTS SET issued_application=1, issued_at=NOW() WHERE teamid=? AND classid=? AND (debarred=0 OR debarred IS NULL)");
        $stmt->bind_param("ss", $teamid, $classid);
    } else {
        $stmt = $conn->prepare("UPDATE STUDENTS SET issued_application=1, issued_at=NOW() WHERE teamid=? AND (debarred=0 OR debarred IS NULL)");
        $stmt->bind_param("s", $teamid);
    }
    $stmt->execute();
}

// ✅ Mark individual application issued
if (isset($_POST['issue_individual']) && !empty($_POST['htno'])) {
    $htno = $_POST['htno'];
    $stmt = $conn->prepare("UPDATE STUDENTS SET issued_application=1, issued_at=NOW() WHERE htno=? AND (debarred=0 OR debarred IS NULL)");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
}

// ✅ Fetch totals (ignoring negative dues, skip debarred students)
$total_where = $classid ? "WHERE classid=? AND (debarred=0 OR debarred IS NULL)" : "WHERE (debarred=0 OR debarred IS NULL)";
$stmt = $conn->prepare("
    SELECT 
        IFNULL(SUM(GREATEST(tfdue_12_9,0)),0) +
        IFNULL(SUM(GREATEST(otdues_12_9,0)),0) +
        IFNULL(SUM(GREATEST(busdue_12_9,0)),0) +
        IFNULL(SUM(GREATEST(hosdue_12_9,0)),0) +
        IFNULL(SUM(GREATEST(olddue_12_9,0)),0) AS total_12_9,

        IFNULL(SUM(GREATEST(tfdue_today,0)),0) +
        IFNULL(SUM(GREATEST(otdues_today,0)),0) +
        IFNULL(SUM(GREATEST(busdue_today,0)),0) +
        IFNULL(SUM(GREATEST(hosdue_today,0)),0) +
        IFNULL(SUM(GREATEST(olddue_today,0)),0) AS total_today
    FROM STUDENTS
    $total_where
");
if ($classid) $stmt->bind_param("s", $classid);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// ✅ Fetch students (skip debarred)
$students_where = $classid ? "WHERE classid=? AND (debarred=0 OR debarred IS NULL)" : "WHERE (debarred=0 OR debarred IS NULL)";
$order_by = "ORDER BY SUBSTRING_INDEX(teamid, '_', 1) ASC, CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) ASC, htno ASC";
$stmt = $conn->prepare("
    SELECT htno, name, prog, classid, teamid,
           GREATEST(tfdue_today,0) AS tfdue_today,
           GREATEST(otdues_today,0) AS otdues_today,
           GREATEST(busdue_today,0) AS busdue_today,
           GREATEST(hosdue_today,0) AS hosdue_today,
           GREATEST(olddue_today,0) AS olddue_today,
           issued_application
    FROM STUDENTS
    $students_where
    $order_by
");
if ($classid) $stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Due Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .positive { color: red !important; font-weight: bold; }
        .zero { color: black !important; font-weight: bold; }
    </style>
</head>
<body class="p-4">

<h3>📑 Due Report <?= $classid ? "for Class: " . htmlspecialchars($classid) : "(All Classes)" ?></h3>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Due on ON 19-01-2026</h5>
                <p class="card-text fs-4">
                    <strong class="<?= dueClass($totals['total_12_9']) ?>">
                        <?= number_format($totals['total_12_9'], 2) ?>
                    </strong>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Due Today</h5>
                <p class="card-text fs-4">
                    <strong class="<?= dueClass($totals['total_today']) ?>">
                        <?= number_format($totals['total_today'], 2) ?>
                    </strong>
                </p>
            </div>
        </div>
    </div>
</div>

<table class="table table-bordered table-sm table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>HT No</th>
            <th>Name</th>
            <?php if (!$classid): ?><th>Class</th><?php endif; ?>
            <th>Team</th>
            <th>TF Today</th>
            <th>Other Today</th>
            <th>Bus Today</th>
            <th>Hostel Today</th>
            <th>Old Today</th>
            <th>Total Today</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $sno = 1;
    $currentTeam = null;

    while ($row = $result->fetch_assoc()):
        $row['tot_due_today'] = $row['tfdue_today'] + $row['otdues_today'] + $row['busdue_today'] + $row['hosdue_today'] + $row['olddue_today'];

        if (!empty($row['teamid']) && $currentTeam !== $row['teamid']) {
            $currentTeam = $row['teamid'];
            echo "<tr class='table-info'><td colspan='" . ($classid ? "11" : "12") . "'>
                    <strong>Team: " . htmlspecialchars($currentTeam) . "</strong>
                  </td></tr>";
        }
    ?>
        <tr>
            <td><?= $sno++ ?></td>
            <td><?= htmlspecialchars($row['htno']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <?php if (!$classid): ?><td><?= htmlspecialchars($row['classid']) ?></td><?php endif; ?>
            <td><?= htmlspecialchars($row['teamid']) ?></td>
            <td class="<?= dueClass($row['tfdue_today']) ?>"><?= $row['tfdue_today'] ?></td>
            <td class="<?= dueClass($row['otdues_today']) ?>"><?= $row['otdues_today'] ?></td>
            <td class="<?= dueClass($row['busdue_today']) ?>"><?= $row['busdue_today'] ?></td>
            <td class="<?= dueClass($row['hosdue_today']) ?>"><?= $row['hosdue_today'] ?></td>
            <td class="<?= dueClass($row['olddue_today']) ?>"><?= $row['olddue_today'] ?></td>
            <td class="<?= dueClass($row['tot_due_today']) ?>"><strong><?= $row['tot_due_today'] ?></strong></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
