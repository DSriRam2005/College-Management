<?php
session_start();
include 'db.php';

// ================= ACCESS =================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

$htpo_username = $_SESSION['username'];

/* ---------------- AJAX PROFILE UPDATE ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $title  = trim($_POST['title'] ?? '');
    $name   = trim($_POST['name'] ?? '');
    $ph_no  = trim($_POST['ph_no'] ?? '');
    $emp_id = trim($_POST['emp_id'] ?? '');

    if ($title && $name && $ph_no && $emp_id) {
        $full_name = $title . ' ' . $name;
        $stmt = $conn->prepare("
            UPDATE USERS 
            SET name=?, ph_no=?, EMP_ID=? 
            WHERE username=?
        ");
        $stmt->bind_param("ssss", $full_name, $ph_no, $emp_id, $htpo_username);
        $stmt->execute();
        echo "success";
    } else {
        echo "error";
    }
    exit();
}

/* ---------------- USER INFO ---------------- */
$stmt = $conn->prepare("
    SELECT name, ph_no, EMP_ID, year 
    FROM USERS 
    WHERE username=?
");
$stmt->bind_param("s", $htpo_username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$existing_title = '';
$existing_name  = '';
if (!empty($user['name'])) {
    $parts = explode(' ', $user['name'], 2);
    if (in_array($parts[0], ['Mr','Miss','Ms','Mrs'])) {
        $existing_title = $parts[0];
        $existing_name  = $parts[1] ?? '';
    } else {
        $existing_name = $user['name'];
    }
}

$show_popup = empty($user['name']) || empty($user['ph_no']) || empty($user['EMP_ID']);
$user_year = $user['year'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HTPO Dashboard</title>

<style>
body{margin:0;font-family:Segoe UI;background:#f4f6f7;overflow-x:hidden}
.topbar{background:#2c3e50;color:#fff;padding:12px 16px;display:flex;align-items:center;position:fixed;width:100%;top:0;z-index:1000}
.topbar h1{margin-left:10px;font-size:20px}
.hamburger{font-size:26px;cursor:pointer;display:none}
.sidebar{width:220px;background:#34495e;height:100vh;position:fixed;top:0;left:0;padding-top:60px;color:#fff;transition:.3s;overflow-y:auto}
.sidebar h2{text-align:center;margin-bottom:10px}
.sidebar a{display:block;color:#ecf0f1;padding:12px 16px;text-decoration:none}
.sidebar a:hover{background:#1abc9c;padding-left:22px}
.content{margin-left:220px;padding-top:60px}
iframe{width:100%;height:calc(100vh - 60px);border:none;background:#fff}
.logout{background:#e74c3c;margin-top:20px}
.logout a{color:#fff;text-align:center}

/* ===== DROPDOWNS ===== */
.menu-section{margin-top:5px}
.menu-title{
    padding:12px 16px;
    cursor:pointer;
    font-weight:bold;
    background:#2f4050;
}
.menu-title:hover{background:#1abc9c}
.submenu{display:none;background:#3b5166}
.submenu a{padding-left:32px;font-size:14px}

/* ===== POPUP ===== */
#profileModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:2000;justify-content:center;align-items:center}
.modal-content{background:#fff;padding:25px;border-radius:10px;width:90%;max-width:420px;text-align:center}
.modal-content input,.modal-content select{width:100%;padding:10px;margin-bottom:12px}
.modal-content button{width:100%;padding:12px;background:#2c3e50;color:#fff;border:none}

@media(max-width:768px){
.hamburger{display:block}
.sidebar{transform:translateX(-100%)}
.sidebar.active{transform:translateX(0)}
.content{margin-left:0}
}
</style>
</head>

<body>

<!-- ================= PROFILE POPUP ================= -->
<div id="profileModal">
<div class="modal-content">
<h3>Complete Your Profile</h3>

<select id="profile_title">
<option value="">Select Title</option>
<option value="Mr" <?= $existing_title=='Mr'?'selected':'' ?>>Mr</option>
<option value="Miss" <?= $existing_title=='Miss'?'selected':'' ?>>Miss</option>
<option value="Mrs" <?= $existing_title=='Mrs'?'selected':'' ?>>Mrs</option>
</select>

<input id="profile_name" placeholder="Full Name" value="<?= htmlspecialchars($existing_name) ?>">
<input id="profile_empid" placeholder="Employee ID" value="<?= htmlspecialchars($user['EMP_ID']) ?>">
<input id="profile_ph" placeholder="Phone Number" value="<?= htmlspecialchars($user['ph_no']) ?>">

<button onclick="saveProfile()">Save & Continue</button>
</div>
</div>

<!-- ================= TOPBAR ================= -->
<div class="topbar">
<span class="hamburger" onclick="toggleSidebar()">☰</span>
<h1>HTPO Dashboard</h1>
</div>

<!-- ================= SIDEBAR ================= -->
<div class="sidebar" id="sidebar">
<h2>HTPO Panel</h2>

<a href="welcome_htpo.php" target="main_frame">🏠 Home</a>

<div class="menu-section">
<div class="menu-title" onclick="toggleMenu(this)">🗓 Attendance</div>
<div class="submenu">
<a href="attendance_htpo.php" target="main_frame">Attendance Report</a>
</div>
</div>

<div class="menu-section">
<div class="menu-title" onclick="toggleMenu(this)">👥 Students & Staff</div>
<div class="submenu">
<a href="htpo_students.php" target="main_frame">My Students</a>
<a href="subjects_htpo.php" target="main_frame">My Staff</a>
</div>
</div>

<div class="menu-section">
<div class="menu-title" onclick="toggleMenu(this)">💰 Fee</div>
<div class="submenu">
    <a href="htpo_reports.php" target="main_frame">Upto Sem Reports</a>
<a href="htpo_consolidated_reports.php" target="main_frame">Consolidated Reports</a>
<a href="htpo_messfee_summary.php" target="main_frame">Mess Fee Summary</a>
</div>
</div>

<div class="menu-section">
<div class="menu-title" onclick="toggleMenu(this)">📑 Tests</div>
<div class="submenu">
<a href="HTPO_test.php" target="main_frame">Tests</a>
<a href="htpo_class_test_report.php" target="main_frame">Test Reports</a>
</div>
</div>
<?php if ($user_year == 25): ?>
<div class="menu-section">
<div class="menu-title" onclick="toggleMenu(this)">📚 Academics</div>
<div class="submenu">


<a href="HTPO_mid.php" target="main_frame">MID</a>
<a href="htpo_mid_report.php" target="main_frame">MID Reports</a>

</div>
</div>
<?php endif; ?>
<div class="logout"><a href="logout.php">🚪 Logout</a></div>
</div>

<!-- ================= CONTENT ================= -->
<div class="content">
<iframe name="main_frame" src="welcome_htpo.php"></iframe>
</div>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('active');
}

function closeSidebarIfMobile(){
    if(window.innerWidth <= 768){
        document.getElementById('sidebar').classList.remove('active');
    }
}

function toggleMenu(el){
    const sub = el.nextElementSibling;

    document.querySelectorAll('.submenu').forEach(s=>{
        if(s !== sub) s.style.display = 'none';
    });

    sub.style.display = (sub.style.display === 'block') ? 'none' : 'block';
}

/* ✅ AUTO CLOSE SIDEBAR ON LINK CLICK */
document.querySelectorAll('.sidebar a').forEach(link=>{
    link.addEventListener('click', ()=>{
        closeSidebarIfMobile();
    });
});

/* ✅ SHOW PROFILE POPUP IF REQUIRED */
<?php if($show_popup): ?>
document.getElementById('profileModal').style.display='flex';
<?php endif; ?>

function saveProfile(){
    let t = profile_title.value,
        n = profile_name.value,
        e = profile_empid.value,
        p = profile_ph.value;

    if(!t || !n || !e || !/^\d{10}$/.test(p)){
        alert('Invalid input');
        return;
    }

    fetch('',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`ajax_update=1&title=${t}&name=${n}&emp_id=${e}&ph_no=${p}`
    })
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()==='success'){
            alert('Saved');
            location.reload();
        } else {
            alert('Error');
        }
    });
}
</script>


</body>
</html>
