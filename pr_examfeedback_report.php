<?php
session_start();
include "db.php";

date_default_timezone_set('Asia/Kolkata');

$selected_date = $_GET['date'] ?? date('Y-m-d');
$display_date = date('d M Y', strtotime($selected_date));

// Fetch exams on selected date
$stmt = $conn->prepare("
    SELECT 
        id, subject, sem, exam_name, exam_date
    FROM exam_schedule
    WHERE exam_date = ?
    ORDER BY sem, subject
");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$exams_result = $stmt->get_result();
$exams_count = $exams_result->num_rows;

// Calculate statistics
$total_students = 0;
$total_rating = 0;
$rating_count = 0;
$exam_ids = [];

// Store exams in array for later use
$exams = [];
while ($ex = $exams_result->fetch_assoc()) {
    $exams[] = $ex;
    $exam_ids[] = $ex['id'];
    
    // Get stats for each exam
    $eid = $ex['id'];
    $s = $conn->query("
        SELECT 
            ROUND(AVG(rating),2) AS avg_rating,
            COUNT(*) AS total_students
        FROM exam_feedback
        WHERE exam_id = $eid
    ")->fetch_assoc();
    
    if ($s['total_students'] > 0) {
        $total_students += $s['total_students'];
        $total_rating += ($s['avg_rating'] * $s['total_students']);
        $rating_count += $s['total_students'];
    }
}

// Calculate overall average rating
$overall_avg = $rating_count > 0 ? round($total_rating / $rating_count, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Feedback Dashboard | Student Performance Insights</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 12px;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .card-custom {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .date-filter-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .table-custom {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .table-custom th {
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f1f3f4;
        }
        
        .badge-confidence {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            padding: 0.5rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .date-display {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            display: inline-flex;
            align-items: center;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <!-- HEADER -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2"><i class="fas fa-chart-line me-2"></i>Exam Feedback Dashboard</h1>
                <p class="mb-0 opacity-75">Monitor student confidence and performance metrics</p>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-university fa-3x opacity-75"></i>
            </div>
        </div>
    </div>

    <!-- DATE FILTER -->
    <div class="date-filter-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>Select Date to View Reports</h5>
                <form method="get" class="row g-2">
                    <div class="col-auto">
                        <input type="date" name="date" class="form-control" value="<?= $selected_date ?>" required>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <div class="date-display">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>
                    <span>Showing reports for <strong><?= $display_date ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- STATS SUMMARY -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card-custom stats-card">
                <div class="stats-value text-primary"><?= $exams_count ?></div>
                <div class="stats-label">Exams Scheduled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom stats-card">
                <div class="stats-value text-success"><?= $total_students ?></div>
                <div class="stats-label">Total Feedback Responses</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom stats-card">
                <div class="stats-value text-info"><?= $overall_avg ?></div>
                <div class="stats-label">Average Confidence Level</div>
            </div>
        </div>
    </div>

    <!-- EXAMS TABLE -->
    <div class="card-custom">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="fas fa-list-alt me-2 text-primary"></i>Scheduled Exams</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($exams_count > 0): ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-graduation-cap me-1"></i> Semester</th>
                                <th><i class="fas fa-file-alt me-1"></i> Exam Name</th>
                                <th><i class="fas fa-book me-1"></i> Subject</th>
                                <th><i class="fas fa-chart-bar me-1"></i> Avg Confidence</th>
                                <th><i class="fas fa-users me-1"></i> Students</th>
                                <th><i class="fas fa-eye me-1"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $ex): 
                                $eid = $ex['id'];

                                // Fetch stats for this exam
                                $s = $conn->query("
                                    SELECT 
                                        ROUND(AVG(rating),2) AS avg_rating,
                                        COUNT(*) AS total_students
                                    FROM exam_feedback
                                    WHERE exam_id = $eid
                                ")->fetch_assoc();

                                $avg = $s['avg_rating'] ?: "0.00";
                                $cnt = $s['total_students'] ?: 0;
                                
                                // Determine confidence level color
                                $confidence_class = "bg-primary";
                                if ($avg >= 4) $confidence_class = "bg-success";
                                elseif ($avg >= 3) $confidence_class = "bg-info";
                                elseif ($avg >= 2) $confidence_class = "bg-warning";
                                elseif ($avg > 0) $confidence_class = "bg-danger";
                                else $confidence_class = "bg-secondary";
                            ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark p-2">
                                        <i class="fas fa-graduation-cap me-1"></i> <?= htmlspecialchars($ex['sem']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($ex['exam_name'] ?: 'General Exam') ?></td>
                                <td><?= htmlspecialchars($ex['subject']) ?></td>
                                <td>
                                    <?php if ($avg > 0): ?>
                                        <span class="badge <?= $confidence_class ?> text-white">
                                            <?= $avg ?>/5
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No data</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark p-2">
                                        <i class="fas fa-user me-1"></i> <?= $cnt ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="exam_report_view.php?exam_id=<?= $eid ?>&date=<?= $selected_date ?>" 
                                       class="btn btn-primary btn-sm btn-view">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h4>No Exams Scheduled</h4>
                    <p>There are no exams scheduled for <?= $display_date ?>.</p>
                    <p class="text-muted">Try selecting a different date to view exam feedback.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="mt-4 text-center text-muted">
        <p>Exam Feedback System &copy; <?= date('Y') ?> | Providing insights for better education</p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>