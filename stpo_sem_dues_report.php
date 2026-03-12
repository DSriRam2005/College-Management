<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once "db.php";

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','ADMIN'])) {
    die("ACCESS DENIED");
}

/*
Debarred students are fully ignored.
TOTAL FEE  = sum of all dues columns (12_9)
TODAY DUE  = sum of all *_today columns
PAID      = TOTAL FEE - TODAY DUE
PAID %    = (PAID / TOTAL FEE) * 100
*/

$sql = "
SELECT 
    s.stpo,
    k.NAME AS stpo_name,
    s.teamid,
    s.htno,

    (
        GREATEST(s.tfdue_12_9,0)+
        GREATEST(s.otdues_12_9,0)+
        GREATEST(s.busdue_12_9,0)+
        GREATEST(s.hosdue_12_9,0)+
        GREATEST(s.olddue_12_9,0)
    ) AS total_fee,

    (
        GREATEST(s.tfdue_today,0)+
        GREATEST(s.otdues_today,0)+
        GREATEST(s.busdue_today,0)+
        GREATEST(s.hosdue_today,0)+
        GREATEST(s.olddue_today,0)
    ) AS today_due

FROM STUDENTS s
LEFT JOIN kiet_staff k ON k.EMPID = s.stpo
WHERE s.stpo IS NOT NULL 
  AND s.stpo != ''
  AND IFNULL(s.debarred,0) = 0
";

$res = $conn->query($sql) or die($conn->error);

/* ================= BUILD REPORT ================= */
$report = [];

while ($r = $res->fetch_assoc()) {
    $stpo = $r['stpo'];

    if (!isset($report[$stpo])) {
        $report[$stpo] = [
            'name'        => $r['stpo_name'],
            'teams'       => [],
            'students'    => [],
            'total_fee'   => 0,
            'today_due'   => 0,
            'paid'        => 0,
            'paid_pct'    => 0
        ];
    }

    $report[$stpo]['teams'][$r['teamid']] = true;
    $report[$stpo]['students'][$r['htno']] = true;
    $report[$stpo]['total_fee'] += $r['total_fee'];
    $report[$stpo]['today_due'] += $r['today_due'];
}

/* ================= CALCULATE PAID & % ================= */
foreach ($report as &$r) {
    $r['paid'] = max(0, $r['total_fee'] - $r['today_due']);
    if ($r['total_fee'] > 0) {
        $r['paid_pct'] = ($r['paid'] / $r['total_fee']) * 100;
    }
}
unset($r);

/* ================= SORT BY PAID % DESC ================= */
uasort($report, function($a, $b){
    return $b['paid_pct'] <=> $a['paid_pct'];
});

/* ================= TOTALS ================= */
$totalTeams = $totalStudents = 0;
$grandTotalFee = $grandTodayDue = $grandPaid = 0;

foreach ($report as $r) {
    $totalTeams += count($r['teams']);
    $totalStudents += count($r['students']);
    $grandTotalFee += $r['total_fee'];
    $grandTodayDue += $r['today_due'];
    $grandPaid     += $r['paid'];
}

$grandPaidPct = ($grandTotalFee > 0) ? ($grandPaid / $grandTotalFee) * 100 : 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>PR – FEE DUE REPORT</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{
    font-family:Inter,Arial;
    background:#f4f6fb;
    padding:12px;
    text-transform:uppercase;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:12px;
    box-shadow:0 6px 15px rgba(0,0,0,.08);
}

h2{
    font-size:16px;
    margin-bottom:10px;
}

/* Responsive table wrapper */
.table-responsive{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

/* Table */
table{
    width:100%;
    min-width:900px;   /* force horizontal scroll on mobile */
    border-collapse:collapse;
}

th,td{
    padding:6px 8px;
    border-bottom:1px solid #e5e7eb;
    text-align:center;
    font-size:11px;
}

th{
    background:#f1f5f9;
    white-space:nowrap;
}

.name{
    text-align:left;
    font-weight:600;
    color:#0f172a;
    white-space:nowrap;
}

.count{
    font-weight:600;
    color:#1e40af;
}

.money{
    font-weight:600;
    white-space:nowrap;
}

.badge{
    padding:3px 6px;
    border-radius:999px;
    font-size:10px;
    font-weight:600;
}

.ok{background:#dcfce7;color:#166534}
.warn{background:#fef3c7;color:#92400e}
.bad{background:#fee2e2;color:#991b1b}

/* Print */
@media print {
    body{background:#fff;padding:0}
    .card{box-shadow:none;border-radius:0;padding:0}
    .print-btn{display:none}
}

/* Mobile tuning */
@media (max-width:768px){
    body{padding:6px;}
    .card{padding:8px;}
    h2{font-size:14px;text-align:center;}
    th,td{padding:5px;font-size:10px;}
    .badge{font-size:9px;padding:2px 5px;}
}
</style>
</head>

<body>
<div class="card">

<div style="text-align:right;margin-bottom:8px;">
    <button onclick="window.print()" class="print-btn" style="
        padding:6px 12px;
        background:#2563eb;
        color:#fff;
        border:none;
        border-radius:6px;
        cursor:pointer;
        font-weight:600;">
        🖨 PRINT
    </button>
</div>

<h2>PR – FEE PAYMENT STATUS</h2>

<div class="table-responsive">
<table>
<thead>
<tr>
<th>S.NO</th>
<th>STPO</th>
<th>NAME</th>
<th>TEAMS</th>
<th>STUDENTS</th>
<th>TOTAL FEE</th>
<th>TODAY DUE</th>
<th>PAID</th>
<th>PAID %</th>
</tr>
</thead>

<tbody>
<?php 
$sno = 1;
foreach ($report as $empid => $r): 
$cls = 'bad';
if ($r['paid_pct'] >= 80) $cls = 'ok';
elseif ($r['paid_pct'] >= 50) $cls = 'warn';
?>
<tr>
<td><?= $sno++ ?></td>

<td>
    <a href="STPO_sem_dues.php?stpo=<?= urlencode($empid) ?>"
       style="color:#2563eb;font-weight:700;text-decoration:none;">
        <?= htmlspecialchars($empid) ?>
    </a>
</td>

<td class="name"><?= htmlspecialchars($r['name'] ?? '—') ?></td>
<td class="count"><?= count($r['teams']) ?></td>
<td class="count"><?= count($r['students']) ?></td>
<td class="money">₹<?= number_format($r['total_fee'],2) ?></td>
<td class="money">₹<?= number_format($r['today_due'],2) ?></td>
<td class="money">₹<?= number_format($r['paid'],2) ?></td>
<td>
    <span class="badge <?= $cls ?>"><?= number_format($r['paid_pct'],1) ?>%</span>
</td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr>
<td colspan="3">TOTAL</td>
<td><?= $totalTeams ?></td>
<td><?= $totalStudents ?></td>
<td class="money">₹<?= number_format($grandTotalFee,2) ?></td>
<td class="money">₹<?= number_format($grandTodayDue,2) ?></td>
<td class="money">₹<?= number_format($grandPaid,2) ?></td>
<td>
    <span class="badge ok"><?= number_format($grandPaidPct,1) ?>%</span>
</td>
</tr>
</tfoot>

</table>
</div>

</div>
</body>
</html>
