<?php
session_start();
include "db.php";

if (!isset($_GET['exam_id'])) die("Invalid request");

$exam_id = intval($_GET['exam_id']);
$date = $_GET['date'] ?? date('Y-m-d');
$display_date = date('d M Y', strtotime($date));

// Fetch exam info
$stmt = $conn->prepare("
    SELECT subject, sem, exam_name, exam_date 
    FROM exam_schedule 
    WHERE id = ?
");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();

// Fetch student feedback sorted properly
$stmt2 = $conn->prepare("
    SELECT 
        f.htno,
        f.name,
        f.classid,
        s.teamid,
        f.rating,
        f.remark
    FROM exam_feedback f
    LEFT JOIN STUDENTS s ON s.htno=f.htno
    WHERE f.exam_id = ?
    ORDER BY f.classid, s.teamid, f.htno
");
$stmt2->bind_param("i", $exam_id);
$stmt2->execute();
$rows = $stmt2->get_result();

// Organize data by class and team
$organized_data = [];
$student_count = 0;

while ($r = $rows->fetch_assoc()) {
    $classid = $r['classid'];
    $teamid = $r['teamid'];
    
    if (!isset($organized_data[$classid])) {
        $organized_data[$classid] = [];
    }
    if (!isset($organized_data[$classid][$teamid])) {
        $organized_data[$classid][$teamid] = [];
    }
    
    $organized_data[$classid][$teamid][] = $r;
    $student_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback | <?= htmlspecialchars($exam['subject']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .class-header {
            background-color: #e9ecef;
            font-weight: 600;
            padding: 12px 15px;
            border-left: 4px solid #007bff;
            margin-top: 20px;
        }
        
        .team-header {
            background-color: #f8f9fa;
            font-weight: 500;
            padding: 10px 15px;
            border-left: 3px solid #6c757d;
            margin-top: 15px;
        }
        
        .student-row {
            border-bottom: 1px solid #dee2e6;
        }
        
        .student-row:last-child {
            border-bottom: none;
        }
        
        .rating-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .rating-5 { background-color: #d4edda; color: #155724; }
        .rating-4 { background-color: #d1ecf1; color: #0c5460; }
        .rating-3 { background-color: #fff3cd; color: #856404; }
        .rating-2 { background-color: #f8d7da; color: #721c24; }
        .rating-1 { background-color: #e2e3e5; color: #383d41; }
        
        .htno {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="container py-4">

    <!-- Header -->
    <div class="header-card p-4 mb-4">
        <div class="row">
            <div class="col-md-8">
                <h4 class="text-primary mb-2"><?= htmlspecialchars($exam['subject']) ?></h4>
                <p class="mb-1"><strong>Semester:</strong> <?= htmlspecialchars($exam['sem']) ?></p>
                <p class="mb-1"><strong>Exam:</strong> <?= htmlspecialchars($exam['exam_name'] ?: 'General Exam') ?></p>
                <p class="mb-0"><strong>Date:</strong> <?= htmlspecialchars($display_date) ?></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="pr_examfeedback_report.php?date=<?= $date ?>" class="btn btn-outline-secondary">
                    ← Back to Overview
                </a>
            </div>
        </div>
    </div>

    <!-- Student Feedback -->
    <div class="header-card p-0">
        <div class="p-3 border-bottom">
            <h5 class="mb-0">Student Feedback Responses</h5>
        </div>
        
        <?php if ($student_count > 0): ?>
            <div class="p-3">
                <?php 
                $student_counter = 1;
                foreach ($organized_data as $classid => $teams): 
                ?>
                    <div class="class-header">
                        Class: <?= htmlspecialchars($classid) ?>
                    </div>
                    
                    <?php foreach ($teams as $teamid => $students): ?>
                        <div class="team-header">
                            Team: <?= htmlspecialchars($teamid) ?>
                        </div>
                        
                        <?php foreach ($students as $student): 
                            $rating_class = "rating-" . $student['rating'];
                        ?>
                        <div class="student-row p-3">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="fw-bold"><?= htmlspecialchars($student['name']) ?></div>
                                    <div class="htno"><?= htmlspecialchars($student['htno']) ?></div>
                                </div>
                                <div class="col-md-2">
                                    <span class="rating-badge <?= $rating_class ?>">
                                        <?= $student['rating'] ?>/5
                                    </span>
                                </div>
                                <div class="col-md-7">
                                    <?php if (!empty(trim($student['remark']))): ?>
                                        <div class="text-dark"><?= htmlspecialchars($student['remark']) ?></div>
                                    <?php else: ?>
                                        <div class="text-muted fst-italic">No remarks provided</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted">No feedback received for this exam yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="mt-4 text-center text-muted">
        <small>Exam Feedback System &copy; <?= date('Y') ?></small>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>