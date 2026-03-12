<?php
session_start();
include 'db.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['empid']) || $_SESSION['role'] !== 'STPO') {
    die("ACCESS DENIED");
}

$empid = intval($_SESSION['empid']);

/* ================= FETCH STUDENTS ================= */
$sql = "
    SELECT 
        id,
        htno,
        name,
        prog,
        classid,
        year,
        branch,
        admission_type,
        PHASE,
        CLG_TYPE
    FROM STUDENTS
    WHERE REFEMPID = $empid
    ORDER BY id DESC
";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Admissions</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family:Inter,system-ui,sans-serif;
    background:#f8fafc;
    padding:20px;
    color:#0f172a;
}
h2{
    margin-bottom:16px;
}
table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}
th,td{
    padding:12px 10px;
    text-align:left;
    font-size:14px;
    border-bottom:1px solid #e5e7eb;
}
th{
    background:#0f172a;
    color:#fff;
    font-weight:600;
}
tr:hover{
    background:#f1f5f9;
}
.badge{
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
    font-weight:600;
}
.phase{background:#e0e7ff;color:#3730a3}
.type{background:#dcfce7;color:#166534}
.empty{
    padding:20px;
    background:#fff;
    border-radius:12px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}
</style>
</head>

<body>

<h2>My Admissions</h2>

<?php if(mysqli_num_rows($res) == 0): ?>
    <div class="empty">No admissions found for you.</div>
<?php else: ?>
<table>
<thead>
<tr>
    <th>#</th>
    <th>HT No</th>
    <th>Name</th>
    <th>Program</th>
    <th>Class</th>
    <th>Year</th>
    <th>Branch</th>
    <th>Admission Type</th>
    <th>Phase</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($row = mysqli_fetch_assoc($res)): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['htno']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['prog']) ?></td>
    <td><?= htmlspecialchars($row['classid']) ?></td>
    <td><?= htmlspecialchars($row['year']) ?></td>
    <td><?= htmlspecialchars($row['branch']) ?></td>
    <td><span class="badge type"><?= htmlspecialchars($row['admission_type']) ?></span></td>
    <td><span class="badge phase"><?= htmlspecialchars($row['PHASE']) ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
