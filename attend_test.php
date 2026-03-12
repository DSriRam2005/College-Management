<?php
// attend_test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php'; // ensure $conn is a valid mysqli connection

// ---------------------------
// Helpers
// ---------------------------
function e($s) {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}


// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// ---------------------------
// Determine test id (GET or default)
$TEST_ID = isset($_GET['test_id']) ? intval($_GET['test_id']) : 13;
$testCategory = 'TEST2'; // adjust or map dynamically if needed

// Fetch test name
$TEST_NAME = "Test"; // fallback default
$stmtTN = $conn->prepare("SELECT test_name FROM tests WHERE test_id = ? LIMIT 1");
$stmtTN->bind_param("i", $TEST_ID);
$stmtTN->execute();
$stmtTN->store_result();
if($stmtTN->num_rows > 0){
    $stmtTN->bind_result($TEST_NAME);
    $stmtTN->fetch();
}
$stmtTN->close();


// ---------------------------
// Step 0: Check if test is started (use store_result + bind_result)
$checkStmt = $conn->prepare("SELECT start_stop FROM `start` WHERE categoryname = ?");
if (!$checkStmt) {
    die("DB prepare error: " . $conn->error);
}
$checkStmt->bind_param("s", $testCategory);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->bind_result($status);
    $checkStmt->fetch();
    $checkStmt->close();
    if ($status != 1) {
        echo '<h2 style="text-align:center; color:red; margin-top:50px;">⛔ The test has not started yet. Please wait for it to start.</h2>';
        exit;
    }
} else {
    $checkStmt->close();
    echo '<h2 style="text-align:center; color:red; margin-top:50px;">⛔ Test category not found!</h2>';
    exit;
}

// ---------------------------
// Initialize
$student = null;
$message = "";
$marks_report = null;
$grand_total = 0;

// ---------------------------
// Helper: fetch student by HT number (store_result + bind_result)
function fetch_student_by_htno($conn, $htno) {
    $stmt = $conn->prepare("SELECT htno, name, teamid FROM STUDENTS WHERE htno = ?");
    if (!$stmt) return null;
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        return null;
    }
    $stmt->bind_result($r_htno, $r_name, $r_teamid);
    $stmt->fetch();
    $stmt->close();
    return ['htno' => $r_htno, 'name' => $r_name, 'teamid' => $r_teamid];
}

// Helper: check submission count
function student_submission_count($conn, $htno, $test_id) {
    $s = $conn->prepare("SELECT COUNT(*) AS cnt FROM student_answers WHERE htno = ? AND test_id = ?");
    $s->bind_param("si", $htno, $test_id);
    $s->execute();
    $s->store_result();
    $s->bind_result($cnt);
    $s->fetch();
    $s->close();
    return intval($cnt);
}

// Helper: fetch marks report (per-section)
function fetch_marks_report($conn, $htno, $test_id, &$grand_total_out) {
    $grand_total_out = 0;
    $arr = [];
    $q = $conn->prepare("
        SELECT ts.section_name, SUM(sm.marks_obtained) AS total_marks
        FROM student_marks sm
        JOIN test_sections ts ON sm.section_id = ts.section_id
        WHERE sm.htno = ? AND sm.test_id = ?
        GROUP BY sm.section_id
    ");
    $q->bind_param("si", $htno, $test_id);
    $q->execute();
    $q->store_result();
    if ($q->num_rows > 0) {
        $q->bind_result($section_name, $total_marks);
        while ($q->fetch()) {
            $arr[] = ['section_name' => $section_name, 'total_marks' => $total_marks];
            $grand_total_out += floatval($total_marks);
        }
    }
    $q->close();
    return $arr;
}

// ---------------------------
// Step 2: Handle fetch student action
if (isset($_POST['fetch_student'])) {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = "❌ Invalid CSRF token.";
    } else {
        $htno = trim($_POST['htno'] ?? '');
        if ($htno === '') {
            $message = "❌ Please enter HT number.";
        } else {
            $student = fetch_student_by_htno($conn, $htno);
            if ($student) {
                $count = student_submission_count($conn, $htno, $TEST_ID);
                if ($count > 0) {
                    $message = "⚠️ Student already submitted this test. Showing marks report.";
                    $student = fetch_student_by_htno($conn, $htno);
                    $marks_report = fetch_marks_report($conn, $htno, $TEST_ID, $grand_total);
                }
            } else {
                $message = "❌ Student not found!";
            }
        }
    }
}

// ---------------------------
// Step 3: Handle submit answers
if (isset($_POST['submit_answers'])) {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $message = "❌ Invalid CSRF token.";
    } else {
        $htno = trim($_POST['htno'] ?? '');
        $answers = $_POST['answers'] ?? [];

        if ($htno === '') {
            $message = "❌ Missing HT number.";
        } else {
            $already = student_submission_count($conn, $htno, $TEST_ID);
            if ($already > 0) {
                $message = "⚠️ You have already submitted this test. Showing marks report.";
                $student = fetch_student_by_htno($conn, $htno);
                $marks_report = fetch_marks_report($conn, $htno, $TEST_ID, $grand_total);
            } else {
                // count total questions
                $ct = $conn->prepare("SELECT COUNT(*) AS cnt FROM correct_answers WHERE test_id = ?");
                $ct->bind_param("i", $TEST_ID);
                $ct->execute();
                $ct->store_result();
                $ct->bind_result($total_questions);
                $ct->fetch();
                $ct->close();
                $total_questions = intval($total_questions ?? 0);

                if ($total_questions == 0) {
                    $message = "❌ No questions configured for this test.";
                } elseif (count($answers) != $total_questions) {
                    $message = "⚠️ Please answer all questions before submitting.";
                    // repopulate student to show form again
                    $student = ['htno' => $htno, 'name' => $_POST['name'] ?? '', 'teamid' => $_POST['teamid'] ?? ''];
                } else {
                    // Prepare statements used inside loop
                    $getCorrect = $conn->prepare("SELECT marks, correct_option FROM correct_answers WHERE test_id = ? AND section_id = ? AND question_no = ?");
                    if (!$getCorrect) {
                        $message = "❌ DB error (prepare).";
                    } else {
                        $insAnswer = $conn->prepare("
                            INSERT INTO student_answers (htno, test_id, section_id, question_no, selected_option)
                            VALUES (?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option)
                        ");
                        $insMarks = $conn->prepare("
                            INSERT INTO student_marks (htno, test_id, section_id, marks_obtained)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained)
                        ");
                        if (!$insAnswer || !$insMarks) {
                            $message = "❌ DB error (prepare insert).";
                        } else {
                            // Begin transaction
                            $conn->begin_transaction();
                            try {
                                // bind params for repeated use
                                $getCorrect->bind_param("iii", $TEST_ID, $section_id_lookup, $question_no_lookup);
                                $insAnswer->bind_param("siiis", $htno_param, $testid_param, $section_id_param, $question_no_param, $selected_option_param);
                                $insMarks->bind_param("siid", $htno_mark_param, $testid_mark_param, $section_id_mark_param, $marks_obtained_param);

                                // constants
                                $htno_param = $htno;
                                $testid_param = $TEST_ID;
                                $htno_mark_param = $htno;
                                $testid_mark_param = $TEST_ID;

                                $section_marks = [];

                                // iterate answers
                                foreach ($answers as $key => $selected_option_raw) {
                                    $parts = explode("_", $key);
                                    if (count($parts) !== 2) continue;
                                    $section_id_lookup = intval($parts[0]);
                                    $question_no_lookup = intval($parts[1]);
                                    $selected_option = strtoupper(substr(trim($selected_option_raw), 0, 1));
                                    if (!in_array($selected_option, ['A','B','C','D'])) $selected_option = 'A'; // fallback
                                    $selected_option_param = $selected_option;

                                    // fetch correct answer
                                    $getCorrect->execute();
                                    $getCorrect->store_result();
                                    if ($getCorrect->num_rows > 0) {
                                        $getCorrect->bind_result($correct_marks, $correct_option);
                                        $getCorrect->fetch();
                                        $marks_obtained = ($correct_option === $selected_option) ? floatval($correct_marks) : 0.0;
                                        // insert student answer
                                        $section_id_param = $section_id_lookup;
                                        $question_no_param = $question_no_lookup;
                                        $insAnswer->execute();
                                        if ($insAnswer->errno) {
                                            throw new Exception("Answer insert failed: " . $insAnswer->error);
                                        }
                                        // accumulate marks per section
                                        if (!isset($section_marks[$section_id_lookup])) $section_marks[$section_id_lookup] = 0.0;
                                        $section_marks[$section_id_lookup] += $marks_obtained;
                                    } else {
                                        // question not found - treat as zero, continue
                                    }
                                    // free statements results for next loop
                                    $getCorrect->free_result();
                                }

                                // insert/update section marks
                                foreach ($section_marks as $sid => $total_marks) {
                                    $section_id_mark_param = $sid;
                                    $marks_obtained_param = $total_marks;
                                    $insMarks->execute();
                                    if ($insMarks->errno) {
                                        throw new Exception("Marks insert failed: " . $insMarks->error);
                                    }
                                }

                                // commit
                                $conn->commit();

                                // close prepared statements
                                $getCorrect->close();
                                $insAnswer->close();
                                $insMarks->close();

                                // fetch marks report to show
                                $student = fetch_student_by_htno($conn, $htno);
                                $marks_report = fetch_marks_report($conn, $htno, $TEST_ID, $grand_total);

                                $message = "✅ Answers submitted successfully!";
                            } catch (Exception $ex) {
                                $conn->rollback();
                                error_log("Submit error: " . $ex->getMessage());
                                $message = "❌ An internal error occurred while saving answers. Please contact admin.";
                                // ensure we close prepared stmts
                                if (isset($getCorrect) && $getCorrect) $getCorrect->close();
                                if (isset($insAnswer) && $insAnswer) $insAnswer->close();
                                if (isset($insMarks) && $insMarks) $insMarks->close();
                            }
                        }
                    }
                }
            }
        }
    }
}

// ---------------------------
// Prepare sections to render when showing the question form
$sections_for_render = [];
if ($student && !$marks_report) {
    $secStmt = $conn->prepare("SELECT section_id, section_name FROM test_sections WHERE test_id = ? ORDER BY section_id");
    $secStmt->bind_param("i", $TEST_ID);
    $secStmt->execute();
    $secStmt->store_result();
    if ($secStmt->num_rows > 0) {
        $secStmt->bind_result($s_section_id, $s_section_name);
        while ($secStmt->fetch()) {
            $sections_for_render[] = ['section_id' => $s_section_id, 'section_name' => $s_section_name];
        }
    }
    $secStmt->close();
}

// --- Fetch question paper path for this test_id
$pdfSrc = null;
$questionPath = null;

$qpStmt = $conn->prepare("SELECT question_paper_path FROM tests WHERE test_id = ? LIMIT 1");
if ($qpStmt) {
    $qpStmt->bind_param("i", $TEST_ID);
    $qpStmt->execute();
    $qpStmt->store_result();
    if ($qpStmt->num_rows > 0) {
        $qpStmt->bind_result($questionPath);
        $qpStmt->fetch();
    }
    $qpStmt->close();
}

// If questionPath is empty -> null
$questionPath = trim((string)($questionPath ?? ''));

// CASE A: If your DB stores a filesystem path or a relative URL (e.g. "uploads/papers/abc.pdf")
if ($questionPath !== '') {
    // Prefer serving via small endpoint for security/headers if path is outside webroot or needs auth:
    // Option 1 (simple): If path is already a publicly accessible URL or relative path inside webroot:
    if (filter_var($questionPath, FILTER_VALIDATE_URL)) {
        $pdfSrc = $questionPath; // it's already a URL
    } else {
        // Normalize to prevent directory-traversal attempts
        $safe = str_replace(["\0", "../", "..\\"], '', $questionPath);
        // If file exists on disk relative to project root:
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($safe, "/\\");
        if (is_file($fullPath) && is_readable($fullPath)) {
            // Use relative URL so iframe can load it directly
            // If your project is served from the same folder, convert to path relative to web root:
            $pdfSrc = '/' . ltrim($safe, "/\\"); // adjust if your webroot differs
        } else {
            // Fall back to serve_file.php which reads from DB or handles non-public files
            $pdfSrc = "serve_file.php?test_id=" . urlencode($TEST_ID);
        }
    }
} else {
    // No path in DB: use endpoint (serve_file.php) or show placeholder
    $pdfSrc = "serve_file.php?test_id=" . urlencode($TEST_ID);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OMR Test - <?php echo e($TEST_NAME); ?></title>
<style>
/* same CSS as you wanted (modernized) */
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
label { margin-right: 12px; color: var(--text-light); font-size: 15px; }
input[type="radio"] { margin-right: 6px; }
.report-table { border-collapse: collapse; width: 100%; margin-top: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.report-table th, .report-table td { border: 1px solid #ddd; padding: 12px; text-align: center; font-size: 15px; }
.report-table th { background: var(--primary); color: #fff; }
.report-table tr:nth-child(even) { background: #f9f9f9; }
.report-table tr:last-child { font-weight: bold; background: #e6f0ff; }
@media (max-width: 900px) {
  .main-container { flex-direction: column; }
  .pdf-container { height: 45vh; border-right: none; border-bottom: 3px solid var(--primary); }
  .container { height: 55vh; overflow-y: scroll; padding: 25px; }
  input[type="text"] { width: 100%; margin-bottom: 10px; }
  input[type="submit"], button { width: 100%; margin-top: 8px; }
  h1 { font-size: 1.5em; }
}

/* Violation modal styles */
#violationModal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  background: rgba(0,0,0,0.55);
}
#violationModal .box {
  background: #fff;
  padding: 24px;
  border-radius: 10px;
  width: 90%;
  max-width: 520px;
  text-align: center;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
#violationModal h3 { margin: 0 0 10px; color: #c62828; }
#violationModal p { margin: 8px 0 12px; color: #333; font-size: 16px; }
#violationModal .count { font-weight: 700; font-size: 20px; color: #111; margin-top: 8px; }
#violationModal button { margin-top: 12px; padding: 8px 14px; border-radius: 6px; border: none; background: #004aad; color: #fff; cursor: pointer; }
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
      <!-- Replace with dynamic path from tests.question_paper_path if you store it -->
      <?php if (!empty($pdfSrc)): ?>
  <iframe src="<?php echo e($pdfSrc); ?>"></iframe>
<?php else: ?>
  <div style="padding:20px; color:#666; text-align:center;">📄 Question paper not available.</div>
<?php endif; ?>

  </div>
  <?php endif; ?>

  <div class="container">
      <h1>OMR Test - <?php echo e($TEST_NAME); ?></h1>

      <?php if(!$marks_report && !$student): ?>
      <form method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
          <input type="text" name="htno" placeholder="Enter HT Number" required>
          <input type="submit" name="fetch_student" value="Fetch Student">
      </form>
      <?php endif; ?>

      <?php if($message): ?>
      <div class="message <?php echo (strpos($message,'✅')!==false?'success':'error'); ?>">
          <?php echo e($message); ?>
      </div>
      <?php endif; ?>

      <?php if($student && !$marks_report): ?>
      <h2>Student Details</h2>
      <p><strong>HT No:</strong> <?php echo e($student['htno']); ?></p>
      <p><strong>Name:</strong> <?php echo e($student['name']); ?></p>
      <p><strong>Team ID:</strong> <?php echo e($student['teamid']); ?></p>

      <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
          <input type="hidden" name="htno" value="<?php echo e($student['htno']); ?>">
          <input type="hidden" name="name" value="<?php echo e($student['name']); ?>">
          <input type="hidden" name="teamid" value="<?php echo e($student['teamid']); ?>">

          <?php
          // Prepare question stmt once and reuse for each section
          $qStmt = $conn->prepare("SELECT question_no FROM correct_answers WHERE test_id = ? AND section_id = ? ORDER BY question_no");
          foreach ($sections_for_render as $section):
              $section_id = intval($section['section_id']);
          ?>
          <div class="section">
              <h3><?php echo e($section['section_name']); ?></h3>
              <?php
              $qStmt->bind_param("ii", $TEST_ID, $section_id);
              $qStmt->execute();
              $qStmt->store_result();
              if ($qStmt->num_rows > 0) {
                  $qStmt->bind_result($q_question_no);
                  while ($qStmt->fetch()):
                      $qno = intval($q_question_no);
              ?>
              <div class="question">
                  <label><strong>Q<?php echo $qno; ?>:</strong></label>
                  <?php foreach(['A','B','C','D'] as $opt): ?>
                      <label><input type="radio" name="answers[<?php echo $section_id . '_' . $qno; ?>]" value="<?php echo $opt; ?>" required> <?php echo $opt; ?></label>
                  <?php endforeach; ?>
              </div>
              <?php
                  endwhile;
              }
              $qStmt->free_result();
              ?>
          </div>
          <?php endforeach;
          if (isset($qStmt)) $qStmt->close();
          ?>

          <input type="submit" name="submit_answers" value="Submit Answers">
      </form>
      <?php endif; ?>

      <?php if($marks_report): ?>
      <h2>Student Details</h2>
      <p><strong>HT No:</strong> <?php echo e($student['htno']); ?></p>
      <p><strong>Name:</strong> <?php echo e($student['name']); ?></p>
      <p><strong>Team ID:</strong> <?php echo e($student['teamid'] ?? ''); ?></p>

      <h2>Marks Report</h2>
      <?php
      // total marks possible
      $total_marks_possible = 0;
      $totalStmt = $conn->prepare("SELECT SUM(marks) AS total FROM correct_answers WHERE test_id = ?");
      $totalStmt->bind_param("i", $TEST_ID);
      $totalStmt->execute();
      $totalStmt->store_result();
      $totalStmt->bind_result($t_total);
      $totalStmt->fetch();
      $total_marks_possible = floatval($t_total ?? 0);
      $totalStmt->close();

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
              <td><?php echo e($row['section_name']); ?></td>
              <td><?php echo e($row['total_marks']); ?></td>
          </tr>
          <?php endforeach; ?>
          <tr>
              <td><strong>Total</strong></td>
              <td><strong><?php echo e($grand_total); ?> / <?php echo e($total_marks_possible); ?></strong></td>
          </tr>
          <tr style="background:<?php echo e($color); ?>; color:#fff; font-weight:bold; font-size:16px;">
              <td>Percentage</td>
              <td><?php echo number_format($percentage, 2); ?>%</td>
          </tr>
          <tr style="background:<?php echo e($color); ?>; color:#fff; font-weight:bold; font-size:16px;">
              <td colspan="2"><?php echo e($remark); ?></td>
          </tr>
      </table>
      <?php endif; ?>
  </div>
</div>

<!-- Violation modal -->
<div id="violationModal" aria-hidden="true">
  <div class="box">
    <h3 id="vmTitle">⚠️ Violation detected</h3>
    <p id="vmMessage">You switched tabs — this is not allowed during the test.</p>
    <div class="count">Refreshing in <span id="vmCount">3</span>s...</div>
    <button id="vmClose" style="display:none;">Close</button>
  </div>
</div>

<script>
let testStarted = <?php echo isset($student) && !$marks_report ? 'true' : 'false'; ?>;

(function() {
  if (!testStarted) return;

  const pdfFrame = document.querySelector(".pdf-container iframe");
  const TRIGGER_DEBOUNCE = 600; // ms
  let lastTrigger = 0;
  let modalTimer = null;

  const modal = document.getElementById('violationModal');
  const vmTitle = document.getElementById('vmTitle');
  const vmMessage = document.getElementById('vmMessage');
  const vmCount = document.getElementById('vmCount');
  const vmClose = document.getElementById('vmClose');

  function showModal(reason, seconds = 3) {
    // Reset any existing countdown
    if (modalTimer) {
      clearInterval(modalTimer);
      modalTimer = null;
    }

    vmTitle.textContent = "⚠️ Violation detected";
    vmMessage.textContent = reason;
    vmCount.textContent = seconds;
    vmClose.style.display = 'none';

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    let remaining = seconds;
    modalTimer = setInterval(() => {
      remaining--;
      if (remaining <= 0) {
        clearInterval(modalTimer);
        modalTimer = null;
        // optional small delay to ensure UI updates
        setTimeout(() => {
          // force reload
          location.reload();
        }, 120);
        return;
      }
      vmCount.textContent = remaining;
    }, 1000);
  }

  function triggerViolation(reason) {
    const now = Date.now();
    if (now - lastTrigger < TRIGGER_DEBOUNCE) {
      // still show modal even if debounced? we'll ignore too-rapid repeats to avoid flicker
      return;
    }
    lastTrigger = now;

    // If iframe is active element, ignore (user interacting with PDF)
    try {
      const active = document.activeElement;
      if (pdfFrame && active === pdfFrame) return;
    } catch (e) {}

    // show modal and reload after countdown
    showModal(reason, 3);

    // optional: send beacon for server-side logging (uncomment and create endpoint)
    /*
    try {
      const payload = JSON.stringify({
        htno: document.querySelector('input[name="htno"]')?.value || null,
        test_id: <?php echo json_encode($TEST_ID); ?>,
        reason: reason,
        ts: new Date().toISOString()
      });
      navigator.sendBeacon('/log_infraction.php', payload);
    } catch(e) {}
    */
  }

  // visibilitychange: happens on tab switch or window minimize in many browsers
  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      triggerViolation("Tab switch detected! You cannot switch tabs during the test.");
    }
  });

  // blur: happens on alt+tab, switch window, minimize
  window.addEventListener("blur", () => {
    // ignore if PDF iframe is clicked (embedded)
    try {
      const active = document.activeElement;
      if (pdfFrame && active === pdfFrame) return;
    } catch (e) {}
    triggerViolation("Window focus lost! You cannot switch apps or minimize.");
  });

  // keyboard shortcuts
  document.addEventListener("keydown", (e) => {
    const k = e.key;
    const c = e.ctrlKey || e.metaKey;
    if (k === "F5" || (c && ["r","R","w","t"].includes(k))) {
      e.preventDefault();
      triggerViolation("Refresh or opening/closing tabs is disabled during the test.");
    }
  });

  // right-click
  document.addEventListener("contextmenu", (e) => {
    e.preventDefault();
    triggerViolation("Right-click is disabled during the test.");
  });

  // Single-tab lock (sessionStorage per test)
  (function() {
    const KEY = "active_test_tab_<?php echo e($TEST_ID); ?>";
    const ID = Math.random().toString(36).substr(2, 9);
    const existing = sessionStorage.getItem(KEY);
    if (existing && existing !== ID) {
      // If another tab is active, show modal and reload
      showModal("Another test tab is already active. This tab will refresh.", 3);
      setTimeout(() => location.reload(true), 3500);
      return;
    } else {
      sessionStorage.setItem(KEY, ID);
    }

    window.addEventListener("storage", (e) => {
      if (e.key === KEY && e.newValue !== ID) {
        showModal("Another tab has been opened for this test. This tab will refresh.", 3);
        setTimeout(() => location.reload(true), 3500);
      }
    });

    window.addEventListener("beforeunload", () => {
      try {
        if (sessionStorage.getItem(KEY) === ID) sessionStorage.removeItem(KEY);
      } catch (e) {}
    });
  })();

  // optional close button (not shown by default) to let user dismiss message (but page will still reload)
  vmClose.addEventListener('click', () => {
    if (modalTimer) {
      clearInterval(modalTimer);
      modalTimer = null;
    }
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  });

})();
</script>
</body>
</html>