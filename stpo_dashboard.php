<?php
session_start();

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['empid']) || $_SESSION['role'] !== 'STPO') {
    header("Location: stpo_login.php");
    exit;
}

$name  = $_SESSION['name'];
$empid = $_SESSION['empid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>STPO Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box}

body{
    font-family:Inter,system-ui,sans-serif;
    background:#f8fafc;
    color:#0f172a;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    top:0;left:0;
    width:250px;
    height:100vh;
    background:linear-gradient(180deg,#0f172a,#020617);
    padding:24px 16px;
    transition:transform .3s ease;
    z-index:10;
}
.sidebar h2{
    text-align:center;
    margin-bottom:30px;
    color:#e5e7eb;
    font-size:18px;
}
.sidebar a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 16px;
    margin-bottom:8px;
    border-radius:10px;
    color:#cbd5e1;
    text-decoration:none;
    font-size:14px;
}
.sidebar a:hover{
    background:#1e293b;
    color:#fff;
}

/* MAIN */
.main{
    margin-left:250px;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* TOPBAR */
.topbar{
    height:64px;
    background:#fff;
    margin:16px;
    border-radius:14px;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
}

.menu-btn{
    display:none;
    font-size:22px;
    background:none;
    border:none;
    cursor:pointer;
}

/* IFRAME */
iframe{
    flex:1;
    margin:0 16px 16px;
    border:none;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,.06);
}

/* OVERLAY */
.overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.5);
    z-index:9;
}

/* MOBILE */
@media(max-width:768px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.active{transform:translateX(0)}
    .main{margin-left:0}
    .menu-btn{display:block}
    .overlay.active{display:block}
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h2>STPO PANEL</h2>
    <a href="stpo_home.php" target="contentFrame" onclick="closeMenu()">🏠 Dashboard</a>
     <a href="stpo_admissions.php" target="contentFrame" onclick="closeMenu()">✔️ Admissions</a>
    <a href="stpo_incentive_report.php" target="contentFrame" onclick="closeMenu()">✔️ Incentive</a>
    <a href="stpo_attendance_summary.php" target="contentFrame" onclick="closeMenu()">🗓️ Attendance Report</a>
    <a href="STPO_sem_dues.php" target="contentFrame" onclick="closeMenu()">📊 Sem Fee Report</a>
    <a href="stpo_team_report.php" target="contentFrame" onclick="closeMenu()">📊 Mess Fee Report</a>
    <a href="stpo_login.php">🚪 Logout</a>
</div>

<div class="overlay" id="overlay" onclick="closeMenu()"></div>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <span>Welcome, <strong><?= htmlspecialchars($name) ?></strong></span>
        <span>EMPID: <?= htmlspecialchars($empid) ?></span>
    </div>

    <iframe name="contentFrame" src="stpo_home.php"></iframe>
</div>

<script>
function toggleMenu(){
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

function closeMenu(){
    if (window.innerWidth <= 768){
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    }
}
</script>

</body>
</html>
