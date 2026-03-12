<?php
session_start();
include 'db.php';

// ✅ Allow only CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$classid = $_SESSION['classid'] ?? null;
if (!$classid) {
    die("No classid assigned.");
}

if (!isset($_GET['htno'])) {
    die("Invalid request");
}
$htno = $_GET['htno'];
$today = date("Y-m-d");

// ✅ Check student belongs to CPTO’s classid
$chk = $conn->prepare("SELECT 1 FROM STUDENTS WHERE htno=? AND classid=?");
$chk->bind_param("ss", $htno, $classid);
$chk->execute();
$res = $chk->get_result();
if ($res->num_rows === 0) {
    die("Unauthorized.");
}

$sql = "SELECT remark, called_no, remark_date 
        FROM REMARKS
        WHERE htno=? AND remark_date < ?
        ORDER BY remark_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $htno, $today);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Remark History</title>
    <style>
        table { border-collapse: collapse; width: 70%; margin: 20px auto; }
        th, td { border: 1px solid #666; padding: 8px; text-align: center; }
        th { background: #eee; }
    </style>
</head>
<body>
<h2 style="text-align:center;">Remark History for <?= htmlspecialchars($htno) ?></h2>
<table>
<tr><th>Remark</th><th>Called No</th><th>Date</th></tr>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($row['remark']) ?></td>
  <td><?= htmlspecialchars($row['called_no']) ?></td>
  <td><?= htmlspecialchars($row['remark_date']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
