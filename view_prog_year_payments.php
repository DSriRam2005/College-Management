<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Allow big select for InfinityFree
$conn->query("SET SQL_BIG_SELECTS = 1");

// INPUTS
$prog = $_GET['prog'] ?? '';
$year = $_GET['year'] ?? '';
$date = $_GET['date'] ?? '';   // coming from previous page

// ✅ Fetch payments using STUDENTS prog+year and PAYMENTS filtered by date
$sql = $conn->prepare("
    SELECT 
        P.pay_date,
        S.prog,
        S.year,
        P.htno,
        P.name,
        P.receiptno,
        P.method,
        (P.paid_tf + P.paid_ot + P.paid_bus + P.paid_hos + P.paid_old + P.paid_mess) AS total_paid
    FROM STUDENTS S
    INNER JOIN PAYMENTS P ON P.htno = S.htno
    WHERE S.prog = ?
      AND S.year = ?
      AND P.pay_date = ?
      AND P.receiptno IS NOT NULL
      AND P.receiptno != ''
    ORDER BY P.pay_date DESC, P.id DESC
");

$sql->bind_param("sis", $prog, $year, $date);
$sql->execute();
$result = $sql->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Payments - <?= htmlspecialchars($prog) ?> / <?= htmlspecialchars($year) ?></title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<style>
body { font-family: Arial; margin: 20px; }
table { width: 100%; border-collapse: collapse; margin-top: 15px; }
th, td { border: 1px solid #ddd; padding: 8px; }
th { background: #f5f5f5; }
</style>
</head>

<body>

<h2>
    Payments List — <?= htmlspecialchars($prog) ?> / Year <?= htmlspecialchars($year) ?><br>
    <span style="font-size:14px; color:#555;">Date: <?= htmlspecialchars($date) ?></span>
</h2>

<table id="payTable">
<thead>
<tr>
    <th>Date</th>
    <th>HT No</th>
    <th>Name</th>
    <th>Receipt No</th>
    <th>Method</th>
    <th>Total Paid (₹)</th>
</tr>
</thead>
<tbody>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['pay_date'] ?></td>
    <td><?= $row['htno'] ?></td>
    <td><?= $row['name'] ?></td>

    <td>
        <a href="receipt_view.php?receiptno=<?= urlencode($row['receiptno']) ?>" 
           style="color:blue; text-decoration:underline;">
           <?= $row['receiptno'] ?>
        </a>
    </td>

    <td><?= $row['method'] ?></td>
    <td><b>₹ <?= number_format($row['total_paid'],2) ?></b></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
$(document).ready(() => {
    $('#payTable').DataTable();
});
</script>

</body>
</html>
