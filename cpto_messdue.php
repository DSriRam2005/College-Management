<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

// ✅ Allow large joins (InfinityFree limitation fix)
$conn->query("SET SQL_BIG_SELECTS=1");

date_default_timezone_set('Asia/Kolkata');

// ✅ Allow PR, ADMIN, CPTO only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','ADMIN','CPTO'])) {
    header("Location: index.php");
    exit();
}

// ✅ Get classid (PR via GET, CPTO via session)
$classid = $_GET['classid'] ?? ($_SESSION['classid'] ?? null);
if (!$classid) {
    die("❌ No class selected.");
}

// ✅ Get selected month (default = current month)
$selectedMonth = $_GET['month'] ?? date('Y-m');
$monthName = date("F Y", strtotime($selectedMonth . "-01"));

// ✅ Helper for coloring dues
function dueClass($amount) {
    return ($amount > 0) ? 'positive' : 'zero';
}

// ✅ Fetch available months from messfee table
$monthsResult = $conn->query("SELECT DISTINCT DATE_FORMAT(month_year, '%Y-%m') AS ym FROM messfee ORDER BY ym DESC");
$availableMonths = [];
while ($m = $monthsResult->fetch_assoc()) {
    $availableMonths[] = $m['ym'];
}

// ✅ Fetch overall total for class for selected month
$stmtTotal = $conn->prepare("
    SELECT SUM(IFNULL(m.due,0)) AS overall_total
    FROM STUDENTS s
    LEFT JOIN messfee m ON s.htno = m.htno
    WHERE s.classid = ? 
      AND (s.debarred = 0 OR s.debarred IS NULL)
      AND DATE_FORMAT(m.month_year, '%Y-%m') = ?
");
$stmtTotal->bind_param("ss", $classid, $selectedMonth);
$stmtTotal->execute();
$overallTotal = $stmtTotal->get_result()->fetch_assoc()['overall_total'] ?? 0;

// ✅ Fetch all students and calculate old dues
$stmt = $conn->prepare("
    SELECT 
        s.htno, 
        s.name, 
        s.teamid,
        IFNULL(curr.due,0) AS current_due,
        IFNULL(old.old_total,0) AS old_due
    FROM STUDENTS s
    LEFT JOIN (
        SELECT htno, due, DATE_FORMAT(month_year, '%Y-%m') AS month
        FROM messfee
        WHERE DATE_FORMAT(month_year, '%Y-%m') = ?
    ) AS curr ON s.htno = curr.htno
    LEFT JOIN (
        SELECT htno, SUM(due) AS old_total
        FROM messfee
        WHERE DATE_FORMAT(month_year, '%Y-%m') < ?
        GROUP BY htno
    ) AS old ON s.htno = old.htno
    WHERE s.classid = ?
      AND (s.debarred = 0 OR s.debarred IS NULL)
    ORDER BY 
        SUBSTRING_INDEX(s.teamid, '_', 1) ASC,
        CAST(SUBSTRING_INDEX(s.teamid, '_', -1) AS UNSIGNED) ASC,
        s.htno ASC
");
$stmt->bind_param("sss", $selectedMonth, $selectedMonth, $classid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mess Fee Dues - <?= htmlspecialchars($classid) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .positive { color: red !important; font-weight: bold; }
        .zero { color: green !important; font-weight: bold; }
        .table-info td { background-color: #d1ecf1 !important; }
        .form-select { min-width: 180px; }
    </style>
</head>
<body>

<h3>🍽 Mess Fee Dues for Class: <?= htmlspecialchars($classid) ?></h3>

<!-- ✅ Month Filter -->
<form method="GET" class="mb-3">
    <input type="hidden" name="classid" value="<?= htmlspecialchars($classid) ?>">
    <label for="month" class="form-label"><strong>Select Month:</strong></label>
    <select name="month" id="month" class="form-select d-inline-block" onchange="this.form.submit()">
        <?php foreach ($availableMonths as $m): ?>
            <option value="<?= $m ?>" <?= ($m == $selectedMonth ? 'selected' : '') ?>>
                <?= date("F Y", strtotime($m . "-01")) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<!-- ✅ Overall Class Total -->
<div class="mb-3">
    <strong>Overall Mess Fee Due for Class (<?= htmlspecialchars($monthName) ?>): </strong>
    <span class="<?= dueClass($overallTotal) ?>"><?= number_format($overallTotal, 2) ?></span>
</div>

<table class="table table-bordered table-sm table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>HT No</th>
            <th>Name</th>
            <th>Team</th>
            <th>Old Total Due (Before <?= htmlspecialchars($monthName) ?>)</th>
            <th>Due (<?= htmlspecialchars($monthName) ?>)</th>
            <th>Total Due (Old + Current)</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $sno = 1;
    $currentTeam = null;

    while ($row = $result->fetch_assoc()):
        $totalDue = $row['old_due'] + $row['current_due'];

        // ✅ Team header
        if (!empty($row['teamid']) && $currentTeam !== $row['teamid']) {
            $teamStmt = $conn->prepare("
                SELECT 
                    SUM(IFNULL(m.due,0)) AS team_total,
                    (SELECT SUM(IFNULL(mm.due,0)) FROM messfee mm 
                     JOIN STUDENTS ss ON ss.htno = mm.htno
                     WHERE ss.teamid = ? AND ss.classid = ? 
                     AND DATE_FORMAT(mm.month_year, '%Y-%m') < ?) AS old_total
                FROM STUDENTS s
                LEFT JOIN messfee m 
                    ON s.htno = m.htno 
                   AND DATE_FORMAT(m.month_year, '%Y-%m') = ?
                WHERE s.teamid = ? 
                  AND s.classid = ? 
                  AND (s.debarred=0 OR s.debarred IS NULL)
            ");
            $teamStmt->bind_param("ssssss", $row['teamid'], $classid, $selectedMonth, $selectedMonth, $row['teamid'], $classid);
            $teamStmt->execute();
            $teamTotals = $teamStmt->get_result()->fetch_assoc();
            $teamCurrent = $teamTotals['team_total'] ?? 0;
            $teamOld = $teamTotals['old_total'] ?? 0;
            $teamTotal = $teamOld + $teamCurrent;

            $currentTeam = $row['teamid'];
            echo "<tr class='table-info'>
                    <td colspan='7'>
                        <strong>Team: " . htmlspecialchars($currentTeam) . "</strong> | 
                        Old Total: <span class='" . dueClass($teamOld) . "'>" . number_format($teamOld, 2) . "</span> |
                        Current Month: <span class='" . dueClass($teamCurrent) . "'>" . number_format($teamCurrent, 2) . "</span> |
                        Overall: <span class='" . dueClass($teamTotal) . "'>" . number_format($teamTotal, 2) . "</span>
                    </td>
                  </tr>";
        }
    ?>
        <tr>
            <td><?= $sno++ ?></td>
            <td><?= htmlspecialchars($row['htno']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['teamid'] ?? '—') ?></td>

            <td class="<?= dueClass($row['old_due']) ?>"><?= number_format($row['old_due'], 2) ?></td>
            <td class="<?= dueClass($row['current_due']) ?>"><?= number_format($row['current_due'], 2) ?></td>
            <td class="<?= dueClass($totalDue) ?>"><?= number_format($totalDue, 2) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
