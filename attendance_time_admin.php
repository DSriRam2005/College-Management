<?php
session_start();
include 'db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

// ✅ Restrict to ADMIN only


$message = $error = "";

// --- When Admin Submits Defaults ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default'])) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $updated_by = $_SESSION['username'] ?? 'ADMIN';

    if (empty($start_time) || empty($end_time)) {
        $error = "Please enter both start and end times.";
    } else {
        // Get all distinct classids from STUDENTS
        $result = $conn->query("SELECT DISTINCT classid FROM STUDENTS WHERE classid IS NOT NULL AND classid != ''");
        $classids = [];
        while ($r = $result->fetch_assoc()) $classids[] = $r['classid'];

        if (empty($classids)) {
            $error = "No class IDs found in STUDENTS table.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    INSERT INTO attendance_time (classid, start_time, end_time, updated_by)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        start_time = VALUES(start_time),
                        end_time = VALUES(end_time),
                        updated_by = VALUES(updated_by),
                        updated_at = CURRENT_TIMESTAMP
                ");
                foreach ($classids as $cid) {
                    $stmt->bind_param("ssss", $cid, $start_time, $end_time, $updated_by);
                    $stmt->execute();
                }
                $stmt->close();
                $conn->commit();
                $message = "Default attendance time applied to all classes (" . count($classids) . " total).";
            } catch (Exception $ex) {
                $conn->rollback();
                $error = "Error: " . $ex->getMessage();
            }
        }
    }
}

// --- Show Current Times ---
$res = $conn->query("SELECT * FROM attendance_time ORDER BY classid ASC");
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin — Set Default Attendance Time</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Admin — Default Attendance Time for All Classes</h3>
    <a href="logout.php" class="btn btn-sm btn-outline-secondary">Logout</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Default Start Time</label>
          <input type="time" name="start_time" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Default End Time</label>
          <input type="time" name="end_time" class="form-control" required>
        </div>
        <div class="col-md-4">
          <button type="submit" name="set_default" class="btn btn-success w-100">Apply to All Classes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white">
      <strong>Current Attendance Times</strong>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped text-center mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Class ID</th>
              <th>Start Time</th>
              <th>End Time</th>
              <th>Updated By</th>
              <th>Updated At</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="6" class="py-3">No records found.</td></tr>
            <?php else:
              $i = 1;
              foreach ($rows as $r): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['classid']) ?></td>
                <td><?= htmlspecialchars($r['start_time']) ?></td>
                <td><?= htmlspecialchars($r['end_time']) ?></td>
                <td><?= htmlspecialchars($r['updated_by']) ?></td>
                <td><?= htmlspecialchars($r['updated_at']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
