<?php
session_start();
include 'db.php';

// Allow only TPO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TPO') {
    header('Location: index.php');
    exit();
}

$default_page = 'TPO_home.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TPO Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body, html { height: 100%; margin: 0; }
    .iframe-area { height: calc(100vh - 56px); }
    #tpo_iframe { width: 100%; height: 100%; border: 0; }
    .btn-nav { margin-left: 5px; }
</style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="container-fluid">

    <a class="navbar-brand" href="#">TPO Dashboard</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">

      <ul class="navbar-nav me-auto mb-2 mb-md-0">

        <!-- Home -->
        <li class="nav-item">
          <button class="btn btn-success btn-sm btn-nav" onclick="loadPage('TPO_home.php', this)">Home</button>
        </li>

        <!-- Reports -->
        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('TPO_report.php', this)">Reports</button>
        </li>

        <!-- TEST MANAGEMENT DROPDOWN -->
        <li class="nav-item dropdown">
          <button class="btn btn-secondary btn-sm btn-nav dropdown-toggle" data-bs-toggle="dropdown">
            Test Management
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="loadPage('TPO_create_test.php', this)">Create Test</a></li>
            <li><a class="dropdown-item" href="#" onclick="loadPage('startbuttoon.php', this)">Start Test</a></li>
            <li><a class="dropdown-item" href="#" onclick="loadPage('upload_answers.php', this)">Upload Test Key</a></li>
            <li><a class="dropdown-item" href="#" onclick="loadPage('upload_student_marks.php', this)">Upload Test Marks</a></li>
          </ul>
        </li>

        <!-- ENTER MARKS -->
        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('TPO_enter_marks.php', this)">Enter Test Marks</button>
        </li>

        <!-- RANKING -->
        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('TPO_ranking.php', this)">Test Ranking</button>
        </li>

        <!-- TEST AVERAGE -->
        <li class="nav-item">
          <button class="btn btn-secondary btn-sm btn-nav" onclick="loadPage('TPO_consolidated_report.php', this)">Test Avg</button>
        </li>

        <!-- PLACEMENTS DROPDOWN -->
        <li class="nav-item dropdown">
          <button class="btn btn-secondary btn-sm btn-nav dropdown-toggle" data-bs-toggle="dropdown">
            Placements
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="loadPage('company_module.php', this)">Enter Company</a></li>
            <li><a class="dropdown-item" href="#" onclick="loadPage('tpo_add_placement.php', this)">Placement Details</a></li>
            <li><a class="dropdown-item" href="#" onclick="loadPage('view_placements.php', this)">Placed Details</a></li>
          </ul>
        </li>

      </ul>

      <!-- Right Section -->
      <div class="d-flex">
        <span class="me-3">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>

    </div>
  </div>
</nav>

<!-- IFRAME AREA -->
<div class="iframe-area">
  <iframe id="tpo_iframe" src="<?= htmlspecialchars($default_page) ?>" title="TPO Content"></iframe>
</div>

<script>
function loadPage(url, el) {
    document.getElementById("tpo_iframe").src = url;

    document.querySelectorAll('.btn-nav').forEach(btn => {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-secondary');
    });

    if (el.tagName === "A") el = el.closest("li")?.parentElement?.previousElementSibling;
    if (el && el.classList.contains('btn-nav')) {
        el.classList.remove('btn-secondary');
        el.classList.add('btn-success');
    }
}

// Adjust iframe dynamically
window.addEventListener("resize", () => {
    document.getElementById("tpo_iframe").style.height =
        (window.innerHeight - document.querySelector(".navbar").offsetHeight) + "px";
});
window.dispatchEvent(new Event("resize"));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
