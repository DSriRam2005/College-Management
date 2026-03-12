<?php
/* ================= DEBUG ================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================= SESSION & DB ================= */
session_start();
require_once 'db.php';

/* ================= ACCESS ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    die("ACCESS DENIED");
}

if (!isset($_SESSION['classid'])) {
    die("SESSION ERROR: classid missing");
}

$classid = $_SESSION['classid'];

/* ================= HANDLE UPDATE ================= */
if (isset($_POST['update_refund'])) {
    $htno  = $_POST['htno'];
    $name  = $_POST['account_holder_name'] ?: null;
    $accno = $_POST['account_number'] ?: null;
    $ifsc  = $_POST['ifsc_code'] ?: null;

    $up = $conn->prepare("
        UPDATE REFUND_BANK_ACCOUNTS
        SET account_holder_name=?, account_number=?, ifsc_code=?
        WHERE htno=?
    ");
    $up->bind_param("ssss", $name, $accno, $ifsc, $htno);
    $up->execute();

    header("Location: refund_acc.php");
    exit;
}

/* ================= FETCH EDIT DATA (IF EDIT CLICKED) ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $htno = $_GET['edit'];
    $stmt = $conn->prepare("
        SELECT htno, account_holder_name, account_number, ifsc_code
        FROM REFUND_BANK_ACCOUNTS
        WHERE htno=?
    ");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

/* ================= FETCH LIST (ONLY HTNO IN REFUND TABLE) ================= */
$sql = "
SELECT 
    s.htno,
    s.name,
    r.account_holder_name,
    r.account_number,
    r.ifsc_code
FROM STUDENTS s
INNER JOIN REFUND_BANK_ACCOUNTS r ON r.htno = s.htno
WHERE s.classid = ?
ORDER BY s.htno ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Refund Bank Accounts</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:20px; }
        table { width:100%; border-collapse:collapse; background:#fff; }
        th, td { border:1px solid #ccc; padding:8px; }
        th { background:#222; color:#fff; }
        tr:nth-child(even) { background:#f2f2f2; }
        .btn { padding:5px 10px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px; }
        .edit-box {
            background:#fff;
            padding:15px;
            margin-bottom:20px;
            border:1px solid #ccc;
            width:400px;
        }
        input { width:100%; padding:7px; margin-bottom:10px; }
        button { background:#28a745; color:#fff; border:none; padding:7px 12px; }
    </style>
</head>
<body>

<h2>Refund Bank Accounts – Class <?= htmlspecialchars($classid) ?></h2>

<?php if ($editData): ?>
<!-- ================= EDIT FORM ================= -->
<div class="edit-box">
    <h4>Edit Refund – <?= htmlspecialchars($editData['htno']) ?></h4>
    <form method="post">
        <input type="hidden" name="htno" value="<?= htmlspecialchars($editData['htno']) ?>">

        <label>Account Holder Name</label>
        <input type="text" name="account_holder_name"
               value="<?= htmlspecialchars($editData['account_holder_name'] ?? '') ?>">

        <label>Account Number</label>
        <input type="text" name="account_number"
               value="<?= htmlspecialchars($editData['account_number'] ?? '') ?>">

        <label>IFSC Code</label>
        <input type="text" name="ifsc_code"
               value="<?= htmlspecialchars($editData['ifsc_code'] ?? '') ?>">

        <button type="submit" name="update_refund">Update</button>
        <a href="refund_acc.php" class="btn" style="background:#6c757d;">Cancel</a>
    </form>
</div>
<?php endif; ?>

<!-- ================= LIST TABLE ================= -->
<table>
    <tr>
        <th>#</th>
        <th>HTNO</th>
        <th>Name</th>
        <th>Account Holder</th>
        <th>Account Number</th>
        <th>IFSC</th>
        <th>Edit</th>
    </tr>

<?php
$i = 1;
while ($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['htno']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['account_holder_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['account_number'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['ifsc_code'] ?? '') ?></td>
    <td>
        <a class="btn" href="refund_acc.php?edit=<?= urlencode($row['htno']) ?>">Edit</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
