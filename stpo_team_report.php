<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

$conn->query("SET SQL_BIG_SELECTS=1");
date_default_timezone_set('Asia/Kolkata');

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','ADMIN','CTPO','STPO'])) {
    header("Location: stpo_login.php");
    exit();
}

/* ================= STPO RESOLUTION ================= */
if (isset($_GET['stpo']) && $_GET['stpo'] !== '') {
    $stpo = $_GET['stpo'];
} elseif ($_SESSION['role'] === 'STPO') {
    $stpo = $_SESSION['empid'];
} else {
    die("❌ NO STPO SELECTED");
}

/* ================= MONTH ================= */
$selectedMonth = $_GET['month'] ?? date('Y-m');
$monthName = date("F Y", strtotime($selectedMonth . "-01"));

/* ================= COLOR LOGIC ================= */

/* Amount color (text) */
function amtClass($amt){
    if ($amt > 0) return 'amt-red';
    return 'amt-green';
}

/* STUDENT ROW COLOR (FINAL) */
function rowClass($total){
    if ($total > 0) return 'row-due';   // RED ROW
    return 'row-paid';                  // GREEN ROW
}

/* ================= AVAILABLE MONTHS ================= */
$months = [];
$mres = $conn->query("
    SELECT DISTINCT DATE_FORMAT(month_year,'%Y-%m') ym
    FROM messfee
    ORDER BY ym DESC
");
while($m = $mres->fetch_assoc()){
    $months[] = $m['ym'];
}

/* ================= STPO NAME ================= */
$nameStmt = $conn->prepare("SELECT NAME FROM kiet_staff WHERE EMPID=?");
$nameStmt->bind_param("s", $stpo);
$nameStmt->execute();
$stpoName = $nameStmt->get_result()->fetch_assoc()['NAME'] ?? '—';

/* ================= OVERALL TOTAL ================= */
$totalStmt = $conn->prepare("
    SELECT SUM(IFNULL(m.due,0)) total
    FROM STUDENTS s
    LEFT JOIN messfee m ON s.htno = m.htno
    WHERE s.stpo = ?
      AND (s.debarred = 0 OR s.debarred IS NULL)
      AND DATE_FORMAT(m.month_year,'%Y-%m') = ?
");
$totalStmt->bind_param("ss", $stpo, $selectedMonth);
$totalStmt->execute();
$overallTotal = (float)($totalStmt->get_result()->fetch_assoc()['total'] ?? 0);

/* ================= STUDENT DATA ================= */
$stmt = $conn->prepare("
    SELECT 
        s.htno,
        s.name,
        s.teamid,
        IFNULL(curr.due,0) AS curr_due,
        IFNULL(old.old_due,0) AS old_due
    FROM STUDENTS s
    LEFT JOIN (
        SELECT htno, due
        FROM messfee
        WHERE DATE_FORMAT(month_year,'%Y-%m') = ?
    ) curr ON curr.htno = s.htno
    LEFT JOIN (
        SELECT htno, SUM(due) old_due
        FROM messfee
        WHERE DATE_FORMAT(month_year,'%Y-%m') < ?
        GROUP BY htno
    ) old ON old.htno = s.htno
    WHERE s.stpo = ?
      AND (s.debarred = 0 OR s.debarred IS NULL)
    ORDER BY 
        SUBSTRING_INDEX(s.teamid,'_',1),
        CAST(SUBSTRING_INDEX(s.teamid,'_',-1) AS UNSIGNED),
        s.htno
");
$stmt->bind_param("sss", $selectedMonth, $selectedMonth, $stpo);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>STPO Team Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6fb;
    padding:20px;
    font-family:Inter, Arial, sans-serif;
}
.table{
    background:#fff;
    border-radius:10px;
    overflow:hidden;
}
.table thead th{
    background:#0f172a !important;
    color:#fff;
    font-size:13px;
}

/* ===== AMOUNT TEXT COLORS ===== */
.amt-red{ color:#b91c1c; font-weight:700; }
.amt-green{ color:#15803d; font-weight:700; }

/* ===== STUDENT ROW COLORS ===== */
.row-due{ background:#fee2e2 !important; }    /* RED */
.row-paid{ background:#dcfce7 !important; }  /* GREEN */

/* TEAM HEADER */
.team-row td{
    background:#e0f2fe !important;
    color:#075985;
    font-weight:700;
    border-top:2px solid #38bdf8;
}
</style>
</head>

<body>

<h3>
📊 STPO TEAM REPORT — <?= htmlspecialchars($stpo) ?> (<?= htmlspecialchars($stpoName) ?>)
</h3>
<p><strong>MONTH:</strong> <?= htmlspecialchars($monthName) ?></p>

<form method="get" class="mb-3">
    <input type="hidden" name="stpo" value="<?= htmlspecialchars($stpo) ?>">
    <select name="month" class="form-select w-auto d-inline" onchange="this.form.submit()">
        <?php foreach($months as $m): ?>
        <option value="<?= $m ?>" <?= $m==$selectedMonth?'selected':'' ?>>
            <?= date("F Y", strtotime($m.'-01')) ?>
        </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="mb-3">
    <strong>OVERALL DUE:</strong>
    <span class="<?= amtClass($overallTotal) ?>">
        <?= number_format($overallTotal,2) ?>
    </span>
</div>

<table class="table table-bordered table-sm table-striped">
<thead>
<tr>
    <th>#</th>
    <th>HTNO</th>
    <th>NAME</th>
    <th>TEAM</th>
    <th>OLD</th>
    <th>CURRENT</th>
    <th>TOTAL</th>
</tr>
</thead>

<tbody>
<?php
$sno = 1;
$currentTeam = null;

while ($r = $result->fetch_assoc()):
    $old   = (float)$r['old_due'];
    $curr  = (float)$r['curr_due'];
    $total = $old + $curr;

    if ($currentTeam !== $r['teamid']) {

        $tstmt = $conn->prepare("
            SELECT 
                SUM(IFNULL(m.due,0)) curr,
                (SELECT SUM(due)
                 FROM messfee mm
                 JOIN STUDENTS ss ON ss.htno=mm.htno
                 WHERE ss.teamid=? AND DATE_FORMAT(mm.month_year,'%Y-%m') < ?) old
            FROM STUDENTS s
            LEFT JOIN messfee m ON s.htno=m.htno
                AND DATE_FORMAT(m.month_year,'%Y-%m')=?
            WHERE s.teamid=? AND s.stpo=?
        ");
        $tstmt->bind_param("sssss", $r['teamid'], $selectedMonth, $selectedMonth, $r['teamid'], $stpo);
        $tstmt->execute();
        $t = $tstmt->get_result()->fetch_assoc();

        $teamOld   = (float)($t['old'] ?? 0);
        $teamCurr  = (float)($t['curr'] ?? 0);
        $teamTotal = $teamOld + $teamCurr;

        echo "
        <tr class='team-row'>
            <td colspan='7'>
                TEAM: {$r['teamid']} |
                OLD: <span class='".amtClass($teamOld)."'>".number_format($teamOld,2)."</span> |
                CURRENT: <span class='".amtClass($teamCurr)."'>".number_format($teamCurr,2)."</span> |
                TOTAL: <span class='".amtClass($teamTotal)."'>".number_format($teamTotal,2)."</span>
            </td>
        </tr>";

        $currentTeam = $r['teamid'];
    }
?>
<tr class="<?= rowClass($total) ?>">
    <td><?= $sno++ ?></td>
    <td><?= htmlspecialchars($r['htno']) ?></td>
    <td><?= htmlspecialchars($r['name']) ?></td>
    <td><?= htmlspecialchars($r['teamid']) ?></td>
    <td class="<?= amtClass($old) ?>"><?= number_format($old,2) ?></td>
    <td class="<?= amtClass($curr) ?>"><?= number_format($curr,2) ?></td>
    <td class="<?= amtClass($total) ?>"><?= number_format($total,2) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<a href="javascript:history.back()" class="btn btn-secondary mt-3">⬅ Back</a>

</body>
</html>
