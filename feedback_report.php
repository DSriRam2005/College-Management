<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');
include "db.php";

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$expert_id = $_GET['expert_id'] ?? '';

$where = " WHERE 1=1 ";

// ✅ DATE FILTER
if (!empty($from_date) && !empty($to_date)) {
    $where .= " AND C.date BETWEEN '$from_date' AND '$to_date' ";
}

// ✅ FACULTY FILTER
if (!empty($expert_id)) {
    $where .= " AND E.expert_id = '$expert_id' ";
}

// ✅ FETCH FEEDBACK REPORT
$query = "
    SELECT 
        F.*,
        C.date,
        C.subject,
        C.topic,
        C.classtype,
        E.expert_name,
        S.name AS student_name
    FROM CLASS_FEEDBACK F
    JOIN CLASS_CALENDAR C ON F.CLASS_CALENDAR_ID = C.id
    JOIN EXPERTS E ON C.expert_id = E.expert_id
    LEFT JOIN STUDENTS S ON F.HTNO = S.htno
    $where
    ORDER BY C.date DESC
";

$result = mysqli_query($conn, $query);

// ✅ FETCH ALL EXPERTS FOR DROPDOWN
$experts = mysqli_query($conn, "SELECT expert_id, expert_name FROM EXPERTS ORDER BY expert_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            Date-wise & Faculty-wise Feedback Report
        </div>

        <form method="get" class="card-body row g-3">

            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="form-control">
            </div>

            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Faculty (Expert)</label>
                <select name="expert_id" class="form-control">
                    <option value="">All Experts</option>
                    <?php while ($ex = mysqli_fetch_assoc($experts)): ?>
                        <option value="<?= $ex['expert_id'] ?>" 
                            <?= ($expert_id == $ex['expert_id']) ? 'selected' : '' ?>>
                            <?= $ex['expert_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>

        </form>
    </div>

    <!-- ✅ REPORT TABLE -->
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            Feedback Report
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <tr class="table-dark">
                    <th>Date</th>
                    <th>Student</th>
                    <th>HTNO</th>
                    <th>Faculty</th>
                    <th>Subject</th>
                    <th>Topic</th>
                    <th>Class Type</th>
                    <th>Q1</th>
                    <th>Q2</th>
                    <th>Q3</th>
                    <th>Comments</th>
                </tr>

                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['student_name'] ?></td>
                            <td><?= $row['HTNO'] ?></td>
                            <td><?= $row['expert_name'] ?></td>
                            <td><?= $row['subject'] ?></td>
                            <td><?= $row['topic'] ?></td>
                            <td><?= $row['classtype'] ?></td>
                            <td><?= $row['Q1_RATING'] ?></td>
                            <td><?= $row['Q2_RATING'] ?></td>
                            <td><?= $row['Q3_RATING'] ?></td>
                            <td><?= $row['COMMENTS'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-danger">No Feedback Found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

</body>
</html>
