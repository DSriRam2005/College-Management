<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php'; 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// REQUIRED FOR INFINITYFREE
$conn->query("SET SQL_BIG_SELECTS=1");

date_default_timezone_set('Asia/Kolkata');

// ===== ROLE CHECK =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    die("Access denied.");
}

// ===== FETCH USER INFO =====
$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) die("User info not found.");

$prog = $user['prog'];
$year = $user['year'];
$college = $user['college'];   // e.g. "KIET,KIEK"

// ===== DATE HANDLING =====
$selected_date = $_GET['date'] ?? date('Y-m-d');
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// ===== MAIN QUERY =====
$query = "
    SELECT 
        s.classid,
        COUNT(s.htno) AS total_students,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count
    FROM STUDENTS s
    LEFT JOIN attendance a 
        ON s.htno = a.htno AND DATE(a.att_date) = ?
    WHERE s.prog = ? 
      AND s.year = ? 
      AND FIND_IN_SET(s.college, ?)
      AND s.classid IS NOT NULL 
      AND s.classid != '' AND s.debarred = 0
    GROUP BY s.classid
    ORDER BY s.classid ASC 
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssis", $selected_date, $prog, $year, $college);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HTPO Attendance Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 1rem; }
    th, td { vertical-align: middle !important; }
    a.class-link { color: #0d6efd; font-weight: 600; text-decoration: none; }
    a.class-link:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary">📊 Attendance Report - HTPO View</h3>
        <a href="dashboard_htpo.php" class="btn btn-secondary btn-sm">⬅ Back</a>
    </div>

    <div class="alert alert-info p-3 mb-3">
        <strong>Program:</strong> <?= htmlspecialchars($prog) ?> &nbsp;&nbsp;
        <strong>Year:</strong> <?= htmlspecialchars($year) ?> &nbsp;&nbsp;
        <strong>Colleges:</strong> <?= htmlspecialchars($college) ?>
    </div>

    <!-- Filters -->
    <div class="card p-3 mb-3 shadow-sm">
        <form method="get" class="row g-3 align-items-center justify-content-center">
            <div class="col-auto">
                <input type="date" name="date" value="<?= $selected_date ?>" class="form-control" onchange="this.form.submit()">
            </div>

            <div class="col-auto">
                <a href="?date=<?= $prev_date ?>" class="btn btn-outline-primary">&lt; Prev</a>
                <a href="?date=<?= $next_date ?>" class="btn btn-outline-primary">Next &gt;</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-center mb-3">
                Date: <span class="text-primary"><?= htmlspecialchars($selected_date) ?></span>
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>S.No</th>
                            <th>Class ID</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Total</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sno = 1;
                    $grand_present = $grand_absent = $grand_total = 0;

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $attendance_percent = $row['total_students'] > 0 
                                ? round(($row['present_count'] / $row['total_students']) * 100, 1) 
                                : 0;

                            $grand_present += $row['present_count'];
                            $grand_absent += $row['absent_count'];
                            $grand_total += $row['total_students'];

                            echo "
                                <tr>
                                    <td>{$sno}</td>
                                    <td>
                                        <a href='attendance_classwise.php?classid=".urlencode($row['classid'])."&date={$selected_date}' 
                                           class='class-link'>
                                           ".htmlspecialchars($row['classid'])."
                                        </a>
                                    </td>
                                    <td class='text-success fw-bold'>{$row['present_count']}</td>
                                    <td class='text-danger fw-bold'>{$row['absent_count']}</td>
                                    <td>{$row['total_students']}</td>
                                    <td>{$attendance_percent}%</td>
                                </tr>
                            ";
                            $sno++;
                        }

                        $overall_percent = $grand_total > 0 
                            ? round(($grand_present / $grand_total) * 100, 1) 
                            : 0;

                        echo "
                            <tr class='table-primary fw-bold'>
                                <td colspan='2'>Total</td>
                                <td>{$grand_present}</td>
                                <td>{$grand_absent}</td>
                                <td>{$grand_total}</td>
                                <td>{$overall_percent}%</td>
                            </tr>
                        ";
                    } else {
                        echo "<tr><td colspan='6' class='text-center text-muted'>No attendance records found for this date.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
