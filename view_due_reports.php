<?php
session_start();
include 'db.php';

// ✅ Allow PR, CPTO, and HTPO only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','CPTO','HTPO'])) {
    header("Location: index.php");
    exit();
}

$classid = null;

// ✅ Determine classid based on role
if ($_SESSION['role'] === 'CPTO') {
    $classid = $_SESSION['classid'] ?? null;
} elseif (in_array($_SESSION['role'], ['PR','HTPO'])) {
    $classid = $_GET['classid'] ?? null;
}

// ✅ Helper function to assign color class
function dueClass($amount) {
    if ($amount < 0) return 'negative';
    elseif ($amount > 0) return 'positive';
    else return 'zero';
}

// ✅ Fetch total dues excluding debarred students
if ($classid) {
    $stmt = $conn->prepare("
        SELECT 
            IFNULL(SUM(tfdue_12_9),0) +
            IFNULL(SUM(otdues_12_9),0) +
            IFNULL(SUM(busdue_12_9),0) +
            IFNULL(SUM(hosdue_12_9),0) +
            IFNULL(SUM(olddue_12_9),0) AS total_12_9,

            IFNULL(SUM(tfdue_today),0) +
            IFNULL(SUM(otdues_today),0) +
            IFNULL(SUM(busdue_today),0) +
            IFNULL(SUM(hosdue_today),0) +
            IFNULL(SUM(olddue_today),0) AS total_today
        FROM STUDENTS
        WHERE classid=?
          AND (debarred = 0 OR debarred IS NULL)
    ");
    $stmt->bind_param("s", $classid);
} else {
    $stmt = $conn->prepare("
        SELECT 
            IFNULL(SUM(tfdue_12_9),0) +
            IFNULL(SUM(otdues_12_9),0) +
            IFNULL(SUM(busdue_12_9),0) +
            IFNULL(SUM(hosdue_12_9),0) +
            IFNULL(SUM(olddue_12_9),0) AS total_12_9,

            IFNULL(SUM(tfdue_today),0) +
            IFNULL(SUM(otdues_today),0) +
            IFNULL(SUM(busdue_today),0) +
            IFNULL(SUM(hosdue_today),0) +
            IFNULL(SUM(olddue_today),0) AS total_today
        FROM STUDENTS
        WHERE (debarred = 0 OR debarred IS NULL)
    ");
}
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// ✅ Fetch student dues excluding debarred students
if ($classid) {
    $stmt = $conn->prepare("
        SELECT htno, name, prog, classid, teamid,
               tfdue_12_9, otdues_12_9, busdue_12_9, hosdue_12_9, olddue_12_9,
               tfdue_today, otdues_today, busdue_today, hosdue_today, olddue_today
        FROM STUDENTS
        WHERE classid=?
          AND (debarred = 0 OR debarred IS NULL)
        ORDER BY 
            SUBSTRING_INDEX(teamid, '_', 1) ASC,
            CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) ASC,
            htno ASC
    ");
    $stmt->bind_param("s", $classid);
} else {
    $stmt = $conn->prepare("
        SELECT htno, name, prog, classid, teamid,
               tfdue_12_9, otdues_12_9, busdue_12_9, hosdue_12_9, olddue_12_9,
               tfdue_today, otdues_today, busdue_today, hosdue_today, olddue_today
        FROM STUDENTS
        WHERE (debarred = 0 OR debarred IS NULL)
        ORDER BY 
            classid ASC,
            SUBSTRING_INDEX(teamid, '_', 1) ASC,
            CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) ASC,
            htno ASC
    ");
}
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
        .negative { color: green !important; font-weight: bold; }
        .zero { color: black !important; font-weight: bold; }
    </style>
</head>
<body class="p-4">
    <h3>📑 Due Report <?= $classid ? "for Class: " . htmlspecialchars($classid) : "(All Classes)" ?></h3>

    <!-- ✅ Top summary cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Due on 06.10</h5>
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
                <th>TF Due Today</th>
                <th>Other Due Today</th>
                <th>Bus Due Today</th>
                <th>Hostel Due Today</th>
                <th>Old Due Today</th>
                <th>Total Today</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $sno = 1;
        $currentTeam = null;

        while ($row = $result->fetch_assoc()):
            $row['tot_due_today'] = 
                $row['tfdue_today'] +
                $row['otdues_today'] +
                $row['busdue_today'] +
                $row['hosdue_today'] +
                $row['olddue_today'];

            // ✅ Insert separator row when team changes
            if ($currentTeam !== $row['teamid']) {
                $currentTeam = $row['teamid'];
                echo "<tr class='table-info'>
                        <td colspan='" . ($classid ? "11" : "12") . "'><strong>Team: " . htmlspecialchars($currentTeam) . "</strong></td>
                      </tr>";
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
