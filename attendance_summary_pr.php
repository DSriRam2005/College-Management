<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// ===== ROLE CHECK =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    die("Access denied.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Kolkata');

// ===== DATE HANDLING =====
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// ===== FILTER HANDLING =====
$selected_year = $_GET['year'] ?? '25';
$selected_prog = $_GET['prog'] ?? '';

// ===== GET DISTINCT YEARS & PROGS FOR DROPDOWN =====
$years_res = $conn->query("SELECT DISTINCT year FROM STUDENTS WHERE year IS NOT NULL AND year != '' ORDER BY year ASC");
$progs_res = $conn->query("SELECT DISTINCT prog FROM STUDENTS WHERE prog IS NOT NULL AND prog != '' ORDER BY prog ASC");

// ===== BUILD QUERY =====
$query = "
    SELECT 
        s.classid AS classname,
        u.EMP_ID AS emp_id,
        u.name AS emp_name,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
        COUNT(s.htno) AS total_students
    FROM STUDENTS s
    LEFT JOIN attendance a 
        ON s.htno = a.htno 
        AND DATE(a.att_date) = ?
    LEFT JOIN USERS u
        ON u.classid = s.classid
        AND u.role IN ('CPTO','HTPO')
    WHERE s.classid IS NOT NULL 
      AND s.classid != '' 
      AND s.debarred = 0
";


$params = [$selected_date];
$types = "s";

if (!empty($selected_year)) {
    $query .= " AND s.year = ? ";
    $params[] = $selected_year;
    $types .= "s";
}

if (!empty($selected_prog)) {
    $query .= " AND s.prog = ? ";
    $params[] = $selected_prog;
    $types .= "s";
}

$query .= "
GROUP BY s.classid, u.EMP_ID, u.name
ORDER BY 
    (SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(s.htno)) DESC
";


$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Summary - PR View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 1rem; }
        .date-nav button { font-weight: bold; }
        th, td { vertical-align: middle !important; }
        a.class-link { text-decoration: none; font-weight: 600; color: #007bff; }
        a.class-link:hover { text-decoration: underline; color: #0056b3; }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-primary">📊 Attendance Summary (PR View)</h3>
        <a href="dashboard_pr.php" class="btn btn-secondary btn-sm">⬅ Back</a>
    </div>

    <!-- Filters -->
    <div class="card p-3 mb-3 shadow-sm">
        <form method="get" class="row g-3 align-items-center justify-content-center">

            <div class="col-auto">
                <input type="date" name="date" value="<?php echo $selected_date; ?>" class="form-control" onchange="this.form.submit()">
            </div>

            <div class="col-auto">
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php while ($yr = $years_res->fetch_assoc()) { ?>
                        <option value="<?php echo $yr['year']; ?>" <?php if ($selected_year == $yr['year']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($yr['year']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-auto">
                <select name="prog" class="form-select" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    <?php while ($pg = $progs_res->fetch_assoc()) { ?>
                        <option value="<?php echo $pg['prog']; ?>" <?php if ($selected_prog == $pg['prog']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($pg['prog']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-auto">
                <a href="?date=<?php echo $prev_date; ?>&year=<?php echo urlencode($selected_year); ?>&prog=<?php echo urlencode($selected_prog); ?>" class="btn btn-outline-primary">&lt; Prev</a>
                <a href="?date=<?php echo $next_date; ?>&year=<?php echo urlencode($selected_year); ?>&prog=<?php echo urlencode($selected_prog); ?>" class="btn btn-outline-primary">Next &gt;</a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-center mb-3">
                Date: <span class="text-primary"><?php echo htmlspecialchars($selected_date); ?></span>
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Class ID</th>
                            <th>EMP ID</th>
                            <th>Name</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Total</th>
                            <th>Attendance %</th>
                        </tr>

                    </thead>
                    <tbody>
                    <?php
                    $grand_present = $grand_absent = $grand_total = 0;
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $attendance_percent = $row['total_students'] > 0 
                                ? round(($row['present_count'] / $row['total_students']) * 100, 1) 
                                : 0;

                            $grand_present += $row['present_count'];
                            $grand_absent += $row['absent_count'];
                            $grand_total += $row['total_students'];

                            $link = "attendance_classwise.php?classid=" . urlencode($row['classname']) .
                                    "&date=" . urlencode($selected_date) .
                                    "&year=" . urlencode($selected_year) .
                                    "&prog=" . urlencode($selected_prog);

                            echo "
                                <tr>
                                    <td><a href='$link' class='class-link'>{$row['classname']}</a></td>
                                        <td>{$row['emp_id']}</td>
                                        <td>{$row['emp_name']}</td>
                                    <td class='text-success fw-bold'>{$row['present_count']}</td>
                                    <td class='text-danger fw-bold'>{$row['absent_count']}</td>
                                    <td>{$row['total_students']}</td>
                                    <td>{$attendance_percent}%</td>
                                </tr>
                            ";
                        }

                        $overall_percent = $grand_total > 0 
                            ? round(($grand_present / $grand_total) * 100, 1) 
                            : 0;

                        echo "
                            <tr class='table-primary fw-bold'>
                                <td>Total</td>
                                <td></td>
                                <td></td>
                                <td>$grand_present</td>
                                <td>$grand_absent</td>
                                <td>$grand_total</td>
                                <td>$overall_percent%</td>
                            </tr>
                        ";
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted'>No attendance records found for this date.</td></tr>";
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
