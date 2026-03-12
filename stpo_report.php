<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR','ADMIN'])) {
    die("ACCESS DENIED");
}

$currentMonth = date('Y-m-01');

/* ================= FETCH STPO DATA ================= */
$sql = "
SELECT s.stpo, k.NAME AS stpo_name, s.teamid, s.htno
FROM STUDENTS s
LEFT JOIN kiet_staff k ON k.EMPID = s.stpo
WHERE s.stpo IS NOT NULL AND s.stpo != ''
  AND s.teamid IS NOT NULL AND s.teamid != ''
";
$res = $conn->query($sql) or die($conn->error);

/* ================= FETCH MESS FEES ================= */
$mess = [];
$mres = $conn->query("SELECT htno, month_year, ttamt, due FROM messfee");
while ($m = $mres->fetch_assoc()) {
    if (!isset($mess[$m['htno']])) {
        $mess[$m['htno']] = [
            'prev_due'=>0,
            'curr_ttamt'=>0,
            'curr_due'=>0
        ];
    }

    if ($m['month_year'] < $currentMonth) {
        $mess[$m['htno']]['prev_due'] += $m['due'];
    }
    if ($m['month_year'] == $currentMonth) {
        $mess[$m['htno']]['curr_ttamt'] += $m['ttamt'];
        $mess[$m['htno']]['curr_due']   += $m['due'];
    }
}

/* ================= BUILD REPORT ================= */
$report = [];
while ($r = $res->fetch_assoc()) {
    $stpo = $r['stpo'];

    if (!isset($report[$stpo])) {
        $report[$stpo] = [
            'name'        => $r['stpo_name'],
            'teams'       => [],
            'students'    => [],
            'prev_due'    => 0,
            'curr_ttamt'  => 0,
            'curr_due'    => 0,
            'paid_amt'    => 0,
            'paid_pct'    => null
        ];
    }

    $report[$stpo]['teams'][$r['teamid']] = true;
    $report[$stpo]['students'][$r['htno']] = true;

    if (isset($mess[$r['htno']])) {
        $report[$stpo]['prev_due']   += $mess[$r['htno']]['prev_due'];
        $report[$stpo]['curr_ttamt'] += $mess[$r['htno']]['curr_ttamt'];
        $report[$stpo]['curr_due']   += $mess[$r['htno']]['curr_due'];
    }
}

/* ================= PAID AMOUNT + PAID % ================= */
foreach ($report as &$r) {
    $r['paid_amt'] = max(0, $r['curr_ttamt'] - $r['curr_due']);
    if ($r['curr_ttamt'] > 0) {
        $r['paid_pct'] = ($r['paid_amt'] / $r['curr_ttamt']) * 100;
    }
}
unset($r);

/* ================= SORT: PAID % → THEN FEE ================= */
uasort($report, function($a, $b){
    $pctA = $a['paid_pct'] ?? -1;
    $pctB = $b['paid_pct'] ?? -1;

    if ($pctA == $pctB) {
        return ($b['curr_ttamt'] ?? 0) <=> ($a['curr_ttamt'] ?? 0);
    }
    return $pctB <=> $pctA;
});

function paidColor($pct, $fee){
    if ($fee <= 0) return '';
    if ($pct >= 80) return 'ok';
    if ($pct >= 50) return 'warn';
    return 'bad';
}

/* ================= TOTALS ================= */
$totalTeams = $totalStudents = 0;
$totalPrevDue = $totalCurrFee = $totalCurrDue = $totalPaidAmt = 0;

foreach ($report as $r) {
    $totalTeams    += count($r['teams']);
    $totalStudents += count($r['students']);
    $totalPrevDue  += $r['prev_due'];
    $totalCurrFee  += $r['curr_ttamt'];
    $totalCurrDue  += $r['curr_due'];
    $totalPaidAmt  += $r['paid_amt'];
}

$totalPaidPct = ($totalCurrFee > 0)
    ? ($totalPaidAmt / $totalCurrFee) * 100
    : null;

$totalCls = paidColor($totalPaidPct, $totalCurrFee);
?>
<!DOCTYPE html>
<html>
<head>
<title>STPO – MONTHLY FEE STATUS</title>
<meta charset="utf-8">
<style>
body{font-family:Inter,Arial;background:#f4f6fb;padding:30px;text-transform:uppercase}
.card{background:#fff;border-radius:14px;padding:25px;box-shadow:0 10px 25px rgba(0,0,0,.08)}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:center}
th{background:#f1f5f9;font-size:13px}
.name{text-align:left;font-weight:600;color:#0f172a}
.count{font-weight:600;color:#1e40af}
.money{font-weight:600}
.muted{color:#6b7280}
.prev-due:not(.zero){background:#fef2f2;color:#991b1b}
.curr-fee:not(.zero){background:#eff6ff;color:#1e40af}
.curr-due:not(.zero){background:#fffbeb;color:#92400e}
.paid-amt:not(.zero){background:#ecfdf5;color:#065f46}
.badge{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600}
.ok{background:#dcfce7;color:#166534}
.warn{background:#fef3c7;color:#92400e}
.bad{background:#fee2e2;color:#991b1b}
.progress{background:#e5e7eb;border-radius:10px;height:8px;margin-top:6px}
.progress.ok div{background:#22c55e}
.progress.warn div{background:#eab308}
.progress.bad div{background:#ef4444}
tfoot td{font-weight:700;background:#f8fafc;border-top:2px solid #cbd5e1}
</style>
</head>

<body>
<div class="card">
<h2>STPO – THIS MONTH FEE OVERVIEW</h2>

<table>
<thead>
<tr>
<th>S.NO</th>
<th>STPO</th>
<th>NAME</th>
<th>TEAMS</th>
<th>STUDENTS</th>
<th>PREVIOUS DUE</th>
<th>THIS MONTH FEE</th>
<th>THIS MONTH DUE</th>
<th>PAID AMOUNT</th>
<th>PAID %</th>
</tr>
</thead>

<tbody>
<?php $sno=1; foreach ($report as $empid=>$r): $cls=paidColor($r['paid_pct'],$r['curr_ttamt']); ?>
<tr>
<td><?= $sno++ ?></td>
<td><a href="stpo_team_report.php?stpo=<?= urlencode($empid) ?>"><?= htmlspecialchars($empid) ?></a></td>
<td class="name"><?= htmlspecialchars($r['name'] ?? '—') ?></td>
<td class="count"><?= count($r['teams']) ?></td>
<td class="count"><?= count($r['students']) ?></td>

<td class="money prev-due">
    <?= '₹'.number_format($r['prev_due'],2) ?>
</td>
    
<td class="money curr-fee">
    <?= '₹'.number_format($r['curr_ttamt'],2) ?>
</td>
    
<td class="money curr-due">
    <?= '₹'.number_format($r['curr_due'],2) ?>
</td>
    
<td class="money paid-amt">
    <?= '₹'.number_format($r['paid_amt'],2) ?>
</td>

<td>
<?php if ($r['curr_ttamt']>0): ?>
<span class="badge <?= $cls ?>"><?= number_format($r['paid_pct'],1) ?>%</span>
<div class="progress <?= $cls ?>"><div style="width:<?= min(100,$r['paid_pct']) ?>%"></div></div>
<?php else: ?><span class="muted">—</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>

<tfoot>
<tr>
<td colspan="3">TOTAL</td>
<td><?= $totalTeams ?></td>
<td><?= $totalStudents ?></td>
<td class="money"><?= $totalPrevDue>0?'₹'.number_format($totalPrevDue,2):'—' ?></td>
<td class="money"><?= $totalCurrFee>0?'₹'.number_format($totalCurrFee,2):'—' ?></td>
<td class="money"><?= $totalCurrDue>0?'₹'.number_format($totalCurrDue,2):'—' ?></td>
<td class="money"><?= $totalPaidAmt>0?'₹'.number_format($totalPaidAmt,2):'—' ?></td>
<td>
<?php if ($totalCurrFee>0): ?>
<span class="badge <?= $totalCls ?>"><?= number_format($totalPaidPct,1) ?>%</span>
<div class="progress <?= $totalCls ?>"><div style="width:<?= min(100,$totalPaidPct) ?>%"></div></div>
<?php else: ?><span class="muted">—</span><?php endif; ?>
</td>
</tr>
</tfoot>

</table>
</div>
</body>
</html>
