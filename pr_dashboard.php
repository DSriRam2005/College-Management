<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header('Location: index.php');
    exit();
}

$default_page = 'pr_home.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PR Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body, html {
    height: 100%;
    margin: 0;
    background:#f8fafc;
    font-family: Inter, Arial, sans-serif;
}

/* Layout */
.iframe-area {
    height: calc(100vh - 60px);
}
#pr_iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Navbar */
.navbar {
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,.08);
}

/* Menu layout */
.navbar-nav {
    gap: 6px;
}

/* Button design */
.btn-nav {
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 20px;
    border: 1px solid #d1d5db;
    background: #f1f5f9;
    color: #1f2937;
    transition: all .2s ease;
    white-space: nowrap;
}

.btn-nav:hover {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

.btn-nav.btn-success {
    background: #2563eb !important;
    border-color: #2563eb !important;
    color: #fff !important;
}

.btn-nav.btn-secondary {
    background: #f1f5f9 !important;
    color: #1f2937 !important;
    border-color: #d1d5db !important;
}

/* Dropdown */
.dropdown-menu {
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 8px 20px rgba(0,0,0,.08);
    font-size: 13px;
}

.dropdown-item {
    font-weight: 600;
    color: #1f2937;
}

.dropdown-item:hover {
    background: #2563eb;
    color: #fff;
}

/* Right side user info */
.user-info {
    font-size: 13px;
    color: #374151;
}
</style>
</head>

<body>

<!-- TOP BAR -->
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid">

    <a class="navbar-brand fw-bold" href="#">PR Dashboard</a>

    <!-- HAMBURGER -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNavMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 flex-wrap">

        <!-- HOME -->
        <li class="nav-item">
          <button class="btn-nav btn-success" onclick="loadPage('pr_home.php', this)">Home</button>
        </li>

        <!-- CLASS REPORTS DROPDOWN -->
        <li class="nav-item dropdown">
          <button class="btn-nav btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
            Class Reports
          </button>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item" href="#" onclick="loadPage('reports.php', this); return false;">
                Fee Report
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="loadPage('pr_mess.php', this); return false;">
                Mess Fee Report
              </a>
            </li>
              <li>
              <a class="dropdown-item" href="#" onclick="loadPage('prsemiresidential.php', this); return false;">
                Activities Report
              </a>
            </li>
          </ul>
        </li>

        <!-- STPO REPORTS DROPDOWN -->
        <li class="nav-item dropdown">
          <button class="btn-nav btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
            STPO Reports
          </button>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item" href="#" onclick="loadPage('stpo_sem_dues_report.php', this); return false;">
                STPO Fee Report
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#" onclick="loadPage('stpo_report.php', this); return false;">
                STPO Mess Report
              </a>
            </li>
          </ul>
        </li>

        <!-- OTHER LINKS -->
        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('team_reports.php', this)">Team</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('zonereports.php', this)">Zone</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('datewisepaymentreport.php', this)">Daywise</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('attendance_summary_pr.php', this)">Attendance</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('PR_TESTREPORT.php', this)">Tests</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('pr_mid_summary.php', this)">MID</button>
        </li>

        <li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('pr_companies.php', this)">Placements</button>
        </li>
<li class="nav-item">
          <button class="btn-nav btn-secondary" onclick="loadPage('adm_inc.php', this)">AdmissionsIncentives</button>
        </li>
      </ul>

      <div class="d-flex align-items-center">
        <span class="user-info me-3">
          Logged in as: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'PR') ?></strong>
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>

    </div>
  </div>
</nav>

<!-- CONTENT -->
<div class="iframe-area">
  <iframe id="pr_iframe" src="<?= htmlspecialchars($default_page) ?>"></iframe>
</div>

<script>
function loadPage(url, el) {
    document.getElementById('pr_iframe').src = url;

    // Reset all main nav buttons
    document.querySelectorAll('.btn-nav').forEach(btn => {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-secondary');
    });

    // Highlight active button
    if (el.classList.contains('dropdown-item')) {
        let parentBtn = el.closest('.dropdown').querySelector('.btn-nav');
        parentBtn.classList.remove('btn-secondary');
        parentBtn.classList.add('btn-success');
    } else {
        el.classList.remove('btn-secondary');
        el.classList.add('btn-success');
    }

    // AUTO CLOSE HAMBURGER MENU ON MOBILE
    const navbarCollapse = document.getElementById('topNavMenu');
    if (navbarCollapse.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse)
            || new bootstrap.Collapse(navbarCollapse);
        bsCollapse.hide();
    }
}

// Resize iframe dynamically
function resizeIframe(){
    const iframe = document.getElementById('pr_iframe');
    const navbar = document.querySelector('.navbar');
    iframe.style.height = (window.innerHeight - navbar.offsetHeight) + 'px';
}

window.addEventListener('resize', resizeIframe);
window.onload = resizeIframe;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
