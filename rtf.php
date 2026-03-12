<?php
session_start();
require_once "db.php";

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'CPTO') {
    die("ACCESS DENIED");
}

$username = $_SESSION['username'];

/* ================= GET CPTO CLASSID ================= */
$stmtUser = $conn->prepare("SELECT classid FROM USERS WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();

if (!$userData || empty($userData['classid'])) {
    die("No class assigned to this CPTO.");
}

$classid = $userData['classid'];

/* ================= FETCH STUDENTS WITH CORRECT TEAM ORDER ================= */
$stmt = $conn->prepare("
    SELECT s.htno, s.name, s.branch, s.year, s.teamid,
           f.total_due, f.amount_credited, 
           f.remaining_fee_due, f.fee_return_to_student
    FROM STUDENTS s
    INNER JOIN fee_details_25_02 f ON s.htno = f.htno
    WHERE s.classid = ?
    ORDER BY 
    CAST(SUBSTRING_INDEX(s.teamid, '_', -1) AS UNSIGNED) ASC,
    s.htno ASC
");
$stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();

/* ================= PREPARE DATA + TOTALS ================= */
$total_due = 0;
$total_credit = 0;
$total_remaining = 0;
$total_refund = 0;

$data = [];

while ($row = $result->fetch_assoc()) {

    $total_due += $row['total_due'];
    $total_credit += $row['amount_credited'];
    $total_remaining += $row['remaining_fee_due'];
    $total_refund += $row['fee_return_to_student'];

    $data[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>CPTO Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
@media print {
    .no-print { display: none; }
}
.team-block {
    background:#e9ecef;
    font-weight:bold;
    text-align:left;
}
</style>
</head>

<body class="bg-light">

<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>CPTO Dashboard - Class <?php echo htmlspecialchars($classid); ?></h4>
    <button onclick="window.print()" class="btn btn-primary no-print">Print</button>
</div>

<?php if (count($data) > 0): ?>

<!-- ================= TOTAL CARDS ================= -->
<div class="row mb-4 text-center no-print">

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-danger shadow-sm">
            <div class="card-body">
                <h6>Total Due</h6>
                <h5 class="text-danger">₹<?php echo number_format($total_due,2); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-success shadow-sm">
            <div class="card-body">
                <h6>Total Credit</h6>
                <h5 class="text-success">₹<?php echo number_format($total_credit,2); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-warning shadow-sm">
            <div class="card-body">
                <h6>Total Remaining</h6>
                <h5 class="text-warning">₹<?php echo number_format($total_remaining,2); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6 mb-2">
        <div class="card border-primary shadow-sm">
            <div class="card-body">
                <h6>Total Refund</h6>
                <h5 class="text-primary">₹<?php echo number_format($total_refund,2); ?></h5>
            </div>
        </div>
    </div>

</div>

<!-- ================= TABLE ================= -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover align-middle text-center">

<thead class="table-dark">
<tr>
    <th>Team</th>
    <th>HTNO</th>
    <th>Name</th>
    <th>Branch</th>
    <th>Year</th>
    <th>Total Due</th>
    <th>Will Credit</th>
    <th>Remaining</th>
    <th>Refund</th>
</tr>
</thead>

<tbody>

<?php
$current_team = null;

foreach ($data as $row):

    if ($current_team !== $row['teamid']):
        $current_team = $row['teamid'];
?>

<tr class="team-block">
    <td colspan="9">
        Team ID: <?php echo htmlspecialchars($current_team ?: 'NO TEAM'); ?>
    </td>
</tr>

<?php endif; ?>

<tr>
    <td><?php echo htmlspecialchars($row['teamid']); ?></td>
    <td><?php echo htmlspecialchars($row['htno']); ?></td>
    <td><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['branch']); ?></td>
    <td><?php echo htmlspecialchars($row['year']); ?></td>
    <td class="text-danger fw-bold">₹<?php echo number_format($row['total_due'],2); ?></td>
    <td class="text-success fw-bold">₹<?php echo number_format($row['amount_credited'],2); ?></td>
    <td class="text-warning fw-bold">₹<?php echo number_format($row['remaining_fee_due'],2); ?></td>
    <td class="text-primary fw-bold">₹<?php echo number_format($row['fee_return_to_student'],2); ?></td>
</tr>

<?php endforeach; ?>

</tbody>

<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="5">TOTAL</td>
    <td class="text-danger">₹<?php echo number_format($total_due,2); ?></td>
    <td class="text-success">₹<?php echo number_format($total_credit,2); ?></td>
    <td class="text-warning">₹<?php echo number_format($total_remaining,2); ?></td>
    <td class="text-primary">₹<?php echo number_format($total_refund,2); ?></td>
</tr>
</tfoot>

</table>
</div>

<?php else: ?>

<div class="alert alert-warning text-center">
    No students found.
</div>

<?php endif; ?>

</div>
</body>
</html>