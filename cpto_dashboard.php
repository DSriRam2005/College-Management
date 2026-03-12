<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

/* ===== AUTH ===== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

/* ===== USER INFO ===== */
$stmt = $conn->prepare("
    SELECT EMP_ID, name, ph_no, htno, spoc_name, spoc_no, year, classid, prog
    FROM USERS WHERE username = ?
");


$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$userProg = strtoupper(trim($user['prog'] ?? ''));

$userYear = $user['year'] ?? null;
$classid  = strtoupper(trim($user['classid'] ?? ''));

$_SESSION['classid'] = $classid;

/* ===== PG CHECK ===== */
$isPG = (
    str_contains($classid, 'MBA') ||
    str_contains($classid, 'MCA') ||
    str_contains($classid, 'MTECH')
);

/* ===== PROFILE POPUP CHECK ===== */
$show_popup = in_array($userYear, [22,23,24,25]) && (
    empty($user['EMP_ID']) ||
    empty($user['name']) ||
    empty($user['ph_no']) ||
    (!$isPG && empty($user['htno'])) ||
    empty($user['spoc_name']) ||
    empty($user['spoc_no'])
);

/* ===== AJAX PROFILE UPDATE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {

    $emp_id    = trim($_POST['emp_id']);
    $name      = trim($_POST['name']);
    $ph_no     = trim($_POST['ph_no']);
    $htno      = $isPG ? null : trim($_POST['htno'] ?? '');
    $spoc_name = trim($_POST['spoc_name']);
    $spoc_no   = trim($_POST['spoc_no']);

    if ($emp_id && $name && $ph_no && $spoc_name && $spoc_no && ($isPG || $htno)) {
        $stmt = $conn->prepare("
            UPDATE USERS 
            SET EMP_ID=?, name=?, ph_no=?, htno=?, spoc_name=?, spoc_no=? 
            WHERE username=?
        ");
        $stmt->bind_param("sssssss",
            $emp_id, $name, $ph_no, $htno, $spoc_name, $spoc_no, $username
        );
        $stmt->execute();
        echo "success";
    } else {
        echo "error";
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CTPO Dashboard</title>

<style>
body{
    margin:0;
    font-family:Segoe UI, sans-serif;
    background:#f4f6f7;
}

/* ===== TOPBAR ===== */
.topbar{
    position:fixed;
    top:0;
    left:0;
    right:0;
    height:60px;
    background:#2c3e50;
    color:#fff;
    display:flex;
    align-items:center;
    padding:0 16px;
    z-index:1000;
}
.hamburger{
    font-size:28px;
    cursor:pointer;
    display:none;
}
.topbar h1{
    font-size:20px;
    margin-left:10px;
}

/* ===== SIDEBAR ===== */
.sidebar{
    position:fixed;
    top:60px;
    left:0;
    width:220px;
    height:calc(100vh - 60px);
    background:#34495e;
    color:#fff;
    overflow-y:auto;
    transition:left .3s;
    z-index:999;
}
.sidebar h2{
    text-align:center;
}
.sidebar a{
    display:block;
    padding:12px 16px;
    color:#ecf0f1;
    text-decoration:none;
}
.sidebar a:hover{
    background:#1abc9c;
}

/* ===== DROPDOWN ===== */
.dropbtn{
    width:100%;
    background:#2c3e50;
    border:none;
    color:#fff;
    padding:12px 16px;
    text-align:left;
    cursor:pointer;
}
.dropdown-content{
    display:none;
    background:#3d566e;
}
.dropdown-content a{
    padding:10px 30px;
}

/* ===== CONTENT ===== */
.content{
    margin-left:220px;
    padding-top:60px;
}
iframe{
    width:100%;
    height:calc(100vh - 60px);
    border:none;
}

/* ===== POPUP ===== */
#profilePopup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    display:none;
    z-index:2000;
}
#popupBox{
    background:#fff;
    max-width:420px;
    width:90%;
    margin:10% auto;
    padding:20px;
    border-radius:6px;
}
#popupBox input{
    width:100%;
    padding:8px;
    margin:6px 0;
}
#popupBox button{
    width:100%;
    padding:10px;
    background:#1abc9c;
    border:none;
    color:#fff;
    cursor:pointer;
}

/* ===== MOBILE ===== */
@media(max-width:768px){

    .hamburger{
        display:block;
    }

    .sidebar{
        left:-240px;
        width:240px;
    }

    .sidebar.active{
        left:0;
    }

    .content{
        margin-left:0;
    }

    iframe{
        height:calc(100vh - 60px);
    }

    #overlay.active{
        display:block;
    }

    /* Bigger tap areas */
    .sidebar a,
    .dropbtn{
        font-size:16px;
        padding:14px 18px;
    }

    /* Popup mobile */
    #popupBox{
        margin:20% auto;
    }
}

    /* ===== MOBILE OVERLAY ===== */
#overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    display:none;
    z-index:998;
}

/* Sidebar animation improvement */
.sidebar{
    transition: left .3s ease;
}
    
    @media (max-width:768px){

    .hamburger{
        display:block;
    }

    .sidebar{
        left:-240px;
        width:240px;
    }

    .sidebar.active{
        left:0;
    }

    .content{
        margin-left:0;
    }

    iframe{
        height:calc(100vh - 60px);
    }

    #overlay.active{
        display:block;
    }

    .sidebar a,
    .dropbtn{
        font-size:16px;
        padding:14px 18px;
    }

    #popupBox{
        margin:20% auto;
    }
}


</style>
</head>

<body>
    <div id="overlay"></div>	


<!-- TOPBAR -->
<div class="topbar">
    <span class="hamburger" onclick="toggleSidebar()">☰</span>
    <h1>CTPO Dashboard</h1>
</div>

<!-- PROFILE POPUP -->
<div id="profilePopup">
<div id="popupBox">
<h3>Complete Your Profile</h3>
<input id="emp_id" placeholder="EMP ID">
<input id="name" placeholder="Name">
<input id="ph_no" placeholder="Phone No">
<?php if(!$isPG): ?><input id="htno" placeholder="HTNO"><?php endif; ?>
<input id="spoc_name" placeholder="SPOC Name">
<input id="spoc_no" placeholder="SPOC Phone">
<button onclick="saveProfile()">Save</button>
</div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
<h2>CTPO Panel</h2>

<a href="welcome_cpto.php" target="main_frame">🏠 Home</a>
    <a href="ctpo_profile.php" target="main_frame">👤 My Profile</a>

<!-- ATTENDANCE -->
<div class="dropdown">
    <button class="dropbtn">📅 Attendance ▼</button>
    <div class="dropdown-content">
        <?php if (in_array($userYear, [25, 24, 23, 22])): ?>
            <a href="cpto_mark_attendance.php" target="main_frame">🗓 Mark Attendance</a>
            <a href="attendance_classwise.php?classid=<?= urlencode($classid ?: ($_SESSION['classid'] ?? '')) ?>" 
                target="main_frame">📊 Attendance Report</a>
        	<a href="cpto_attendance.php" target="main_frame">🗓️ Monthy Report</a>

 <?php if (
    ($userProg === 'DIP' && (int)$userYear === 25) ||
    ($userProg === 'B.TECH' && in_array((int)$userYear, [25,24,23]))
): ?>
            <a href="ctpo_report.php" target="main_frame">🗓️ Student Activity Selection</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- STUDENTS -->
<!-- STUDENTS -->
<div class="dropdown">
    <button class="dropbtn">🎓 Students ▼</button>
    <div class="dropdown-content">
        <a href="ctpo_student_details.php" target="main_frame">🧑‍🎓 Student Details</a>
        <a href="ctpo_students.php" target="main_frame">👥 Team Edit</a>
        <a href="ctpo_assign_stpo.php" target="main_frame">👥 STPO Edit</a>

       
    </div>
</div>

<!-- CLASSWORK -->
<div class="dropdown">
    <button class="dropbtn">🎓 Classwork ▼</button>
    <div class="dropdown-content">
        <a href="timetable_cpto.php" target="main_frame">
    <i class="fa-solid fa-calendar-days"></i> Time Table
</a>

<a href="periodupdate.php" target="main_frame">
    <i class="fa-solid fa-clock-rotate-left"></i> Period Update
</a>

    </div>
</div>
<!-- DUES -->
<div class="dropdown">
    <button class="dropbtn">💰 Dues ▼</button>
    <div class="dropdown-content">
        <a href="rtf.php" target="main_frame">✅ Verify Fee</a>
        <a href="view_sem_dues.php" target="main_frame">📘 Dues Upto Sem</a>
        <a href="cpto_messdue.php" target="main_frame">🍽 Mess Dues</a>
        <a href="ctpo_update_due.php" target="main_frame">✏ Fee Update</a>
    </div>
</div>

<!-- TEST -->
<div class="dropdown">
    <button class="dropbtn">📝 Tests ▼</button>
    <div class="dropdown-content">
        <a href="CTPO_test_marks.php" target="main_frame">📑 Test</a>
        <a href="cpto_test_report.php" target="main_frame">📑 Test Report</a>
    </div>
</div>
 <!-- ACADEMICS-->
    <?php if (in_array($userYear, [25])): ?>
    <div class="dropdown">
        <button class="dropbtn">	Academics ▼</button>
        <div class="dropdown-content">
            <a href="ctpo_mid.php" target="main_frame">📑 MID Marks</a>
            <a href="mid_upload.php" target="main_frame">📑 MID Upload</a>
            <a href="ctpo_teamwise_mid.php " target="main_frame">📑 MID Team-Wise Report</a>
        </div>
</div>
    <?php endif; ?>
    

<div class="logout">
    <a href="logout.php">🚪 Logout</a>
</div>

</div>

<!-- CONTENT -->
<div class="content">
    <iframe name="main_frame" src="welcome_cpto.php"></iframe>
</div>


<script>
<?php if($show_popup): ?>
document.getElementById('profilePopup').style.display='block';
<?php endif; ?>



function saveProfile(){
    let fd=new FormData();
    fd.append('ajax_update',1);
    ['emp_id','name','ph_no','spoc_name','spoc_no','htno'].forEach(id=>{
        let el=document.getElementById(id);
        if(el) fd.append(id,el.value);
    });
    fetch('',{method:'POST',body:fd})
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()==='success') location.reload();
        else alert('Fill all fields');
    });
}

function toggleSidebar(){
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

function closeSidebar(){
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}

/* Click overlay to close */
document.getElementById('overlay').onclick = closeSidebar;

/* Dropdown toggle */
document.querySelectorAll(".dropbtn").forEach(btn => {
    btn.onclick = (e) => {
        e.stopPropagation();

        // Close all other dropdowns
        document.querySelectorAll(".dropdown-content").forEach(d => {
            if (d !== btn.nextElementSibling) {
                d.style.display = "none";
            }
        });

        // Toggle current dropdown
        let menu = btn.nextElementSibling;
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    };
});

/* Auto close sidebar when clicking any menu link (mobile) */
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        closeSidebar();
    });
});

    </script>

</body>
</html>
