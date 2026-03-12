<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';
date_default_timezone_set('Asia/Kolkata');

/* ========= ACCESS ========= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['STPO','PR'])) {
    header("Location: stpo_login.php");
    exit();
}

if ($_SESSION['role'] === 'STPO') {
    if (empty($_SESSION['empid'])) die("STPO ID MISSING");
    $stpo = $_SESSION['empid'];
} else {
    $stpo = $_GET['stpo'] ?? null;
    if (!$stpo) die("STPO NOT SELECTED");
}

/* ========= HELPERS ========= */
function dueClass($amt) {
    return ($amt > 0) ? 'positive' : 'zero';
}

/* ========= STPO NAME ========= */
$q = $conn->prepare("SELECT NAME FROM kiet_staff WHERE EMPID=?");
$q->bind_param("s", $stpo);
$q->execute();
$stpoName = $q->get_result()->fetch_assoc()['NAME'] ?? '—';

/* ========= TOTALS ========= */
$stmt = $conn->prepare("
SELECT
    IFNULL(SUM(
        GREATEST(tfdue_12_9,0) +
        GREATEST(otdues_12_9,0) +
        GREATEST(busdue_12_9,0) +
        GREATEST(hosdue_12_9,0) +
        GREATEST(olddue_12_9,0)
    ),0) AS total_12_9,

    IFNULL(SUM(
        GREATEST(tfdue_today,0) +
        GREATEST(otdues_today,0) +
        GREATEST(busdue_today,0) +
        GREATEST(hosdue_today,0) +
        GREATEST(olddue_today,0)
    ),0) AS total_today
FROM STUDENTS
WHERE stpo = ?
AND IFNULL(debarred,0) = 0
");
$stmt->bind_param("s", $stpo);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

/* ========= TEAM TOTALS ========= */
$teamTotals = [];
$tt = $conn->prepare("
SELECT teamid,
SUM(
    GREATEST(tfdue_today,0) +
    GREATEST(otdues_today,0) +
    GREATEST(busdue_today,0) +
    GREATEST(hosdue_today,0) +
    GREATEST(olddue_today,0)
) AS team_total
FROM STUDENTS
WHERE stpo = ?
AND IFNULL(debarred,0) = 0
GROUP BY teamid
");
$tt->bind_param("s", $stpo);
$tt->execute();
$rtt = $tt->get_result();
while ($t = $rtt->fetch_assoc()) {
    $teamTotals[$t['teamid']] = $t['team_total'];
}

/* ========= STUDENTS ========= */
$stmt = $conn->prepare("
SELECT
    htno,
    name,
    teamid,
    GREATEST(tfdue_today,0)  AS tf,
    GREATEST(otdues_today,0) AS ot,
    GREATEST(busdue_today,0) AS bus,
    GREATEST(hosdue_today,0) AS hos,
    GREATEST(olddue_today,0) AS oldd
FROM STUDENTS
WHERE stpo = ?
AND IFNULL(debarred,0) = 0
ORDER BY
    SUBSTRING_INDEX(teamid,'_',1),
    CAST(SUBSTRING_INDEX(teamid,'_',-1) AS UNSIGNED),
    htno
");
$stmt->bind_param("s", $stpo);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>STPO Fee Report</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
    .positive { color:red !important; font-weight:bold; }
    .zero { color:black !important; font-weight:bold; }
</style>
</head>
<body class="p-4">

<h3>📑 STPO Fee Report — <?= htmlspecialchars($stpoName) ?></h3>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Due on 12-09</h5>
                <p class="card-text fs-4">
                    <strong class="<?= dueClass($totals['total_12_9']) ?>">
                        <?= number_format($totals['total_12_9'],2) ?>
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
                        <?= number_format($totals['total_today'],2) ?>
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
    <th>Team</th>
    <th>TF</th>
    <th>OT</th>
    <th>Bus</th>
    <th>Hostel</th>
    <th>Old</th>
    <th>Total</th>
</tr>
</thead>
<tbody>

<?php
$sno = 1;
$currentTeam = null;

while ($r = $result->fetch_assoc()):
    $total = $r['tf'] + $r['ot'] + $r['bus'] + $r['hos'] + $r['oldd'];

    if (!empty($r['teamid']) && $currentTeam !== $r['teamid']) {

        if ($currentTeam !== null) {
            $teamSum = $teamTotals[$currentTeam] ?? 0;
            echo "<tr class='table-warning'>
                    <td colspan='9' class='text-end'><strong>Team Total</strong></td>
                    <td class='".dueClass($teamSum)."'><strong>".number_format($teamSum,2)."</strong></td>
                  </tr>";
        }

        $currentTeam = $r['teamid'];
        echo "<tr class='table-info'>
                <td colspan='10'><strong>Team : ".htmlspecialchars($currentTeam)."</strong></td>
              </tr>";
    }
?>
<tr>
    <td><?= $sno++ ?></td>
    <td><?= htmlspecialchars($r['htno']) ?></td>
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td><?= htmlspecialchars($r['teamid']) ?></td>
    <td class="<?= dueClass($r['tf']) ?>"><?= $r['tf'] ?></td>
    <td class="<?= dueClass($r['ot']) ?>"><?= $r['ot'] ?></td>
    <td class="<?= dueClass($r['bus']) ?>"><?= $r['bus'] ?></td>
    <td class="<?= dueClass($r['hos']) ?>"><?= $r['hos'] ?></td>
    <td class="<?= dueClass($r['oldd']) ?>"><?= $r['oldd'] ?></td>
    <td class="<?= dueClass($total) ?>"><strong><?= $total ?></strong></td>
</tr>
<?php endwhile; ?>

<?php
if ($currentTeam !== null) {
    $teamSum = $teamTotals[$currentTeam] ?? 0;
    echo "<tr class='table-warning'>
            <td colspan='9' class='text-end'><strong>Team Total</strong></td>
            <td class='".dueClass($teamSum)."'><strong>".number_format($teamSum,2)."</strong></td>
          </tr>";
}
?>

</tbody>
</table>
</body>
</html>
