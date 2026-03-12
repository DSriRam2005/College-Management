<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ Allow heavy SELECT on InfinityFree
$conn->query("SET SQL_BIG_SELECTS = 1");

// ✅ Pre-aggregate payments first (reduces row scan)
$sql = "
    SELECT 
        PA.htno,
        MAX(PA.name) AS name,
        MAX(PA.pay_date) AS latest_date,
        SUM(PA.tot_paid) AS total_paid,
        MAX(PA.method) AS method,
        MAX(PA.receiptno) AS receiptno
    FROM (
        SELECT
            htno,
            name,
            pay_date,
            (paid_tf + paid_ot + paid_bus + paid_hos + paid_old + paid_mess) AS tot_paid,
            method,
            receiptno
        FROM PAYMENTS
    ) AS PA
    LEFT JOIN STUDENTS S ON S.htno = PA.htno
    WHERE S.htno IS NULL
    GROUP BY PA.htno
    ORDER BY latest_date DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>N/A Payments (HTNO missing in STUDENTS)</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 10px; border: 1px solid #ddd; }
h2 { margin-bottom: 15px; }
</style>
</head>
<body>

<h2>Payments That Do Not Match Any Student (N/A)</h2>

<table id="naPayments">
    <thead>
        <tr>
            <th>HTNO</th>
            <th>Name</th>
            <th>Total Paid (₹)</th>
            <th>Last Payment Date</th>
            <th>Receipt No</th>
            <th>Method</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['htno']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><b><?= number_format((float)$row['total_paid'], 2) ?></b></td>
            <td><?= htmlspecialchars($row['latest_date']) ?></td>
            <td><?= htmlspecialchars($row['receiptno']) ?></td>
            <td><?= htmlspecialchars($row['method']) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
    $("#naPayments").DataTable();
});
</script>

</body>
</html>
