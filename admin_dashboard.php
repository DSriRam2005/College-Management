<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

/* 🔴 Pending CTPO Queries Count */
$pending_ctpo_count = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM ctpo_queries WHERE admin_reply IS NULL");
if ($res) {
    $pending_ctpo_count = (int)$res->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<style>
/* ================= GLOBAL ================= */
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: "Inter","Segoe UI",system-ui,sans-serif;
    background: #f1f5f9;
    color: #111827;
}

/* ================= TOPBAR ================= */
.topbar {
    position: fixed;
    top: 0;
    left: 0;
    height: 56px;
    width: 100%;
    background: #ffffff;
    color: #111827;
    display: flex;
    align-items: center;
    padding: 0 16px;
    border-bottom: 1px solid #e5e7eb;
    z-index: 1000;
}

.topbar h1 {
    font-size: 18px;
    margin-left: 12px;
    font-weight: 600;
}

.hamburger {
    font-size: 22px;
    cursor: pointer;
    display: none;
    color: #111827;
}

/* ===== TOPBAR RIGHT BUTTON (ADDED) ===== */
.topbar-right {
    margin-left: auto;
}

.ctpo-btn {
    position: relative;
    padding: 8px 14px;
    background: #2563eb;
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 6px;
}

.ctpo-btn:hover {
    background: #1d4ed8;
}

.ctpo-btn .badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ================= SIDEBAR ================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    height: 100vh;
    background: #ffffff;
    padding-top: 70px;
    overflow-y: auto;
    border-right: 1px solid #e5e7eb;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;
}

/* Dashboard link */
.sidebar a.dashboard-link {
    padding: 14px 18px;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    text-decoration: none;
    border-left: 4px solid #3b82f6;
    background: #eff6ff;
}

.sidebar a.dashboard-link:hover {
    background: #dbeafe;
}

/* Menu groups */
.menu-group {
    border-bottom: 1px solid #f1f5f9;
}

.menu-title {
    padding: 12px 18px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-title:hover {
    background: #f3f4f6;
}

.menu-title span {
    font-size: 12px;
    transition: transform 0.2s ease;
}

.menu-group.active .menu-title span {
    transform: rotate(180deg);
}

/* Dropdown items */
.menu-items {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.menu-group.active .menu-items {
    max-height: 400px;
}

.menu-items a {
    display: block;
    padding: 10px 32px;
    font-size: 14px;
    color: #374151;
    text-decoration: none;
}

.menu-items a:hover {
    background: #e5e7eb;
    color: #111827;
}

/* Logout */
.logout-box {
    margin-top: auto;
    padding: 12px;
}

.logout-box a {
    display: block;
    text-align: center;
    padding: 12px;
    font-weight: 600;
    color: #ffffff;
    background: #ef4444;
    text-decoration: none;
    border-radius: 6px;
}

.logout-box a:hover {
    background: #dc2626;
}

/* ================= CONTENT ================= */
.content {
    margin-left: 240px;
    padding-top: 56px;
    height: calc(100vh - 56px);
    background: #f9fafb;
}

iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #ffffff;
}

/* ================= MOBILE ================= */
@media (max-width: 768px) {
    .hamburger { display: block; }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .content {
        margin-left: 0;
    }
}
</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <span class="hamburger" onclick="toggleSidebar()">☰</span>
    <h1>Admin Dashboard</h1>

    <!-- 🔴 CTPO QUERY BUTTON (ADDED) -->
    <div class="topbar-right">
        <a href="admin_ctpo_queries.php" target="content_frame" class="ctpo-btn">
            Fee Queries
            <?php if ($pending_ctpo_count > 0): ?>
                <span class="badge"><?= $pending_ctpo_count ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">

    <a href="admin_home.php" target="content_frame" class="dashboard-link">
        Dashboard
    </a>

    <div class="menu-group">
        <div class="menu-title">Payments History <span>▾</span></div>
        <div class="menu-items">
            <a href="pay_history.php" target="content_frame">Payment History</a>
            <a href="payment_his.php" target="content_frame">Paid History</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-title">Fee Management <span>▾</span></div>
        <div class="menu-items">
            <a href="admin_ctpo_queries.php" target="content_frame">Fee Queries</a>
            <a href="feeedit.php" target="content_frame">Amount Edit</a>
            <a href="bulkfeeupdatecsv.php" target="content_frame">Update Fee Upload</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-title">Mess Uploads <span>▾</span></div>
        <div class="menu-items">
            <a href="newmess.php" target="content_frame">Add Mess Fee</a>
            <a href="messfeeupload.php" target="content_frame">Upload Mess Fee</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-title">Students <span>▾</span></div>
        <div class="menu-items">
            <a href="upload_students.php" target="content_frame">Upload Students</a>
            <a href="view_students.php" target="content_frame">View Students</a>
        </div>
    </div>

    <div class="menu-group">
        <div class="menu-title">Attendance <span>▾</span></div>
        <div class="menu-items">
            <a href="attendance_time_admin.php" target="content_frame">Attendance Time</a>
        </div>
    </div>

    <div class="logout-box">
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- CONTENT -->
<div class="content">
    <iframe name="content_frame" src="admin_home.php"></iframe>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

/* ================= DROPDOWN TOGGLE ================= */
document.querySelectorAll('.menu-title').forEach(title => {
    title.addEventListener('click', () => {

        // Close other open menus
        document.querySelectorAll('.menu-group').forEach(g => {
            if (g !== title.parentElement) {
                g.classList.remove('active');
            }
        });

        // Toggle current
        title.parentElement.classList.toggle('active');
    });
});

/* ================= AUTO CLOSE ON LINK CLICK ================= */
document.querySelectorAll('.menu-items a, .dashboard-link').forEach(link => {
    link.addEventListener('click', () => {

        // ✅ CLOSE ALL DROPDOWNS
        document.querySelectorAll('.menu-group').forEach(g => {
            g.classList.remove('active');
        });

        // ✅ CLOSE SIDEBAR ON MOBILE
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.remove('active');
        }
    });
});
</script>

</body>
</html>
