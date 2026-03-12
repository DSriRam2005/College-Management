<?php
session_start();
include 'db.php';

/* ONLY PR */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

/* FILTERS */
$selectedProg = $_GET['prog'] ?? 'ALL';
$selectedYear = $_GET['year'] ?? '';
$view = $_GET['view'] ?? 'clean';   // clean | actual

$where = "WHERE s.classid IS NOT NULL AND s.classid<>'' AND (s.debarred=0 OR s.debarred IS NULL)";
if ($selectedProg !== 'ALL') {
    $where .= " AND s.prog = '".$conn->real_escape_string($selectedProg)."'";
}
if (!empty($selectedYear)) {
    $where .= " AND s.year = '".$conn->real_escape_string($selectedYear)."'";
}

/* QUERY */
if ($view === 'clean') {
    $sql = "
        SELECT 
            s.classid,
            u.EMP_ID AS ctpoid,
            u.name AS ctponame,

            SUM(
                IFNULL(CASE WHEN s.tfdue_12_9>0 THEN s.tfdue_12_9 ELSE 0 END,0) +
                IFNULL(CASE WHEN s.otdues_12_9>0 THEN s.otdues_12_9 ELSE 0 END,0) +
                IFNULL(CASE WHEN s.busdue_12_9>0 THEN s.busdue_12_9 ELSE 0 END,0) +
                IFNULL(CASE WHEN s.hosdue_12_9>0 THEN s.hosdue_12_9 ELSE 0 END,0) +
                IFNULL(CASE WHEN s.olddue_12_9>0 THEN s.olddue_12_9 ELSE 0 END,0)
            ) AS totaldue,

            SUM(
                IFNULL(CASE WHEN s.tfdue_today>0 THEN s.tfdue_today ELSE 0 END,0) +
                IFNULL(CASE WHEN s.otdues_today>0 THEN s.otdues_today ELSE 0 END,0) +
                IFNULL(CASE WHEN s.busdue_today>0 THEN s.busdue_today ELSE 0 END,0) +
                IFNULL(CASE WHEN s.hosdue_today>0 THEN s.hosdue_today ELSE 0 END,0) +
                IFNULL(CASE WHEN s.olddue_today>0 THEN s.olddue_today ELSE 0 END,0)
            ) AS todaydue

        FROM STUDENTS s
        LEFT JOIN USERS u ON u.classid=s.classid AND u.role='CPTO'
        $where
        GROUP BY s.classid
    ";
} else {
    $sql = "
        SELECT 
            s.classid,
            u.EMP_ID AS ctpoid,
            u.name AS ctponame,

            SUM(IFNULL(s.tfdue_12_9,0)+IFNULL(s.otdues_12_9,0)+IFNULL(s.busdue_12_9,0)+
                IFNULL(s.hosdue_12_9,0)+IFNULL(s.olddue_12_9,0)) AS totaldue,

            SUM(IFNULL(s.tfdue_today,0)+IFNULL(s.otdues_today,0)+IFNULL(s.busdue_today,0)+
                IFNULL(s.hosdue_today,0)+IFNULL(s.olddue_today,0)) AS todaydue,

            COALESCE(SUM(p.total_paid),0) AS paid

        FROM STUDENTS s
        LEFT JOIN USERS u ON u.classid=s.classid AND u.role='CPTO'
        LEFT JOIN (
            SELECT htno,
                   SUM(paid_tf+paid_ot+paid_bus+paid_hos+paid_old) AS total_paid
            FROM PAYMENTS
            GROUP BY htno
        ) p ON p.htno=s.htno
        $where
        GROUP BY s.classid
    ";
}

$res = $conn->query($sql);

/* DATA PROCESS */
$data = [];
while($r = $res->fetch_assoc()){
    $total = $r['totaldue'];
    $today = $r['todaydue'];
    $paid  = ($view === 'clean') ? ($total - $today) : $r['paid'];
    $paidp = ($total>0) ? ($paid/$total)*100 : 0;

    $data[] = [
        'classid'=>$r['classid'],
        'ctpoid'=>$r['ctpoid'],
        'ctponame'=>$r['ctponame'],
        'total'=>$total,
        'today'=>$today,
        'paid'=>$paid,
        'paidp'=>$paidp
    ];
}

/* SORT */
usort($data, fn($a,$b)=> $b['paidp'] <=> $a['paidp']);

/* GRAND TOTAL */
$grand = ['total'=>0,'today'=>0,'paid'=>0];
foreach ($data as $d) {
    $grand['total'] += $d['total'];
    $grand['today'] += $d['today'];
    $grand['paid']  += $d['paid'];
}
$grand['paidp'] = ($grand['total']>0) ? ($grand['paid']/$grand['total'])*100 : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>CTPO Class Ranking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print{
    .no-print{display:none;}
}
</style>
</head>
<body class="p-3">

<div class="d-flex justify-content-between align-items-center">
    <h3>CTPO Class-wise Dues Ranking</h3>
    <button onclick="window.print()" class="btn btn-success no-print">Print</button>
</div>

<!-- FILTERS -->
<form method="get" class="row g-3 mb-3 no-print">
<input type="hidden" name="view" value="<?= $view ?>">

<div class="col-md-3">
    <label class="fw-bold">Program</label>
    <select name="prog" class="form-select" onchange="this.form.submit()">
        <option value="ALL" <?=($selectedProg=='ALL')?'selected':''?>>ALL</option>
        <option value="B.TECH" <?=($selectedProg=='B.TECH')?'selected':''?>>B.TECH</option>
        <option value="DIP" <?=($selectedProg=='DIP')?'selected':''?>>DIP</option>
        <option value="M.TECH" <?=($selectedProg=='M.TECH')?'selected':''?>>M.TECH</option>
        <option value="MBA" <?=($selectedProg=='MBA')?'selected':''?>>MBA</option>
        <option value="MCA" <?=($selectedProg=='MCA')?'selected':''?>>MCA</option>
    </select>
</div>

<div class="col-md-3">
    <label class="fw-bold">Year</label>
    <select name="year" class="form-select" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="25" <?=($selectedYear=='25')?'selected':''?>>1</option>
        <option value="24" <?=($selectedYear=='24')?'selected':''?>>2</option>
        <option value="23" <?=($selectedYear=='23')?'selected':''?>>3</option>
        <option value="22" <?=($selectedYear=='22')?'selected':''?>>4</option>
    </select>
</div>
</form>

<!-- VIEW TOGGLE -->
<div class="mb-3 no-print">
<a href="?view=clean&prog=<?=$selectedProg?>&year=<?=$selectedYear?>" class="btn btn-<?=$view=='clean'?'primary':'secondary'?>">Clean View</a>
<a href="?view=actual&prog=<?=$selectedProg?>&year=<?=$selectedYear?>" class="btn btn-<?=$view=='actual'?'primary':'secondary'?>">Actual View</a>
</div>

<!-- TABLE -->
<table class="table table-bordered table-striped text-center">
<thead class="table-dark">
<tr>
    <th>RANK</th>
    <th>CLASSID</th>
    <th>CTPOID</th>
    <th>CTPONAME</th>
    <th>TOTALDUE</th>
    <th>TODAYDUE</th>
    <th>PAID</th>
    <th>PAID%</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($data as $d): ?>
<tr>
    <td><?=$rank++?></td>
    <td>
    <?php if($view=='clean'): ?>
        <a href="view_sem_dues.php?classid=<?=$d['classid']?>&prog=<?=$selectedProg?>&year=<?=$selectedYear?>" 
           class="fw-bold text-decoration-none">
           <?=$d['classid']?>
        </a>
    <?php else: ?>
        <?=$d['classid']?>
    <?php endif; ?>
    </td>
    <td><?=$d['ctpoid']?></td>
    <td><?=$d['ctponame']?></td>
    <td><?=number_format($d['total'],2)?></td>
    <td><?=number_format($d['today'],2)?></td>
    <td class="fw-bold text-primary"><?=number_format($d['paid'],2)?></td>
    <td class="fw-bold text-success"><?=number_format($d['paidp'],2)?>%</td>
</tr>
<?php endforeach; ?>

<!-- TOTAL ROW -->
<tr class="table-warning fw-bold">
    <td>-</td>
    <td colspan="3">TOTAL</td>
    <td><?= number_format($grand['total'],2) ?></td>
    <td><?= number_format($grand['today'],2) ?></td>
    <td class="text-primary"><?= number_format($grand['paid'],2) ?></td>
    <td class="text-success"><?= number_format($grand['paidp'],2) ?>%</td>
</tr>

</tbody>
</table>

</body>
</html>
