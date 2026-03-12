<?php
session_start();
include 'db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$conn->query("SET SQL_BIG_SELECTS=1");

/* ================= ACCESS ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: login.php");
    exit;
}

/* ================= HTPO DETAILS ================= */
$username = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT college, prog, year
    FROM USERS
    WHERE username=?
    LIMIT 1
");
$stmt->bind_param("s", $username);
$stmt->execute();
$htpo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$colleges = array_map('trim', explode(",", $htpo['college']));
$prog = strtoupper($htpo['prog']);
$year = (int)$htpo['year'];

/* ================= HELPERS ================= */
function safe_format($v) {
    return number_format((float)($v ?? 0), 2);
}

/* ================= FETCH CLASS IDS ================= */
$class_ids = [];

foreach ($colleges as $college) {
    $stmt = $conn->prepare("
        SELECT DISTINCT classid
        FROM STUDENTS
        WHERE FIND_IN_SET(?, college)
          AND prog=?
          AND year=?
          AND classid IS NOT NULL
          AND classid <> ''
          AND (debarred=0 OR debarred IS NULL)
    ");
    $stmt->bind_param("ssi", $college, $prog, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $class_ids[] = $r['classid'];
    }
    $stmt->close();
}

$class_ids = array_unique($class_ids);
sort($class_ids);

if (empty($class_ids)) {
    die("No classes assigned to this HTPO.");
}

/* ================= SQL ================= */
$placeholders = implode(",", array_fill(0, count($class_ids), "?"));
$types = str_repeat("s", count($class_ids));

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
WHERE s.classid IN ($placeholders)
GROUP BY s.classid
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$class_ids);
$stmt->execute();
$res = $stmt->get_result();

/* ================= DATA ================= */
$data = [];
$grand = ['old'=>0,'current'=>0,'paid'=>0,'total'=>0];

while ($r = $res->fetch_assoc()) {

    $total = (float)$r['this_month_total'];
    if ($total <= 0) continue;

    $current = (float)$r['current_due'];
    $paid = $total - $current;
    $percent = $total ? ($paid / $total) * 100 : 0;

    $data[] = [
        'classid' => $r['classid'],
        'old'     => (float)$r['old_due'],
        'current' => $current,
        'paid'    => $paid,
        'percent' => $percent
    ];

    $grand['old'] += $r['old_due'];
    $grand['current'] += $current;
    $grand['paid'] += $paid;
    $grand['total'] += $total;
}

$grand['percent'] = $grand['total']
    ? ($grand['paid'] / $grand['total']) * 100
    : 0;

/* ================= SORT ================= */
usort($data, fn($a,$b) => $b['percent'] <=> $a['percent']);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>HTPO – Mess Fee Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="p-4">

<div class="container-fluid">

<h3 class="text-center mb-4">
🍽 HTPO Class-wise Mess Fee Report — <?= date('F Y') ?><br>
<?= htmlspecialchars($htpo['college']) ?> | <?= $prog ?> | YEAR <?= $year ?>
</h3>

<table id="tbl" class="table table-bordered table-striped text-center align-middle">
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
<?php $rank=1; foreach ($data as $r): ?>
<tr>
<td><?= $rank++ ?></td>

<td>
<a href="cpto_messdue.php?classid=<?= urlencode($r['classid']) ?>">
<?= htmlspecialchars($r['classid']) ?>
</a>
</td>

<td class="text-warning fw-bold"><?= safe_format($r['old']) ?></td>
<td class="text-danger fw-bold"><?= safe_format($r['current']) ?></td>
<td class="text-success fw-bold"><?= safe_format($r['paid']) ?></td>

<td class="fw-bold <?= $r['percent']>=80?'text-success':($r['percent']>=50?'text-warning':'text-danger') ?>">
<?= safe_format($r['percent']) ?>%
</td>
</tr>
<?php endforeach; ?>

<tr class="table-warning fw-bold">
<td>-</td>
<td>Grand Total</td>
<td><?= safe_format($grand['old']) ?></td>
<td><?= safe_format($grand['current']) ?></td>
<td><?= safe_format($grand['paid']) ?></td>
<td><?= safe_format($grand['percent']) ?>%</td>
</tr>

</tbody>
</table>

<a href="htpo_home.php" class="btn btn-secondary mt-3">⬅ Back</a>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function(){
    $('#tbl').DataTable({
        paging:false,
        info:false,
        ordering:false
    });
});
</script>

</body>
</html>
