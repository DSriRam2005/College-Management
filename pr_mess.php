<?php
session_start();
include 'db.php';
$conn->query("SET SQL_BIG_SELECTS=1");

/* ================= ACCESS ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

/* ================= HELPERS ================= */
function safe_format($value) {
    return number_format((float)($value ?? 0), 2);
}

/* ================= CURRENT MONTH ================= */
$currentMonth = date('Y-m-01'); // ✅ FIXED

/* ================= FILTERS ================= */
$selectedProg = $_GET['prog'] ?? 'B.TECH';
$selectedYear = $_GET['year'] ?? '';

/* ================= WHERE ================= */
$where = "
WHERE s.classid IS NOT NULL 
  AND s.classid <> ''
  AND (s.debarred = 0 OR s.debarred IS NULL)
  AND s.prog = '".$conn->real_escape_string($selectedProg)."'
";
if (!empty($selectedYear)) {
    $where .= " AND s.year = '".$conn->real_escape_string($selectedYear)."'";
}

/* ================= SQL ================= */
$sql = "
SELECT 
    s.classid,
    SUM(CASE 
        WHEN f.month_year < DATE_FORMAT(CURDATE(), '%Y-%m-01') 
        THEN f.due ELSE 0 END
    ) AS old_due,

    SUM(CASE 
        WHEN YEAR(f.month_year)=YEAR(CURDATE()) 
         AND MONTH(f.month_year)=MONTH(CURDATE())
        THEN f.due ELSE 0 END
    ) AS current_due,

    SUM(CASE 
        WHEN YEAR(f.month_year)=YEAR(CURDATE()) 
         AND MONTH(f.month_year)=MONTH(CURDATE())
        THEN f.ttamt ELSE 0 END
    ) AS this_month_total

FROM messfee f
JOIN STUDENTS s ON s.htno = f.htno
$where
GROUP BY s.classid
";

$res = $conn->query($sql);

/* ================= DATA ================= */
$data = [];
$grand = ['old_due'=>0,'current_due'=>0,'paid'=>0,'this_month_total'=>0];

while ($row = $res->fetch_assoc()) {

    $old_due = (float)($row['old_due'] ?? 0);
    $current_due = (float)($row['current_due'] ?? 0);
    $this_month_total = (float)($row['this_month_total'] ?? 0);

    if ($this_month_total <= 0) continue; // keep ranking clean

    $paid_this_month = $this_month_total - $current_due;
    $paid_percent = ($this_month_total > 0)
        ? ($paid_this_month / $this_month_total) * 100
        : 0;

    $data[] = [
        'classid' => $row['classid'],
        'old_due' => $old_due,
        'current_due' => $current_due,
        'paid' => $paid_this_month,
        'percent' => $paid_percent
    ];

    $grand['old_due'] += $old_due;
    $grand['current_due'] += $current_due;
    $grand['paid'] += $paid_this_month;
    $grand['this_month_total'] += $this_month_total;
}

$grand['percent'] = ($grand['this_month_total'] > 0)
    ? ($grand['paid'] / $grand['this_month_total']) * 100
    : 0;

/* ================= SORT ================= */
usort($data, fn($a,$b) => $b['percent'] <=> $a['percent']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class-wise Mess Fee Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="p-3">
<div class="container-fluid">

<h2 class="mb-4 text-center">
🍽 Class-wise Mess Fee Report — <?= date('F Y') ?>
</h2>

<!-- ================= FILTERS ================= -->
<form method="get" class="row g-3 mb-3">

<div class="col-md-3">
<label class="form-label fw-bold">Program</label>
<select name="prog" class="form-select" onchange="this.form.submit()">
<option value="B.TECH" <?= $selectedProg=='B.TECH'?'selected':'' ?>>B.TECH</option>
<option value="DIP" <?= $selectedProg=='DIP'?'selected':'' ?>>DIP</option>
<option value="M.TECH" <?= $selectedProg=='M.TECH'?'selected':'' ?>>M.TECH</option>
<option value="MBA" <?= $selectedProg=='MBA'?'selected':'' ?>>MBA</option>
</select>
</div>

<div class="col-md-3">
<label class="form-label fw-bold">Year</label>
<select name="year" class="form-select" onchange="this.form.submit()">
<option value="">All</option>
<option value="25" <?= $selectedYear=='25'?'selected':'' ?>>1</option>
<option value="24" <?= $selectedYear=='24'?'selected':'' ?>>2</option>
<option value="23" <?= $selectedYear=='23'?'selected':'' ?>>3</option>
<option value="22" <?= $selectedYear=='22'?'selected':'' ?>>4</option>
</select>
</div>

</form>

<!-- ================= TABLE ================= -->
<table id="messTable" class="table table-bordered table-striped text-center align-middle">
<thead class="table-dark">
<tr>
<th>Rank</th>
<th>Class ID</th>
<th>Old Due</th>
<th>This Month Due</th>
<th>Paid</th>
<th>% Paid</th>
</tr>
</thead>

<tbody>
<?php $rank=1; foreach($data as $row): ?>
<tr>
<td><?= $rank++ ?></td>

<td>
<a href="cpto_messdue.php?classid=<?= urlencode($row['classid']) ?>&prog=<?= urlencode($selectedProg) ?>&year=<?= urlencode($selectedYear) ?>">
<?= htmlspecialchars($row['classid']) ?>
</a>
</td>

<td class="text-warning fw-bold"><?= safe_format($row['old_due']) ?></td>
<td class="text-danger fw-bold"><?= safe_format($row['current_due']) ?></td>
<td class="text-success fw-bold"><?= safe_format($row['paid']) ?></td>

<td class="fw-bold <?= $row['percent']>=80?'text-success':($row['percent']>=50?'text-warning':'text-danger') ?>">
<?= safe_format($row['percent']) ?>%
</td>
</tr>
<?php endforeach; ?>

<tr class="table-warning fw-bold not-searchable">
<td>-</td>
<td>Grand Total</td>
<td><?= safe_format($grand['old_due']) ?></td>
<td><?= safe_format($grand['current_due']) ?></td>
<td><?= safe_format($grand['paid']) ?></td>
<td><?= safe_format($grand['percent']) ?>%</td>
</tr>

</tbody>
</table>

<a href="pr_home.php" class="btn btn-secondary mt-3">⬅ Back</a>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function(){
    $('#messTable').DataTable({
        paging:false,
        info:false,
        ordering:false
    });
});
</script>

</body>
</html>
