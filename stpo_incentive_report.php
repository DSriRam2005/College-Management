<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'STPO') {
    header("Location: index.php");
    exit();
}

$empid = $_SESSION['empid'];

/* ================= BTECH 2025 (EAPCET 2025 FROM STUDENTS) ================= */
$btech = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
    IFNULL(SUM(PHASE='1' AND CLG_TYPE='H'),0) AS ph1,
    IFNULL(SUM(PHASE='2' AND CLG_TYPE='H'),0) AS ph2,
    IFNULL(SUM(PHASE='3' AND CLG_TYPE='H'),0) AS ph3,
    IFNULL(SUM(PHASE='SPOT' AND CLG_TYPE='H'),0) AS spoth,
    IFNULL(SUM(PHASE='BCAT' AND CLG_TYPE='H'),0) AS bcath,

    IFNULL(SUM(PHASE='1' AND CLG_TYPE='D'),0) AS pd1,
    IFNULL(SUM(PHASE='2' AND CLG_TYPE='D'),0) AS pd2,
    IFNULL(SUM(PHASE='3' AND CLG_TYPE='D'),0) AS pd3,
    IFNULL(SUM(PHASE='SPOT' AND CLG_TYPE='D'),0) AS spotd,
    IFNULL(SUM(PHASE='BCAT' AND CLG_TYPE='D'),0) AS bcatd,

    COUNT(*) AS total_students,

    (
      (SUM(PHASE='1' AND CLG_TYPE='H') * 6000) +
      (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) +

      (SUM(PHASE='1' AND CLG_TYPE='D') * 4000) +
      (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000)
    ) AS total_btech_2025_amt

FROM STUDENTS
WHERE year=25
AND prog='B.TECH'
AND REFEMPID='$empid'
"));

/* ================= CETS ================= */
$cets = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT
    IFNULL(SUM(CASE WHEN admission_year=2025 AND cets='polycet' THEN incentive_amount END),0) AS poly_2025,
    IFNULL(SUM(CASE WHEN admission_year=2025 AND cets='ecet' THEN incentive_amount END),0) AS ecet_2025,

    IFNULL(SUM(CASE WHEN admission_year=2024 AND cets='eapcet' THEN incentive_amount END),0) AS eapcet_2024,
    IFNULL(SUM(CASE WHEN admission_year=2024 AND cets='polycet' THEN incentive_amount END),0) AS poly_2024,
    IFNULL(SUM(CASE WHEN admission_year=2024 AND cets='ecet' THEN incentive_amount END),0) AS ecet_2024

FROM admission_incentives
WHERE empid='$empid'
"));

/* ================= GIVEN ================= */
$given = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(given_incentives),0) AS given_amt
FROM admission_incentives_given
WHERE empid='$empid'
"));

/* ================= CALCULATIONS ================= */
$eapcet_total = $btech['total_btech_2025_amt'] + $cets['eapcet_2024'];
$polycet_total = $cets['poly_2025'] + $cets['poly_2024'];
$ecet_total = $cets['ecet_2025'] + $cets['ecet_2024'];

$grand_2025 = $btech['total_btech_2025_amt'] + $cets['poly_2025'] + $cets['ecet_2025'];
$grand_2024 = $cets['eapcet_2024'] + $cets['poly_2024'] + $cets['ecet_2024'];
$grand_total = $grand_2025 + $grand_2024;

$pending = $grand_total - $given['given_amt'];
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Incentive Dashboard</title>

<style>
body{margin:0;font-family:Inter,Segoe UI;background:#f1f5f9}
.container{max-width:1000px;margin:auto;padding:20px}
h2{margin-bottom:20px;color:#1e293b}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;margin-bottom:20px}
.card{background:#fff;padding:18px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.06)}
.card-title{font-size:13px;color:#64748b;margin-bottom:6px}
.card-value{font-size:22px;font-weight:700;color:#2563eb}
.table-box{background:#fff;padding:15px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.06);margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;border-bottom:1px solid #e2e8f0;text-align:center;font-size:14px}
th{background:#0f172a;color:#fff}
.total-row{background:#0f172a;color:#fff;font-weight:bold}
.badge{padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600}
.green{background:#dcfce7;color:#166534}
.red{background:#fee2e2;color:#991b1b}
</style>
</head>

<body>
<div class="container">

<h2>My Incentive Dashboard (EMPID: <?= $empid ?>)</h2>

<!-- SUMMARY -->
<div class="grid">

<div class="card">
<div class="card-title">Total Incentive</div>
<div class="card-value">₹<?= number_format($grand_total) ?></div>
</div>

<div class="card">
<div class="card-title">Given</div>
<div class="card-value">₹<?= number_format($given['given_amt']) ?></div>
</div>

<div class="card">
<div class="card-title">Pending</div>
<div class="card-value">
<span class="badge <?= $pending>0?'red':'green' ?>">
₹<?= number_format($pending) ?>
</span>
</div>
</div>

</div>

<!-- CETS TABLE -->
<div class="table-box">
<h3>CETS Wise Incentives</h3>
<table>
<tr>
<th>CETS</th>
<th>2025</th>
<th>2024</th>
<th>Total</th>
</tr>

<tr>
<td><b>EAPCET</b></td>
<td>₹<?= number_format($btech['total_btech_2025_amt']) ?></td>
<td>₹<?= number_format($cets['eapcet_2024']) ?></td>
<td>₹<?= number_format($eapcet_total) ?></td>
</tr>

<tr>
<td><b>POLYCET</b></td>
<td>₹<?= number_format($cets['poly_2025']) ?></td>
<td>₹<?= number_format($cets['poly_2024']) ?></td>
<td>₹<?= number_format($polycet_total) ?></td>
</tr>

<tr>
<td><b>ECET</b></td>
<td>₹<?= number_format($cets['ecet_2025']) ?></td>
<td>₹<?= number_format($cets['ecet_2024']) ?></td>
<td>₹<?= number_format($ecet_total) ?></td>
</tr>

<tr class="total-row">
<td>TOTAL</td>
<td>₹<?= number_format($grand_2025) ?></td>
<td>₹<?= number_format($grand_2024) ?></td>
<td>₹<?= number_format($grand_total) ?></td>
</tr>

</table>
</div>

</div>
</body>
</html>