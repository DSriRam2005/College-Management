<?php
session_start();
include 'db.php';

// ✅ Restrict to ZONE role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ZONE') {
    header('Location: index.php');
    exit();
}

// ✅ Fetch zone from session (case-insensitive)
$zone = $_SESSION['ZONE'] ?? $_SESSION['zone'] ?? '';

// ✅ Double-check zone from USERS if missing
if (!$zone && isset($_SESSION['username'])) {
    $check = $conn->prepare("SELECT ZONE FROM USERS WHERE username = ?");
    $check->bind_param("s", $_SESSION['username']);
    $check->execute();
    $zoneResult = $check->get_result()->fetch_assoc();
    $zone = $zoneResult['ZONE'] ?? '';
}

if (!$zone) {
    echo "<div class='alert alert-danger text-center m-3'>
            ❌ Zone not set for this user.<br>
            Please verify the USERS.ZONE column for this username.
          </div>";
    echo "<pre>Debug Session:\n";
    print_r($_SESSION);
    echo "</pre>";
    exit();
}

// ✅ Fetch data from STUDENTS table based on zone
$sql_students = "SELECT COUNT(*) AS total_students FROM STUDENTS WHERE ZONE = ?";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("s", $zone);
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total_students'] ?? 0;

// ✅ Distinct classes in same zone
$sql_classes = "SELECT COUNT(DISTINCT classid) AS total_classes FROM STUDENTS WHERE ZONE = ?";
$stmt2 = $conn->prepare($sql_classes);
$stmt2->bind_param("s", $zone);
$stmt2->execute();
$res2 = $stmt2->get_result();
$total_classes = $res2->fetch_assoc()['total_classes'] ?? 0;

// ✅ Distinct teams in same zone
$sql_teams = "SELECT COUNT(DISTINCT teamid) AS total_teams FROM STUDENTS WHERE ZONE = ?";
$stmt3 = $conn->prepare($sql_teams);
$stmt3->bind_param("s", $zone);
$stmt3->execute();
$res3 = $stmt3->get_result();
$total_teams = $res3->fetch_assoc()['total_teams'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Zone Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .dashboard-card {
      border-radius: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: transform 0.2s ease-in-out;
    }
    .dashboard-card:hover { transform: scale(1.03); }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="text-center mb-4">📍 Zone Dashboard – <?= htmlspecialchars($zone) ?></h2>

  <div class="row justify-content-center g-4">

    <!-- 🧑‍🎓 Total Students -->
    <div class="col-md-4">
      <div class="card dashboard-card text-center border-primary">
        <div class="card-body">
          <h5 class="card-title text-primary">Total Students</h5>
          <h2 class="display-5 fw-bold"><?= $total_students ?></h2>
          <p class="text-muted">Students in <?= htmlspecialchars($zone) ?> Zone</p>
        </div>
      </div>
    </div>

    <!-- 🏫 Total Classes -->
    <div class="col-md-4">
      <div class="card dashboard-card text-center border-success">
        <div class="card-body">
          <h5 class="card-title text-success">Total Classes</h5>
          <h2 class="display-5 fw-bold"><?= $total_classes ?></h2>
          <p class="text-muted">Classes in this zone</p>
        </div>
      </div>
    </div>

    <!-- 👥 Total Teams -->
    <div class="col-md-4">
      <div class="card dashboard-card text-center border-warning">
        <div class="card-body">
          <h5 class="card-title text-warning">Total Teams</h5>
          <h2 class="display-5 fw-bold"><?= $total_teams ?></h2>
          <p class="text-muted">Teams in this zone</p>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-5 text-center">
    <p class="text-muted">Last updated: <?= date('d M Y, h:i A') ?></p>
  </div>
</div>
</body>
</html>
