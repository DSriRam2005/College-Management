<?php
session_start();
include "db.php";

// Timezone and today's date
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$today_display = date('d-m-Y');

/* -------------------------------------------------
   CREATE TABLES IF NOT EXISTS
--------------------------------------------------*/
$conn->query("
    CREATE TABLE IF NOT EXISTS exam_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_date DATE NOT NULL,
        branches VARCHAR(50) NOT NULL,
        exam_name VARCHAR(255),
        subject VARCHAR(255) NOT NULL,
        sem VARCHAR(10) NOT NULL
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS exam_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        htno VARCHAR(50),
        name VARCHAR(100),
        classid VARCHAR(50),
        branch VARCHAR(10),
        year INT,
        sem VARCHAR(10),
        exam_id INT,
        exam_name VARCHAR(255),
        exam_date DATE,
        subject VARCHAR(255),
        rating INT,
        remark VARCHAR(500),
        feedback_date DATETIME
    )
");

/* -------------------------------------------------
   HELPERS
--------------------------------------------------*/
function get_student($conn, $htno) {
    $stmt = $conn->prepare("SELECT htno, name, classid, year FROM STUDENTS WHERE htno=? LIMIT 1");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_allowed_sems($year) {

    // Sem 1-1 allowed for 23, 24, 25
    if (in_array($year, [23, 24, 25])) {
        $sems[] = "1-1";
    }

    // Sem 1-2 allowed ONLY for 23 & 24
    if (in_array($year, [23, 24])) {
        $sems[] = "1-2";
    }

    return $sems ?? [];
}



function get_branch_from_htno($htno) {
    // Example: 24B25A4510 -> "45"
    return substr(strtoupper(trim($htno)), 6, 2);
}

function get_today_exams($conn, $branch, $allowed_sems, $today) {
    $list = [];
    foreach ($allowed_sems as $sem) {
        $stmt = $conn->prepare("
            SELECT id, exam_date, subject, exam_name, sem
            FROM exam_schedule
            WHERE exam_date = ?
              AND sem = ?
              AND FIND_IN_SET(?, branches) > 0
            ORDER BY sem, subject
        ");
        $stmt->bind_param("sss", $today, $sem, $branch);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }
    }
    return $list;
}

// ✅ MODIFIED: Check if student has already submitted feedback for specific exams
function get_submitted_exams($conn, $htno, $today) {
    $submitted = [];
    $stmt = $conn->prepare("SELECT exam_id FROM exam_feedback WHERE htno=? AND exam_date=?");
    $stmt->bind_param("ss", $htno, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $submitted[$row['exam_id']] = true;
    }
    return $submitted;
}

/* -------------------------------------------------
   INITIALIZE
--------------------------------------------------*/
$success_msg = "";
$error_msg   = "";
$student = null;
$allowed_sems = [];
$today_exams = [];
$submitted_exams = [];

/* -------------------------------------------------
   STEP 2 – HANDLE FEEDBACK SUBMISSION (multi-subject)
--------------------------------------------------*/
if (isset($_POST['submit_feedback'])) {

    $htno       = trim($_POST['htno'] ?? '');
    $exam_ids   = isset($_POST['exam_ids']) ? $_POST['exam_ids'] : [];
    $ratings    = isset($_POST['rating']) ? $_POST['rating'] : [];
    $remarks    = isset($_POST['remark']) ? $_POST['remark'] : [];

    if ($htno === '') {
        $error_msg = "Invalid request (no HTNO).";
    } else {

        $student = get_student($conn, $htno);

        if (!$student) {
            $error_msg = "Invalid roll number.";
        } 
        // 🔒 BLOCK 22 & 25 BATCH
        elseif ((int)$student['year'] == 22)
 {
            $error_msg = "Access denied for your batch.";
            $student = null;
        }
        elseif (empty($exam_ids)) {
            $error_msg = "Select at least one subject and fill its feedback.";
        } else {

            $branch = get_branch_from_htno($htno);
            $year   = (int)$student['year'];
            $allowed_sems = get_allowed_sems($year);
            $today_exams  = get_today_exams($conn, $branch, $allowed_sems, $today);
            $submitted_exams = get_submitted_exams($conn, $htno, $today);

            // Map today's exams by id
            $exam_map = [];
            foreach ($today_exams as $ex) {
                $exam_map[$ex['id']] = $ex;
            }

            // FIXED: Validate all selected exams without breaking early
            $valid_exams = [];
            foreach ($exam_ids as $eid) {
                $eid_int = (int)$eid;
                
                // Skip empty values
                if ($eid_int === 0) continue;
                
                if (!isset($exam_map[$eid_int])) {
                    $error_msg = "One of the selected subjects is not valid for today.";
                    break;
                }
                
                $exam = $exam_map[$eid_int];

                if (!in_array($exam['sem'], $allowed_sems)) {
                    $error_msg = "You are not allowed to submit feedback for semester " . htmlspecialchars($exam['sem']);
                    break;
                }

                // Check if already submitted for this specific exam
                if (isset($submitted_exams[$eid_int])) {
                    $error_msg = "You have already submitted feedback for " . htmlspecialchars($exam['subject']) . ". Please select only unsubmitted exams.";
                    break;
                }

                // Check rating & remark for this exam
                $r = $ratings[$eid_int] ?? '';
                $rm = $remarks[$eid_int] ?? '';
                if (trim($r) === '' || trim($rm) === '') {
                    $error_msg = "Rating and remark are required for all selected subjects.";
                    break;
                }

                // If all validations pass, add to valid exams
                $valid_exams[] = $eid_int;
            }

            // Only proceed if no errors and we have valid exams
            if ($error_msg === "" && !empty($valid_exams)) {
                $inserted = 0;

                foreach ($valid_exams as $eid_int) {
                    if (!isset($exam_map[$eid_int])) continue;

                    $exam = $exam_map[$eid_int];
                    $r  = (int)($ratings[$eid_int] ?? 0);
                    $rm = trim($remarks[$eid_int] ?? '');

                    // Insert one row per subject
                    $stmt = $conn->prepare("
                        INSERT INTO exam_feedback
                        (htno, name, classid, branch, year, sem, exam_id, exam_name, exam_date, subject, rating, remark, feedback_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param(
                        "ssssisssssis",
                        $student['htno'],
                        $student['name'],
                        $student['classid'],
                        $branch,
                        $year,
                        $exam['sem'],
                        $eid_int,
                        $exam['exam_name'],
                        $exam['exam_date'],
                        $exam['subject'],
                        $r,
                        $rm
                    );
                    if ($stmt->execute()) {
                        $inserted++;
                        // Add to submitted exams to prevent duplicate submission in same request
                        $submitted_exams[$eid_int] = true;
                    }
                }

                if ($inserted > 0) {
                    $success_msg = "Feedback submitted for {$inserted} subject(s). You can submit feedback for other exams if needed.";
                } else {
                    $error_msg = "No feedback saved. Please try again.";
                }
            } elseif ($error_msg === "" && empty($valid_exams)) {
                $error_msg = "No valid exams selected for feedback submission.";
            }
        }
    }
}

/* -------------------------------------------------
   STEP 1 – HTNO VERIFICATION
--------------------------------------------------*/
if (isset($_POST['check_htno'])) {
    $htno = trim($_POST['htno'] ?? '');
    $student = get_student($conn, $htno);

    if (!$student) {
        $error_msg = "Invalid roll number.";
    } 
    // 🔒 BLOCK 22 & 25 BATCH
    elseif ((int)$student['year'] == 22) {
        $error_msg = "Access denied for your batch.";
        $student = null;
    }
    else {
        $branch = get_branch_from_htno($student['htno']);
        $year   = (int)$student['year'];
        $allowed_sems = get_allowed_sems($year);
        $today_exams  = get_today_exams($conn, $branch, $allowed_sems, $today);
        $submitted_exams = get_submitted_exams($conn, $htno, $today);
    }
}

// If student exists but today_exams not built (e.g., after submit with validation error)
if ($student && empty($today_exams) && (int)$student['year'] != 25) {
    $branch = get_branch_from_htno($student['htno']);
    $year   = (int)$student['year'];
    $allowed_sems = get_allowed_sems($year);
    $today_exams  = get_today_exams($conn, $branch, $allowed_sems, $today);
    $submitted_exams = get_submitted_exams($conn, $student['htno'], $today);
}

// Extra safety: if somehow $student set but is 25 batch
if ($student && (int)$student['year'] == 22) {
    $error_msg = "Access denied for your batch.";
    $student = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Feedback System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #4361ee;
    --primary-dark: #3a56d4;
    --secondary: #7209b7;
    --success: #4cc9f0;
    --success-dark: #2bb4e0;
    --danger: #f72585;
    --warning: #f8961e;
    --light: #f8f9fa;
    --dark: #212529;
    --gray: #6c757d;
    --gray-light: #e9ecef;
    --border-radius: 8px;
    --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    color: var(--dark);
    line-height: 1.6;
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 1000px;
    margin: 0 auto;
    background: #fff;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 25px 30px;
    text-align: center;
}

.header h1 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.header p {
    opacity: 0.9;
    font-size: 0.95rem;
}

.content {
    padding: 30px;
}

.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 25px;
    margin-bottom: 25px;
    border-left: 4px solid var(--primary);
}

.card-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-title i {
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--dark);
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    min-height: 44px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: var(--success-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-outline {
    background: transparent;
    color: var(--primary);
    border: 1px solid var(--primary);
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
}

.alert {
    padding: 15px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: rgba(76, 201, 240, 0.15);
    color: #0c5460;
    border-left: 4px solid var(--success);
}

.alert-error {
    background: rgba(247, 37, 133, 0.1);
    color: #721c24;
    border-left: 4px solid var(--danger);
}

.alert i {
    font-size: 1.2rem;
}

.student-info {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.85rem;
    color: var(--gray);
    margin-bottom: 5px;
}

.info-value {
    font-weight: 500;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-info {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
}

.badge-success {
    background: rgba(76, 201, 240, 0.1);
    color: var(--success);
}

.badge-warning {
    background: rgba(248, 150, 30, 0.1);
    color: var(--warning);
}

.badge-danger {
    background: rgba(247, 37, 133, 0.1);
    color: var(--danger);
}

.table-container {
    width: 100%;
    overflow-x: auto;
}

.exam-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.exam-table th {
    background: var(--gray-light);
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: var(--dark);
}

.exam-table td {
    padding: 15px;
    border-bottom: 1px solid var(--gray-light);
}

.exam-table tr:last-child td {
    border-bottom: none;
}

.subject-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    background: var(--gray-light);
    color: var(--dark);
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 44px;
    justify-content: center;
}

.subject-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.subject-btn.active {
    background: var(--primary);
    color: white;
}

.subject-btn:disabled {
    background: var(--gray-light);
    color: var(--gray);
    cursor: not-allowed;
    transform: none;
}

.feedback-block {
    border: 1px solid var(--gray-light);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-top: 10px;
    background: #fdfdfd;
    display: none;
}

.rating-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin: 15px 0;
}

.rating-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.rating-option input[type="radio"] {
    display: none;
}

.rating-label {
    padding: 8px 16px;
    border-radius: 20px;
    background: var(--gray-light);
    transition: var(--transition);
    font-weight: 500;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rating-option input[type="radio"]:checked + .rating-label {
    background: var(--primary);
    color: white;
}

.textarea-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    resize: vertical;
    min-height: 100px;
    transition: var(--transition);
}

.textarea-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
}

.footer {
    text-align: center;
    padding: 20px;
    color: var(--gray);
    font-size: 0.85rem;
    border-top: 1px solid var(--gray-light);
}

.no-exams {
    text-align: center;
    padding: 30px;
    color: var(--gray);
}

.no-exams i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: var(--gray-light);
}

.submission-status {
    margin: 15px 0;
    padding: 15px;
    background: rgba(76, 201, 240, 0.1);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--success);
}

.access-denied {
    text-align: center;
    padding: 40px;
    color: var(--danger);
}

.access-denied i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: var(--danger);
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .container {
        margin: 5px;
        border-radius: 6px;
    }
    
    .header {
        padding: 20px 15px;
    }
    
    .header h1 {
        font-size: 1.5rem;
    }
    
    .header p {
        font-size: 0.85rem;
    }
    
    .content {
        padding: 15px;
    }
    
    .card {
        padding: 20px 15px;
        margin-bottom: 20px;
    }
    
    .card-title {
        font-size: 1.1rem;
        margin-bottom: 15px;
    }
    
    .form-control, .textarea-control {
        padding: 10px 12px;
        font-size: 16px;
    }
    
    .btn {
        width: 100%;
        padding: 14px 20px;
    }
    
    .student-info {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .exam-table {
        font-size: 0.85rem;
    }
    
    .exam-table th,
    .exam-table td {
        padding: 10px 8px;
    }
    
    /* Stack table columns for mobile */
    .exam-table thead {
        display: none;
    }
    
    .exam-table tbody tr {
        display: block;
        margin-bottom: 15px;
        border: 1px solid var(--gray-light);
        border-radius: var(--border-radius);
        padding: 15px;
    }
    
    .exam-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid var(--gray-light);
    }
    
    .exam-table tbody td:last-child {
        border-bottom: none;
    }
    
    .exam-table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--gray);
        margin-right: 10px;
        flex: 1;
    }
    
    .exam-table tbody td {
        flex: 2;
        text-align: right;
    }
    
    .subject-btn {
        width: 100%;
        padding: 10px;
    }
    
    .rating-options {
        flex-direction: column;
        gap: 8px;
    }
    
    .rating-label {
        width: 100%;
        text-align: center;
        padding: 10px;
    }
    
    .alert {
        padding: 12px 15px;
        font-size: 0.9rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    .submission-status {
        margin: 10px 0;
        padding: 12px;
        font-size: 0.9rem;
    }
    
    .feedback-block {
        padding: 15px;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    body {
        padding: 10px 5px;
        background: #fff;
    }
    
    .container {
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    .header {
        padding: 15px 10px;
    }
    
    .header h1 {
        font-size: 1.3rem;
    }
    
    .content {
        padding: 10px;
    }
    
    .card {
        padding: 15px 10px;
    }
    
    .footer {
        padding: 15px 10px;
        font-size: 0.8rem;
    }
}

/* Prevent horizontal scrolling */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

/* Improve form elements for mobile */
input, textarea, select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border-radius: var(--border-radius);
}
</style>

<script>
// Enhanced mobile functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add data labels for mobile table view
    if (window.innerWidth <= 768) {
        const tableCells = document.querySelectorAll('.exam-table td');
        const headers = ['Semester', 'Exam Name', 'Subject', 'Status', 'Action'];
        
        tableCells.forEach((cell, index) => {
            const headerIndex = index % headers.length;
            cell.setAttribute('data-label', headers[headerIndex]);
        });
    }
    
    // Better touch handling
    const touchElements = document.querySelectorAll('.subject-btn, .rating-label, .btn');
    touchElements.forEach(el => {
        el.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
        });
        
        el.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Prevent zoom on double-tap
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);
});

// Enhanced toggle function for mobile
function toggleExamBlock(examId) {
    const block = document.getElementById('block_' + examId);
    const btn = document.getElementById('btn_' + examId);
    const hidden = document.getElementById('exam_hidden_' + examId);

    if (block.style.display === 'none' || block.style.display === '') {
        block.style.display = 'block';
        btn.classList.add('active');
        btn.innerHTML = '<i class="fas fa-times"></i> Close';
        hidden.value = examId;
        
        // Scroll to the opened block on mobile
        if (window.innerWidth <= 768) {
            block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } else {
        block.style.display = 'none';
        btn.classList.remove('active');
        btn.innerHTML = '<i class="fas fa-edit"></i> Give Feedback';
        hidden.value = '';

        // Clear inputs
        document.querySelectorAll('input[name="rating[' + examId + ']"]').forEach(r => r.checked = false);
        const remarkElem = document.querySelector('textarea[name="remark[' + examId + ']"]');
        if (remarkElem) remarkElem.value = '';
    }
}

function validateFeedback() {
    const selectedInputs = document.querySelectorAll('input[name="exam_ids[]"]');
    let selectedCount = 0;

    selectedInputs.forEach(inp => {
        if (inp.value !== '') selectedCount++;
    });

    if (selectedCount === 0) {
        alert("Please select at least one subject and provide feedback.");
        return false;
    }

    // Check each selected exam has both rating and remark
    for (let inp of selectedInputs) {
        if (inp.value === '') continue;

        const id = inp.value;

        const ratingElem = document.querySelector('input[name="rating[' + id + ']"]:checked');
        const remarkElem = document.querySelector('textarea[name="remark[' + id + ']"]');

        const rating = ratingElem ? ratingElem.value : '';
        const remark = remarkElem ? remarkElem.value.trim() : '';

        if (!rating || !remark) {
            alert("Please provide both a rating and remark for all selected subjects.");
            return false;
        }
    }

    return true;
}
</script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-clipboard-check"></i> Exam Feedback System</h1>
        <p>Share your exam experience to help us improve</p>
        <p>Today: <?= htmlspecialchars($today_display) ?></p>
    </div>

    <div class="content">
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= htmlspecialchars($success_msg) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error_msg) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$student): ?>
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-id-card"></i>
                    <span>Student Verification</span>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label class="form-label" for="htno">Roll Number (HTNO)</label>
                        <input type="text" id="htno" name="htno" class="form-control" required placeholder="e.g. 24B25A4510">
                    </div>
                    <button type="submit" name="check_htno" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Proceed
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Show access denied message for 22 & 25 batch -->
            <?php if ((int)$student['year'] == 22): ?>

                <div class="card">
                    <div class="access-denied">
                        <i class="fas fa-ban"></i>
                        <h2>Access Denied</h2>
                        <p>Feedback submission is not available for your batch.</p>
                        <p>Please contact the examination cell for more information.</p>
                        <a href="?" class="btn btn-outline" style="margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-user-graduate"></i>
                        <span>Student Details</span>
                    </div>
                    <div class="student-info">
                        <div class="info-item">
                            <span class="info-label">Roll Number</span>
                            <span class="info-value"><?= htmlspecialchars($student['htno']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?= htmlspecialchars($student['name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Class</span>
                            <span class="info-value"><?= htmlspecialchars($student['classid']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Year</span>
                            <span class="info-value"><?= htmlspecialchars($student['year']) ?></span>
                        </div>
                    </div>
                    
                    <?php
                    $year = (int)$student['year'];
                    if ($year == 25) {
    echo '<div class="badge badge-info">Allowed Semester: 1-1 only</div>';
} elseif ($year == 24) {
    echo '<div class="badge badge-info">Allowed Semester: 2-1 only</div>';
} elseif ($year == 23) {
    echo '<div class="badge badge-info">Allowed Semesters: 2-1, 2-2, 3-1</div>';
} else {
    echo '<div class="badge badge-info">Allowed Semesters: 1-1, 2-1, 2-2, 3-1</div>';
}

                    
                    // Show submission status
                    $submitted_count = count($submitted_exams);
                    $total_exams = count($today_exams);
                    if ($submitted_count > 0) {
                        echo '<div class="submission-status">';
                        echo '<i class="fas fa-info-circle"></i> ';
                        echo "You have submitted feedback for {$submitted_count} out of {$total_exams} exams today.";
                        echo ' You can submit feedback for remaining exams.';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="card">
                    <div class="card-title">
                        <i class="fas fa-calendar-day"></i>
                        <span>Today's Exams</span>
                    </div>

                    <?php if (empty($today_exams)): ?>
                        <div class="no-exams">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Exams Scheduled</h3>
                            <p>There are no exams scheduled today for your branch and allowed semesters.</p>
                        </div>
                    <?php else: ?>
                        <form method="post" onsubmit="return validateFeedback();">
                            <input type="hidden" name="htno" value="<?= htmlspecialchars($student['htno']) ?>">

                            <div class="table-container">
                                <table class="exam-table">
                                    <thead>
                                        <tr>
                                            <th>Semester</th>
                                            <th>Exam Name</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($today_exams as $ex): 
                                        $examId = (int)$ex['id'];
                                        $isSubmitted = isset($submitted_exams[$examId]);
                                    ?>
                                        <tr>
                                            <td data-label="Semester"><?= htmlspecialchars($ex['sem']) ?></td>
                                            <td data-label="Exam Name"><?= htmlspecialchars($ex['exam_name'] ?: '-') ?></td>
                                            <td data-label="Subject"><?= htmlspecialchars($ex['subject']) ?></td>
                                            <td data-label="Status">
                                                <?php if ($isSubmitted): ?>
                                                    <span class="badge badge-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Action">
                                                <?php if (!$isSubmitted): ?>
                                                    <button type="button"
                                                            id="btn_<?= $examId ?>"
                                                            class="subject-btn"
                                                            onclick="toggleExamBlock(<?= $examId ?>)">
                                                        <i class="fas fa-edit"></i> Give Feedback
                                                    </button>
                                                    <input type="hidden" name="exam_ids[]" id="exam_hidden_<?= $examId ?>" value="">
                                                <?php else: ?>
                                                    <button type="button" class="subject-btn" disabled>
                                                        <i class="fas fa-check"></i> Already Submitted
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (!$isSubmitted): ?>
                                        <tr>
                                            <td colspan="5">
                                                <div id="block_<?= $examId ?>" class="feedback-block">
                                                    <h4><?= htmlspecialchars($ex['subject']) ?> (Sem: <?= htmlspecialchars($ex['sem']) ?>)</h4>
                                                    
                                                    <!-- CONFIDENCE QUESTION -->
                                                    <div class="form-group">
                                                        <label class="form-label">How confident did you feel while writing the exam?</label>
                                                        <div class="rating-options">
                                                            <label class="rating-option">
                                                                <input type="radio" name="rating[<?= $examId ?>]" value="5">
                                                                <span class="rating-label">Very Confident</span>
                                                            </label>
                                                            <label class="rating-option">
                                                                <input type="radio" name="rating[<?= $examId ?>]" value="4">
                                                                <span class="rating-label">Confident</span>
                                                            </label>
                                                            <label class="rating-option">
                                                                <input type="radio" name="rating[<?= $examId ?>]" value="3">
                                                                <span class="rating-label">Moderate</span>
                                                            </label>
                                                            <label class="rating-option">
                                                                <input type="radio" name="rating[<?= $examId ?>]" value="2">
                                                                <span class="rating-label">Low Confidence</span>
                                                            </label>
                                                            <label class="rating-option">
                                                                <input type="radio" name="rating[<?= $examId ?>]" value="1">
                                                                <span class="rating-label">No Confidence</span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <!-- REMARK -->
                                                    <div class="form-group">
                                                        <label class="form-label">Additional Comments</label>
                                                        <textarea name="remark[<?= $examId ?>]" class="textarea-control" placeholder="Please share any additional feedback about this exam..."></textarea>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php 
                            $pending_exams = array_filter($today_exams, function($ex) use ($submitted_exams) {
                                return !isset($submitted_exams[$ex['id']]);
                            });
                            if (!empty($pending_exams)): 
                            ?>
                                <button type="submit" name="submit_feedback" class="btn btn-success">
                                    <i class="fas fa-paper-plane"></i> Submit Feedback for Selected Exams
                                </button>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <div>You have successfully submitted feedback for all exams today. Thank you!</div>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>Exam Feedback System &copy; <?= date('Y') ?> - All rights reserved</p>
    </div>
</div>
</body>
</html>