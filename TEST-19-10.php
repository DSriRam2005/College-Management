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
$TEST_ID = 13;

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
/* -------------------------
   Modernized Perfect CSS
-------------------------- */
:root {
  --primary: #004aad;
  --primary-light: #0073ff;
  --bg: #f4f6fa;
  --white: #ffffff;
  --text-dark: #222;
  --text-light: #555;
  --border: #d0d7e1;
  --success: #e8fff1;
  --error: #ffecec;
}
body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  margin: 0;
  padding: 0;
  color: var(--text-dark);
}
.main-container {
  display: flex;
  height: 100vh;
  width: 100%;
}
.pdf-container {
  flex: 0.5;
  background: #eef2f8;
  border-right: 3px solid var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
}
.pdf-container iframe {
  width: 100%;
  height: 100%;
  border: none;
}
.container {
  flex: 0.5;
  background: var(--white);
  padding: 40px;
  overflow-y: auto;
  height: 100vh;
  box-shadow: inset 0 0 10px rgba(0,0,0,0.05);
  border-left: 1px solid #e1e4eb;
}
h1 {
  text-align: center;
  color: var(--primary);
  font-size: 1.9em;
  letter-spacing: 0.5px;
  margin-bottom: 25px;
}
h2, h3 {
  color: var(--text-dark);
  border-left: 5px solid var(--primary);
  padding-left: 10px;
}
form {
  margin-top: 20px;
}
input[type="text"] {
  padding: 12px;
  width: 70%;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 16px;
  transition: 0.3s;
}
input[type="text"]:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 4px var(--primary-light);
}
input[type="submit"], button {
  padding: 12px 20px;
  background: var(--primary);
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
  margin-left: 5px;
}
input[type="submit"]:hover, button:hover {
  background: var(--primary-light);
}
.message {
  margin-top: 15px;
  padding: 15px 18px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.success {
  background: var(--success);
  color: #0f7000;
  border-left: 5px solid #00b300;
}
.error {
  background: var(--error);
  color: #a80000;
  border-left: 5px solid #ff3b3b;
}
.section {
  background: #f8faff;
  border: 1px solid var(--border);
  padding: 18px;
  border-radius: 10px;
  margin-bottom: 25px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}
.question {
  background: var(--white);
  border: 1px solid #ddd;
  border-radius: 8px;
  margin: 10px 0;
  padding: 10px 12px;
  transition: 0.2s;
}
.question:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
label {
  margin-right: 12px;
  color: var(--text-light);
  font-size: 15px;
}
input[type="radio"] {
  margin-right: 6px;
}
.report-table {
  border-collapse: collapse;
  width: 100%;
  margin-top: 20px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.report-table th, .report-table td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: center;
  font-size: 15px;
}
.report-table th {
  background: var(--primary);
  color: #fff;
}
.report-table tr:nth-child(even) {
  background: #f9f9f9;
}
.report-table tr:last-child {
  font-weight: bold;
  background: #e6f0ff;
}
@media (max-width: 900px) {
  .main-container {
    flex-direction: column;
  }
  .pdf-container {
    height: 45vh;
    border-right: none;
    border-bottom: 3px solid var(--primary);
  }
  .container {
    height: 55vh;
    overflow-y: scroll;
    padding: 25px;
  }
  input[type="text"] {
    width: 100%;
    margin-bottom: 10px;
  }
  input[type="submit"], button {
    width: 100%;
    margin-top: 8px;
  }
  h1 {
    font-size: 1.5em;
  }
}
</style>
</head>
<body>
<div class="main-container">

  <?php if(!$student || $marks_report): ?>
  <div class="pdf-container">
      <h2 style="color:#555;">📄 Question paper will appear here once the test starts</h2>
  </div>
  <?php else: ?>
  <div class="pdf-container">
      <iframe src="Infosys Test 5.pdf"></iframe>
  </div>
  <?php endif; ?>

  <div class="container">
      <h1>OMR Test - Test ID <?php echo $TEST_ID; ?></h1>

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
          $color = "#00cc00"; $remark = "Excellent! Keep it up 👏 (Appreciation)";
      } elseif ($percentage >= 64.6) {
          $color = "#ff9900"; $remark = "Good! Can improve further 💪 (Improvement)";
      } else {
          $color = "#ff3333"; $remark = "Needs more practice 📘 (Needs Improvement)";
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
</div>
<script>
let testStarted = <?php echo isset($student) && !$marks_report ? 'true' : 'false'; ?>;

if (testStarted) {
  let warningShown = false;
  const pdfFrame = document.querySelector(".pdf-container iframe");

  // 🧱 Prevent leaving or closing the page before submission
  window.addEventListener("beforeunload", function (e) {
    e.preventDefault();
    e.returnValue = "⚠️ You cannot leave or refresh the test before submission.";
  });

  // 🚫 Detect tab switch
  document.addEventListener("visibilitychange", function() {
    if (document.hidden && !warningShown) {
      warningShown = true;
      alert("❌ Tab switch detected! You cannot switch tabs during the test.");
      location.reload(); // optional: reload or redirect
    }
  });

  // 🚫 Detect focus loss (like alt+tab or window minimize)
  window.addEventListener("blur", function() {
    const active = document.activeElement;
    if (pdfFrame && active === pdfFrame) return; // ✅ ignore PDF focus
    if (!warningShown) {
      warningShown = true;
      alert("❌ Window focus lost! You cannot switch apps or minimize.");
      location.reload(); // optional
    }
  });

  // 🧱 Block keyboard shortcuts for refresh or new tab
  document.addEventListener("keydown", function (e) {
    // Prevent F5, Ctrl+R, Ctrl+W, Ctrl+T, Alt+Tab combinations
    if (
      e.key === "F5" || 
      (e.ctrlKey && (e.key === "r" || e.key === "R" || e.key === "w" || e.key === "t"))
    ) {
      e.preventDefault();
      alert("⚠️ Refreshing or closing tabs is disabled during the test.");
    }
  });

  // 🧱 Optional: Prevent right-click or copying
  document.addEventListener("contextmenu", e => e.preventDefault());
}
</script>


</body>
</html>
