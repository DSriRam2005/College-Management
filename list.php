<?php
session_start();
include 'db.php';

// ✅ Only allow PR role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

// --- Fetch all payments ---
$res = $conn->query("
    SELECT p.*, s.classid 
    FROM PAYMENTS p
    LEFT JOIN STUDENTS s ON p.htno = s.htno
    ORDER BY p.pay_date DESC, p.id DESC
");

$data = [];
while ($row = $res->fetch_assoc()) {
    $base = [
        'htno'      => $row['htno'],
        'name'      => $row['name'],
        'classid'   => $row['classid'],
        'receiptno' => $row['receiptno'],
        'method'    => $row['method'],
        'date'      => $row['pay_date'],
    ];

    // Expand into rows per category
    if ($row['paid_tf'] > 0) $data[] = $base + ['category'=>'Tuition Fee','amount'=>$row['paid_tf']];
    if ($row['paid_ot'] > 0) $data[] = $base + ['category'=>'Other Fee','amount'=>$row['paid_ot']];
    if ($row['paid_bus'] > 0) $data[] = $base + ['category'=>'Bus Fee','amount'=>$row['paid_bus']];
    if ($row['paid_hos'] > 0) $data[] = $base + ['category'=>'Hostel Fee','amount'=>$row['paid_hos']];
    if ($row['paid_old'] > 0) $data[] = $base + ['category'=>'Old Dues','amount'=>$row['paid_old']];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="p-3">
<div class="container-fluid">
    <h3 class="mb-3">Payment History</h3>

    <table id="payTable" class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>SNO</th>
                <th>HTNO</th>
                <th>Name</th>
                <th>Class</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th>Method</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $sno=1; foreach($data as $row): ?>
                <tr>
                    <td><?php echo $sno++; ?></td>
                    <td><?php echo $row['htno']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['classid']; ?></td>
                    <td><?php echo $row['category']; ?></td>
                    <td class="fw-bold text-success">₹<?php echo number_format($row['amount'],2); ?></td>
                    <td><?php echo $row['receiptno']; ?></td>
                    <td><?php echo $row['method']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="pr_home.php" class="btn btn-secondary mt-3">Back</a>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#payTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true
    });
});
</script>
</body>
</html>
