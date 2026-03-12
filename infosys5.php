<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// ---------------------------
// Step 0: Check if test is started
// ---------------------------
$testCategory = 'TEST2';
$checkTest = $conn->query("SELECT start_stop FROM `start` WHERE categoryname='$testCategory'");
if ($checkTest && $checkTest->num_rows > 0) {
    $status = $checkTest->fetch_assoc()['start_stop'];
    if ($status != 1) {
        die('<h2 style="text-align:center; color:red; margin-top:50px;">⛔ The test has not started yet. Please wait for it to start.</h2>');
    }
} else {
    die('<h2 style="text-align:center; color:red; margin-top:50px;">⛔ Test category not found!</h2>');
}

// ---------------------------
// Step 1: Initialize variables
// ---------------------------
$student = null;
$message = "";
$marks_report = null;
$grand_total = 0;
$TEST_ID = 58;

// ---------------------------
// Step 2: Fetch student details
// ---------------------------
if (isset($_POST['fetch_student'])) {
    $htno = $conn->real_escape_string($_POST['htno']);
    $sql = "SELECT htno, name, teamid FROM STUDENTS WHERE htno='$htno'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        // Check if student already submitted
        $check = $conn->query("SELECT COUNT(*) as cnt FROM student_answers WHERE htno='$htno' AND test_id=$TEST_ID");
        $count = $check->fetch_assoc()['cnt'];

        if ($count > 0) {
            $message = "⚠️ Student already submitted this test. Showing marks report.";
            $student = $conn->query("SELECT htno, name, teamid FROM STUDENTS WHERE htno='$htno'")->fetch_assoc();

            // Fetch marks report
            $result_marks = $conn->query("
                SELECT ts.section_name, SUM(sm.marks_obtained) AS total_marks
                FROM student_marks sm
                JOIN test_sections ts ON sm.section_id = ts.section_id
                WHERE sm.htno='$htno' AND sm.test_id=$TEST_ID
                GROUP BY sm.section_id
            ");
            $marks_report = [];
            $grand_total = 0;
            while ($row = $result_marks->fetch_assoc()) {
                $marks_report[] = $row;
                $grand_total += $row['total_marks'];
            }
        }
    } else {
        $message = "❌ Student not found!";
    }
}

// ---------------------------
// Step 3: Save student answers
// ---------------------------
if (isset($_POST['submit_answers'])) {
    $htno = $conn->real_escape_string($_POST['htno']);
    $answers = $_POST['answers'];

    $check = $conn->query("SELECT COUNT(*) AS cnt FROM student_answers WHERE htno='$htno' AND test_id=$TEST_ID");
    if ($check && $check->fetch_assoc()['cnt'] > 0) {
        $message = "⚠️ You have already submitted this test. Showing marks report.";
        $student = $conn->query("SELECT htno, name, teamid FROM STUDENTS WHERE htno='$htno'")->fetch_assoc();

        // Fetch marks report
        $result_marks = $conn->query("
            SELECT ts.section_name, SUM(sm.marks_obtained) AS total_marks
            FROM student_marks sm
            JOIN test_sections ts ON sm.section_id = ts.section_id
            WHERE sm.htno='$htno' AND sm.test_id=$TEST_ID
            GROUP BY sm.section_id
        ");
        $marks_report = [];
        $grand_total = 0;
        while ($row = $result_marks->fetch_assoc()) {
            $marks_report[] = $row;
            $grand_total += $row['total_marks'];
        }

    } else {
        $q_total = $conn->query("SELECT COUNT(*) as cnt FROM correct_answers WHERE test_id=$TEST_ID");
        $total_questions = $q_total->fetch_assoc()['cnt'];

        if (count($answers) != $total_questions) {
            $message = "⚠️ Please answer all questions before submitting.";
            $student = ['htno' => $htno, 'name' => $_POST['name'], 'teamid' => $_POST['teamid']];
        } else {
            $section_marks = [];
            foreach ($answers as $key => $selected_option) {
                list($section_id, $question_no) = explode("_", $key);
                $res = $conn->query("SELECT marks, correct_option FROM correct_answers 
                                     WHERE test_id=$TEST_ID AND section_id=" . intval($section_id) . " AND question_no=" . intval($question_no));
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $marks_obtained = ($row['correct_option'] == $selected_option) ? $row['marks'] : 0;
                    $conn->query("
                        INSERT INTO student_answers (htno, test_id, section_id, question_no, selected_option)
                        VALUES ('$htno', $TEST_ID, $section_id, $question_no, '$selected_option')
                        ON DUPLICATE KEY UPDATE selected_option=VALUES(selected_option)
                    ");
                    if (!isset($section_marks[$section_id])) $section_marks[$section_id] = 0;
                    $section_marks[$section_id] += $marks_obtained;
                }
            }

            foreach ($section_marks as $section_id => $total_marks) {
                $conn->query("
                    INSERT INTO student_marks (htno, test_id, section_id, marks_obtained)
                    VALUES ('$htno', $TEST_ID, $section_id, $total_marks)
                    ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained)
                ");
            }

            // Fetch marks report
            $student = $conn->query("SELECT htno, name, teamid FROM STUDENTS WHERE htno='$htno'")->fetch_assoc();
            $result_marks = $conn->query("
                SELECT ts.section_name, SUM(sm.marks_obtained) AS total_marks
                FROM student_marks sm
                JOIN test_sections ts ON sm.section_id = ts.section_id
                WHERE sm.htno='$htno' AND sm.test_id=$TEST_ID
                GROUP BY sm.section_id
            ");
            $marks_report = [];
            $grand_total = 0;
            while ($row = $result_marks->fetch_assoc()) {
                $marks_report[] = $row;
                $grand_total += $row['total_marks'];
            }

            $message = "✅ Answers submitted successfully!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OMR Test - Test ID <?php echo $TEST_ID; ?></title>
<style>
* {box-sizing:border-box; font-family:Segoe UI, Roboto, Arial, sans-serif;}
body {background:#f6f8fb; margin:0; padding:0;}
.container {max-width:900px; margin:30px auto; background:#fff; padding:25px 30px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1);}
h1 {text-align:center; color:#004aad; font-size:1.8em; margin-bottom:15px;}
h2,h3 {color:#222;}
form {margin-top:20px;}
input[type="text"] {padding:10px; width:65%; border:1px solid #ccc; border-radius:6px; font-size:15px;}
input[type="submit"], button {padding:10px 18px; background:#004aad; color:#fff; border:none; border-radius:6px; font-size:15px; cursor:pointer; transition: all 0.3s ease;}
input[type="submit"]:hover, button:hover {background:#0073ff;}
.message {margin-top:15px; padding:12px 15px; border-radius:6px; font-weight:600; font-size:15px;}
.success {background:#e8fff1; color:#0f7000; border-left:5px solid #00b300;}
.error {background:#ffecec; color:#a80000; border-left:5px solid #ff3b3b;}
.section {background:#f3f6fb; border:1px solid #d0d7e1; padding:15px; border-radius:10px; margin-bottom:20px;}
.question {background:#fff; border:1px solid #ddd; border-radius:8px; margin:8px 0; padding:8px 10px;}
label {margin-right:10px; color:#333;}
input[type="radio"] {margin-right:6px;}
.report-table {border-collapse:collapse; width:100%; margin-top:20px; border-radius:8px; overflow:hidden;}
.report-table th, .report-table td {border:1px solid #ddd; padding:10px; text-align:center;}
.report-table th {background:#004aad; color:#fff;}
.report-table tr:nth-child(even) {background:#f9f9f9;}
.report-table tr:last-child {font-weight:bold; background:#e6f0ff;}
.report-table tr[style] td {
    border: 3px solid #fff !important;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}
@media (max-width:768px) {
    .container {width:90%; padding:20px;}
    input[type="text"] {width:100%; margin-bottom:10px;}
    input[type="submit"], button {width:100%; margin-top:5px;}
    .question label {display:inline-block; margin:4px 5px;}
    h1 {font-size:1.4em;}
}
</style>
</head>
<body>
<div class="container">
<h1>OMR Test - Test ID <?php echo $TEST_ID; ?></h1>

<!-- Student Fetch Form -->
<?php if(!$marks_report && !$student): ?>
<form method="post">
    <input type="text" name="htno" placeholder="Enter HT Number" required>
    <input type="submit" name="fetch_student" value="Fetch Student">
</form>
<?php endif; ?>

<?php if($message): ?>
<div class="message <?php echo (strpos($message,'✅')!==false?'success':'error'); ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Test Form -->
<?php if($student && !$marks_report): ?>
<h2>Student Details</h2>
<p><strong>HT No:</strong> <?php echo htmlspecialchars($student['htno']); ?></p>
<p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
<p><strong>Team ID:</strong> <?php echo htmlspecialchars($student['teamid']); ?></p>

<form method="post">
    <input type="hidden" name="htno" value="<?php echo $student['htno']; ?>">
    <input type="hidden" name="name" value="<?php echo htmlspecialchars($student['name']); ?>">
    <input type="hidden" name="teamid" value="<?php echo htmlspecialchars($student['teamid']); ?>">

    <?php
    $sections = $conn->query("SELECT section_id, section_name FROM test_sections WHERE test_id=$TEST_ID ORDER BY section_id");
    while($section = $sections->fetch_assoc()):
        $section_id = $section['section_id'];
    ?>
    <div class="section">
        <h3><?php echo htmlspecialchars($section['section_name']); ?></h3>
        <?php
        $questions = $conn->query("SELECT question_no FROM correct_answers WHERE test_id=$TEST_ID AND section_id=$section_id ORDER BY question_no");
        while($q = $questions->fetch_assoc()):
            $qno = $q['question_no'];
        ?>
        <div class="question">
            <label><strong>Q<?php echo $qno; ?>:</strong></label>
            <?php foreach(['A','B','C','D'] as $opt): ?>
                <label><input type="radio" name="answers[<?php echo $section_id.'_'.$qno; ?>]" value="<?php echo $opt; ?>" required> <?php echo $opt; ?></label>
            <?php endforeach; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endwhile; ?>

    <input type="submit" name="submit_answers" value="Submit Answers">
</form>
<?php endif; ?>

<!-- Marks Report -->
<?php if($marks_report): ?>
<h2>Student Details</h2>
<p><strong>HT No:</strong> <?php echo htmlspecialchars($student['htno']); ?></p>
<p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
<p><strong>Team ID:</strong> <?php echo htmlspecialchars($student['teamid'] ?? ''); ?></p>

<h2>Marks Report</h2>
<?php
$total_marks_possible = 0;
$total_query = $conn->query("SELECT SUM(marks) AS total FROM correct_answers WHERE test_id=$TEST_ID");
if ($total_query && $total_query->num_rows > 0) {
    $total_marks_possible = $total_query->fetch_assoc()['total'];
}
$percentage = ($total_marks_possible > 0) ? ($grand_total / $total_marks_possible) * 100 : 0;

if ($percentage >= 74.6) {
    $color = "#00cc00"; // Green
    $remark = "Excellent! Keep it up 👏 (Appreciation)";
} elseif ($percentage >= 64.6) {
    $color = "#ff9900"; // Orange
    $remark = "Good! Can improve further 💪 (Improvement)";
} else {
    $color = "#ff3333"; // Red
    $remark = "Needs more practice 📘 (Needs Improvement)";
}
?>

<table class="report-table">
    <tr><th>Section</th><th>Marks Obtained</th></tr>
    <?php foreach($marks_report as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['section_name']); ?></td>
        <td><?php echo $row['total_marks']; ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td><strong>Total</strong></td>
        <td><strong><?php echo $grand_total; ?> / <?php echo $total_marks_possible; ?></strong></td>
    </tr>
    <tr style="background:<?php echo $color; ?>; color:#fff; font-weight:bold; font-size:16px;">
        <td>Percentage</td>
        <td><?php echo number_format($percentage, 2); ?>%</td>
    </tr>
    <tr style="background:<?php echo $color; ?>; color:#fff; font-weight:bold; font-size:16px;">
        <td colspan="2"><?php echo $remark; ?></td>
    </tr>
</table>
<?php endif; ?>

</div>
</body>
</html>
