<?php
// calendar_logout.php
session_start();
$_SESSION = [];
session_destroy();
header('Location: calender_login.php');
exit;
