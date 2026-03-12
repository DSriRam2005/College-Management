
<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Asia/Kolkata');
include 'db.php';

// ✅ Show success message if redirected after submission
$message = '';
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$error = '';

// --- Access control: only CPTO allowed ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header('Location: index.php');
    exit();
}

// CPTO tied to one class (from login session)
$cp_classid = $_SESSION['classid'] ?? null;

// --- Fetch attendance time window from DB ---
$attendance_start = '09:00:00';
$attendance_end = '15:00:00';

// Create table if not exists (to store start/end times)
$conn->query("
    CREATE TABLE IF NOT EXISTS attendance_time (
        id INT AUTO_INCREMENT PRIMARY KEY,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
");

// If no record, insert default times
$res = $conn->query("SELECT start_time, end_time FROM attendance_time LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $attendance_start = $row['start_time'];
    $attendance_end = $row['end_time'];
} else {
    $conn->query("INSERT INTO attendance_time (start_time, end_time) VALUES ('09:00:00', '15:00:00')");
}

$now_time = date('H:i:s');
$attendance_open = ($now_time >= $attendance_start && $now_time <= $attendance_end);

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!$attendance_open) {
        $error = "Attendance can only be marked between $attendance_start and $attendance_end.";
    } else {
        $att_date = date('Y-m-d'); // Only today's attendance
        $classid = $_POST['classid'] ?? $cp_classid;
        $statuses = $_POST['status'] ?? [];

        if (!$classid) {
            $error = "Class ID is required.";
        } else {
            $conn->begin_transaction();
            try {
                $sql = "INSERT INTO attendance (htno, classid, att_date, status)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            status = VALUES(status),
                            updated_at = CURRENT_TIMESTAMP";
                $stmt = $conn->prepare($sql);
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

                // Fetch all students of this class
                $student_query = $conn->prepare("SELECT htno FROM STUDENTS WHERE classid = ? AND debarred = 0");
                $student_query->bind_param('s', $classid);
                $student_query->execute();
                $students_res = $student_query->get_result();

                while ($student = $students_res->fetch_assoc()) {
                    $htno = $student['htno'];
                    // Checkbox checked → Present, unchecked → Absent
                    $status = isset($statuses[$htno]) ? 'Present' : 'Absent';
                    $stmt->bind_param('ssss', $htno, $classid, $att_date, $status);
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                }

                $stmt->close();
                $conn->commit();

                // ✅ Redirect to same page after saving successfully
                $_SESSION['success_message'] = "Attendance saved successfully for class $classid.";
                header("Location: ctpo_todayattend.php");
                exit();

            } catch (Exception $ex) {
                $conn->rollback();
                $error = "Could not save attendance: " . $ex->getMessage();
            }
        }
    }
}

// --- Fetch students for class ---
$students = [];
$existing = [];

if ($cp_classid) {
    $stmt = $conn->prepare("
    SELECT htno, name, phone, teamid 
    FROM STUDENTS 
    WHERE classid = ? 
      AND debarred = 0 
    ORDER BY 
        CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) ASC,
        teamid ASC,
        htno ASC
");

    $stmt->bind_param('s', $cp_classid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();

    // Fetch today's attendance
    $today = date('Y-m-d');
    $stmt2 = $conn->prepare("SELECT htno, status FROM attendance WHERE classid = ? AND att_date = ? ORDER BY htno ASC ");
    $stmt2->bind_param('ss', $cp_classid, $today);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $existing[$r['htno']] = $r['status'];
    }
    $stmt2->close();
}

// --- Calculate totals ---
$total_students = count($students);
$present_count = 0;
$absent_count = 0;
foreach ($students as $s) {
    $htno = $s['htno'];
    $st = $existing[$htno] ?? 'Absent';
    if (isset($_POST['save_attendance'])) {
        $st = isset($_POST['status'][$htno]) ? 'Present' : 'Absent';
    }
    if ($st === 'Present') $present_count++; else $absent_count++;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>CPTO — Mark Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f7f9fc;
    color: #333;
}
.container {
    max-width: 950px;
}
h3 {
    font-weight: 600;
    color: #0d6efd;
}
.card {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.table {
    border-radius: 10px;
    overflow: hidden;
    background-color: #fff;
}
.table th {
    background-color: #e9f2ff;
    color: #0d6efd;
    font-weight: 600;
    text-align: center;
}
.table td {
    vertical-align: middle !important;
}
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f2f6fb;
}
.table-sm th, .table-sm td {
    padding: 0.6rem;
}
.att-present {
    background-color: #d4edda !important;
}
.att-absent {
    background-color: #f8d7da !important;
}
input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}
.btn-success {
    background: linear-gradient(135deg, #28a745, #218838);
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.2rem;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
}
.btn-outline-secondary {
    border-radius: 8px;
}
.btn-link-page {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: #fff !important;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1.2rem;
    font-weight: 500;
    text-decoration: none;
}
.btn-link-page:hover {
    background: linear-gradient(135deg, #0a58ca, #084298);
    color: #fff;
}
.alert {
    border-radius: 10px;
    font-size: 0.95rem;
}
.badge {
    font-size: 0.9rem;
    padding: 0.5em 0.8em;
    border-radius: 10px;
}
/* Summary cards on top */
.summary-cards {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 15px;
}
.summary-card {
    flex: 1 1 30%;
    min-width: 120px;
    border-radius: 12px;
    padding: 1rem;
    color: #fff;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.total-card { background: #0d6efd; }
.present-card { background: #28a745; }
.absent-card { background: #dc3545; }
.summary-card h4 { margin:0; font-size: 1.4rem; font-weight: 600; }
.summary-card p { margin:0; font-size: 0.95rem; }
/* Responsive */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.88rem;
    }
    h3 { font-size: 1.3rem; }
}
</style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h3>CPTO — Mark Today's Attendance</h3>
    <!-- ✅ Added link to Remarks page -->
    <a href="ctpo_todayattend.php" class="btn btn-link-page">Go to Remarks Page</a>
  </div>

  <!-- Attendance summary cards -->
  <div class="summary-cards">
    <div class="summary-card total-card">
        <h4><?= $total_students ?></h4>
        <p>Total Students</p>
    </div>
    <div class="summary-card present-card">
        <h4><?= $present_count ?></h4>
        <p>Present</p>
    </div>
    <div class="summary-card absent-card">
        <h4><?= $absent_count ?></h4>
        <p>Absent</p>
    </div>
  </div>

  <div class="alert alert-info">
      Attendance Window: <?= $attendance_start ?> to <?= $attendance_end ?> | Current Time: <?= $now_time ?>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$cp_classid): ?>
    <div class="alert alert-warning">Class not assigned. Cannot mark attendance.</div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="classid" value="<?= htmlspecialchars($cp_classid) ?>">

      <div class="card mb-3">
        <div class="card-body p-2">
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle table-striped">
              <thead class="table-light text-center">
                <tr>
                  <th>#</th>
                  <th>HT No</th>
                  <th>Name</th>
                  <th>Present</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($students)): ?>
                  <tr><td colspan="5" class="text-center py-4">No students found.</td></tr>
                <?php else:
                  $i=1;
                  foreach ($students as $s):
                    $htno = $s['htno'];
                    $pref = $existing[$htno] ?? 'Absent';
                    if (isset($_POST['save_attendance'])) {
                        $pref = isset($_POST['status'][$htno]) ? 'Present' : 'Absent';
                    }
                    $row_class = $pref === 'Present' ? 'att-present' : 'att-absent';
                ?>
                  <tr class="<?= $row_class ?>">
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($htno) ?></td>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td class="text-center">
                        <input type="checkbox" name="status[<?= htmlspecialchars($htno) ?>]" value="Present" <?= $pref === 'Present' ? 'checked' : '' ?>
                        <?= !$attendance_open ? 'disabled' : '' ?>>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <button type="submit" name="save_attendance" class="btn btn-success" <?= !$attendance_open ? 'disabled' : '' ?>>Save Attendance</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
