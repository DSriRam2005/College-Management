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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['htno'], $_POST['remark'], $_POST['called_no'])) {
    $htno = $_POST['htno'];
    $remark = $_POST['remark'];
    $called_no = $_POST['called_no'];
    $today = date("Y-m-d");

    // ✅ Verify this student belongs to CPTO’s classid
    $chk = $conn->prepare("SELECT 1 FROM STUDENTS WHERE htno=? AND classid=?");
    $chk->bind_param("ss", $htno, $classid);
    $chk->execute();
    $res = $chk->get_result();
    if ($res->num_rows === 0) {
        die("Unauthorized student.");
    }

    // ✅ Save remark
    $sql = "INSERT INTO REMARKS (htno, remark, called_no, remark_date) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $htno, $remark, $called_no, $today);
    $stmt->execute();
}
header("Location: ctpo_remark.php");
exit();
