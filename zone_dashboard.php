<?php
session_start();
include 'db.php';

// ✅ Allow only ZONE role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ZONE') {
    header('Location: index.php');
    exit();
}

// ✅ Default iframe page
$default_page = 'zone_home.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ZONE Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body, html { height: 100%; margin: 0; }
    .iframe-area { height: calc(100vh - 56px); }
    #zone_iframe { width: 100%; height: 100%; border: 0; }
    .btn-nav { margin-left: 5px; }
  </style>
</head>
<body>

<!-- 🔝 Top Navbar -->
<nav class="navbar navbar-expand-md navbar-light bg-light shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-primary" href="#">ZONE Dashboard</a>

    <!-- Hamburger menu for small screens -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavMenu" aria-controls="topNavMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Collapsible Navigation Menu -->
    <div class="collapse navbar-collapse" id="topNavMenu">
      <ul class="navbar-nav me-auto mb-2 mb-md-0">

        <li class="nav-item">
          <button class="btn btn-success btn-sm btn-nav" onclick="loadPage('zone_home.php', this);">Home</button>
        </li>

        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('zone_attendance.php', this);">Attendance</button>
        </li>

        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('zone_dues.php', this);">Fee Dues</button>
        </li>
                  <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('overall_test_report.php', this);">Test Report</button>
        </li>
      </ul>

      <!-- 👤 User Info + Logout -->
      <div class="d-flex align-items-center">
        <span class="me-3">Logged in as: 
          <strong class="text-success">
            <?= htmlspecialchars($_SESSION['username'] ?? 'ZONE'); ?>
          </strong>
        </span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>
  </div>
</nav>

<!-- 🧭 Iframe Section -->
<div class="iframe-area">
  <iframe id="zone_iframe" src="<?= htmlspecialchars($default_page); ?>" title="Zone Dashboard Content"></iframe>
</div>

<!-- 🔧 Scripts -->
<script>
function loadPage(url, el) {
  document.getElementById('zone_iframe').src = url;
  document.querySelectorAll('.btn-nav').forEach(btn => {
    btn.classList.remove('btn-success');
    btn.classList.add('btn-secondary');
  });
  if (el) {
    el.classList.remove('btn-secondary');
    el.classList.add('btn-success');
  }
}

// Auto adjust iframe height when window resizes
window.addEventListener('resize', function() {
  var iframe = document.getElementById('zone_iframe');
  if (iframe) {
    iframe.style.height = (window.innerHeight - document.querySelector('.navbar').offsetHeight) + 'px';
  }
});
window.dispatchEvent(new Event('resize'));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
