<?php
session_start();
include 'db.php';

// ✅ Restrict access
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$classid = $_GET['classid'] ?? "";
if ($classid == "") {
    die("Class ID not provided.");
}

// ✅ Fetch payment history for all non-debarred students in the class
$stmt = $conn->prepare("
    SELECT 
        P.id, P.htno, S.name, P.teamid, 
        P.paid_tf, P.paid_ot, P.paid_bus, P.paid_hos, P.paid_old, 
        P.pay_date, P.receiptno, P.method
    FROM PAYMENTS P
    JOIN STUDENTS S ON P.htno = S.htno
    WHERE S.classid = ? 
      AND (S.debarred = 0 OR S.debarred IS NULL)
    ORDER BY P.pay_date DESC, P.id DESC
");
$stmt->bind_param("s", $classid);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History</title>
    <style>
        body {
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background:#f8f9fa;
        }
        h2 {
            text-align:center; 
            color:#2d3436;
        }
        table {
            width:100%; 
            border-collapse: collapse; 
            margin-top:20px;
            background:white;
            box-shadow:0 0 8px rgba(0,0,0,0.1);
        }
        th, td {
            border:1px solid #ccc; 
            padding:8px; 
            text-align:center;
        }
        th {
            background:#0984e3; 
            color:white;
        }
        tr:nth-child(even){
            background:#f1f2f6;
        }
        a.back {
            display:inline-block; 
            margin:15px 0; 
            color:#0984e3; 
            text-decoration:none;
            font-weight:bold;
        }
        a.back:hover {
            text-decoration:underline;
        }
        .total {
            font-weight:bold;
            color:#2d3436;
        }
    </style>
</head>
<body>
    <h2>Payment History - <?= htmlspecialchars($classid) ?></h2>
    <a href="javascript:history.back()" class="back">⬅ Back</a>

    <table>
        <tr>
            <th>ID</th>
            <th>HTNO</th>
            <th>Name</th>
            <th>Team</th>
            <th>Tuition Fee</th>
            <th>Other Fee</th>
            <th>Bus Fee</th>
            <th>Hostel Fee</th>
            <th>Old Dues</th>
            <th>Total Paid</th>
            <th>Date</th>
            <th>Receipt No</th>
            <th>Method</th>
        </tr>

        <?php if ($res->num_rows > 0): ?>
            <?php while($row = $res->fetch_assoc()): 
                $total_paid = $row['paid_tf'] + $row['paid_ot'] + $row['paid_bus'] + $row['paid_hos'] + $row['paid_old'];
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['htno']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['teamid']) ?></td>
                <td>₹ <?= number_format($row['paid_tf']) ?></td>
                <td>₹ <?= number_format($row['paid_ot']) ?></td>
                <td>₹ <?= number_format($row['paid_bus']) ?></td>
                <td>₹ <?= number_format($row['paid_hos']) ?></td>
                <td>₹ <?= number_format($row['paid_old']) ?></td>
                <td class="total">₹ <?= number_format($total_paid) ?></td>
                <td><?= htmlspecialchars($row['pay_date']) ?></td>
                <td><?= htmlspecialchars($row['receiptno']) ?></td>
                <td><?= htmlspecialchars($row['method']) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="13" style="color:red;">No payment records found for this class.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
