<?php
session_start();
require_once "db.php";

/* SHOW ERRORS */
ini_set("display_errors", 1);
error_reporting(E_ALL);

/* ROLE CHECK */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN', 'CALENDAR'])) {
    header("Location: calender_login.php");
    exit;
}

/* VALIDATE ID */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Invalid Event ID</h3>");
}

/* CHECK EVENT EXISTS */
$chk = $conn->prepare("SELECT id FROM CLASS_CALENDAR WHERE id = ?");
$chk->bind_param("i", $id);
$chk->execute();
$res = $chk->get_result();

if ($res->num_rows == 0) {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Event Not Found</h3>");
}

/* DELETE EVENT */
$del = $conn->prepare("DELETE FROM CLASS_CALENDAR WHERE id = ?");
$del->bind_param("i", $id);

if ($del->execute()) {
    header("Location: calendar_view.php?msg=deleted");
    exit;
} else {
    die("<h3 style='color:red;text-align:center;margin-top:40px;'>Delete Failed: " . $conn->error . "</h3>");
}
?>
