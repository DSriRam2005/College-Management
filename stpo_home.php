<?php
session_start();
require_once "db.php";

ini_set('display_errors',1);
error_reporting(E_ALL);

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['empid']) || $_SESSION['role'] !== 'STPO') {
    die("ACCESS DENIED");
}

$empid = (string)$_SESSION['empid']; // IMPORTANT: stpo is VARCHAR
$name  = $_SESSION['name'];

/* ================= TOTAL TEAMS ================= */
$teams = 0;
$students = 0;

/* Count distinct teams assigned to this STPO */
$q1 = $conn->prepare("
    SELECT COUNT(DISTINCT teamid) AS total_teams
    FROM STUDENTS
    WHERE stpo = ?
      AND teamid IS NOT NULL
      AND teamid != ''
");
$q1->bind_param("s", $empid);
$q1->execute();
$r1 = $q1->get_result()->fetch_assoc();
$teams = (int)($r1['total_teams'] ?? 0);
$q1->close();

/* Count total students under this STPO */
$q2 = $conn->prepare("
    SELECT COUNT(*) AS total_students
    FROM STUDENTS
    WHERE stpo = ?
");
$q2->bind_param("s", $empid);
$q2->execute();
$r2 = $q2->get_result()->fetch_assoc();
$students = (int)($r2['total_students'] ?? 0);
$q2->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>STPO Home</title>

<style>
body{
    margin:0;
    padding:20px;
    font-family: Arial, sans-serif;
    background:#f8fafc;
    color:#111827;
}
.header{
    margin-bottom:25px;
}
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:20px;
}
.card{
    background:#fff;
    padding:22px;
    border-radius:14px;
    box-shadow:0 8px 20px rgba(0,0,0,.08);
}
.card h2{
    margin:0;
    font-size:36px;
    color:#2563eb;
}
.card p{
    margin-top:6px;
    color:#6b7280;
    font-size:15px;
}
</style>
</head>

<body>

<div class="header">
    <h2>Welcome, <?= htmlspecialchars($name) ?> 👋</h2>
    <p>STPO Dashboard Overview</p>
</div>

<div class="cards">

    <div class="card">
        <h2><?= $teams ?></h2>
        <p>Total Teams Assigned</p>
    </div>

    <div class="card">
        <h2><?= $students ?></h2>
        <p>Total Students Under Teams</p>
    </div>

</div>

</body>
</html>
