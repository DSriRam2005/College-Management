<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');
include "db.php";

$today = date("Y-m-d");

$student = null;
$today_classes = [];
$previous_classes = [];

if (isset($_POST['search'])) {

    $htno = trim($_POST['htno']);

    $stmt = $conn->prepare("SELECT * FROM STUDENTS WHERE htno = ?");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($student) {

        $classid = $student['classid'];

        // ✅ TODAY CLASSES
        $stmt = $conn->prepare("
            SELECT C.*, E.expert_name 
            FROM CLASS_CALENDAR C
            LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
            WHERE C.date = ? 
            AND FIND_IN_SET(?, C.classids)
            ORDER BY C.start_time ASC
        ");
        $stmt->bind_param("ss", $today, $classid);
        $stmt->execute();
        $today_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // ✅ PREVIOUS CLASSES
        $stmt = $conn->prepare("
            SELECT C.*, E.expert_name 
            FROM CLASS_CALENDAR C
            LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
            WHERE C.date < ? 
            AND FIND_IN_SET(?, C.classids)
            ORDER BY C.date DESC, C.start_time DESC
        ");
        $stmt->bind_param("ss", $today, $classid);
        $stmt->execute();
        $previous_classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Class Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- ✅ Mobile view -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f3f4f6;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page-wrapper {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 10px;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
            color: #111827;
        }

        .page-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: .9rem;
            margin-bottom: 1.5rem;
        }

        /* SEARCH CARD */
        .search-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        }
        .search-card .btn-primary {
            font-weight: 600;
        }
        @media (max-width: 575.98px) {
            .search-card .row > div {
                margin-bottom: .75rem;
            }
        }

        /* STUDENT INFO */
        .student-info-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
        }
        .student-info-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }
        .student-info-meta {
            font-size: .85rem;
            color: #6b7280;
        }

        /* GENERIC CARD STYLE */
        .calendar-card {
            border-radius: 14px;
            border: none;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .calendar-card .card-header {
            padding: .75rem 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .95rem;
        }

        .calendar-card .badge-pill {
            border-radius: 999px;
            font-size: .7rem;
            padding: .25rem .75rem;
        }

        /* TABLES */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
            font-size: .85rem;
        }

        .table thead th {
            background: #f3f4f6;
            white-space: nowrap;
            border-bottom-width: 1px;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-accent-bg: #f9fafb;
        }

        .no-data-row {
            text-align: center;
            color: #ef4444;
            font-weight: 500;
        }

        /* FEEDBACK BUTTON */
        .btn-feedback {
            font-size: .75rem;
            padding: .25rem .6rem;
        }

        /* Make cards nicely spaced on mobile */
        @media (max-width: 767.98px) {
            .calendar-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <h1 class="page-title">Student Class Calendar</h1>
    <p class="page-subtitle">Check today’s and previous classes and quickly submit feedback.</p>

    <!-- ✅ SEARCH -->
    <form method="post" class="card search-card p-3 p-md-4 mb-4 bg-white">
        <div class="row align-items-center g-2">
            <div class="col-12 col-md-6">
                <label class="form-label mb-1">Hall Ticket Number</label>
                <input type="text" name="htno" class="form-control" placeholder="Enter HTNO" required>
            </div>
            <div class="col-12 col-md-3 d-grid">
                <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
                <button type="submit" name="search" class="btn btn-primary">
                    Search
                </button>
            </div>
        </div>
    </form>

    <?php if ($student): ?>

        <!-- ✅ STUDENT INFO -->
        <div class="card student-info-card mb-4 bg-white">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <div class="student-info-title">
                        <?= htmlspecialchars($student['name'] ?? 'Student') ?>
                    </div>
                    <div class="student-info-meta">
                        HTNO: <strong><?= htmlspecialchars($student['htno']) ?></strong>
                        <?php if (!empty($student['branch'])): ?>
                            &nbsp; | &nbsp; Branch: <strong><?= htmlspecialchars($student['branch']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($student['classid'])): ?>
                            &nbsp; | &nbsp; Class ID: <strong><?= htmlspecialchars($student['classid']) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-2 mt-md-0 text-md-end">
                    <span class="badge text-bg-success">Today: <?= htmlspecialchars($today) ?></span>
                </div>
            </div>
        </div>

        <!-- ✅ TODAY CLASSES -->
        <div class="card calendar-card mb-4">
            <div class="card-header bg-success text-white">
                <span>Today's Classes</span>
                <span class="badge bg-light text-success badge-pill">
                    <?= count($today_classes) ?> class<?= count($today_classes) == 1 ? '' : 'es' ?>
                </span>
            </div>
            <div class="table-wrapper">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Topic</th>
                            <th>Expert</th>
                            <th>Class Type</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($today_classes): ?>
                        <?php foreach ($today_classes as $row): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(substr($row['start_time'],0,5)) ?>
                                    –
                                    <?= htmlspecialchars(substr($row['end_time'],0,5)) ?>
                                </td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['topic']) ?></td>
                                <td><?= htmlspecialchars($row['expert_name']) ?></td>
                                <td><?= htmlspecialchars($row['classtype']) ?></td>
                                <td>
                                    <a href="feedback.php?class_id=<?= (int)$row['id'] ?>&htno=<?= urlencode($student['htno']) ?>" 
                                       class="btn btn-warning btn-sm btn-feedback">
                                       Give Feedback
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data-row">No classes today</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ✅ PREVIOUS CLASSES -->
        <div class="card calendar-card mb-4">
            <div class="card-header bg-dark text-white">
                <span>Previous Classes</span>
                <span class="badge bg-light text-dark badge-pill">
                    <?= count($previous_classes) ?> record<?= count($previous_classes) == 1 ? '' : 's' ?>
                </span>
            </div>
            <div class="table-wrapper">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Topic</th>
                            <th>Class Type</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($previous_classes): ?>
                        <?php foreach ($previous_classes as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td>
                                    <?= htmlspecialchars(substr($row['start_time'],0,5)) ?>
                                    –
                                    <?= htmlspecialchars(substr($row['end_time'],0,5)) ?>
                                </td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['topic']) ?></td>
                                <td><?= htmlspecialchars($row['classtype']) ?></td>
                                <td>
                                    <a href="feedback.php?class_id=<?= (int)$row['id'] ?>&htno=<?= urlencode($student['htno']) ?>" 
                                       class="btn btn-warning btn-sm btn-feedback">
                                       Give Feedback
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data-row">No previous classes</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
