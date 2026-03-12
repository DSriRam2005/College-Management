<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$student = null;
$message = "";
$feedback_exists = false;

// Step 1: Student search
if (isset($_POST['search'])) {
    $htno = $conn->real_escape_string($_POST['htno']);
    $sql = "SELECT * FROM STUDENTS WHERE htno='$htno' AND prog='B.TECH' AND year=22 AND gen='F'";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        $student = $res->fetch_assoc();

        // Check if feedback already exists
        $check = $conn->prepare("SELECT htno FROM 2infosys_feedback WHERE htno = ?");
        $check->bind_param("s", $student['htno']);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $feedback_exists = true;
            $message = "❌ Feedback for this student has already been submitted.";
        }
        $check->close();

    } else {
        $message = "❌ No eligible record found for entered HTNO.";
    }
}

// Step 2: Feedback submission
if (isset($_POST['submit_feedback']) && !$feedback_exists) {
    $htno = $_POST['htno'];
    $name = $_POST['name'];

    $stmt = $conn->prepare("
        INSERT INTO 2infosys_feedback 
        (htno, name, coding_accuracy, problem_solving, time_management, conceptual_clarity, application_training, comments_a,
        prog_fund_relevance, prog_fund_preparedness, dsa_relevance, dsa_preparedness,
        aptitude_relevance, aptitude_preparedness, mock_relevance, mock_preparedness,
        confidence_level, best_module, training_gaps, lag_topic,
        overall_readiness, next_steps)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssiiiissssssssssssssss",
        $htno, $name,
        $_POST['coding_accuracy'], $_POST['problem_solving'], $_POST['time_management'], $_POST['conceptual_clarity'], $_POST['application_training'], $_POST['comments_a'],
        $_POST['prog_fund_relevance'], $_POST['prog_fund_preparedness'], $_POST['dsa_relevance'], $_POST['dsa_preparedness'],
        $_POST['aptitude_relevance'], $_POST['aptitude_preparedness'], $_POST['mock_relevance'], $_POST['mock_preparedness'],
        $_POST['confidence_level'], $_POST['best_module'], $_POST['training_gaps'], $_POST['lag_topic'],
        $_POST['overall_readiness'], implode(", ", $_POST['next_steps'] ?? [])
    );

    if ($stmt->execute()) {
        $message = "✅ Feedback submitted successfully!";
        $feedback_exists = true; // Disable form after submission
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Infosys Return Test Feedback Form</title>
<style>
body { font-family: Arial; margin: 20px; background: #f8f8f8; }
.container { background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 900px; margin: auto; box-shadow: 0 0 10px #ccc; }
h2 { text-align: center; color: #003366; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
td, th { border: 1px solid #aaa; padding: 8px; text-align: center; }
textarea { width: 100%; height: 60px; }
input[type=number] { width: 50px; }
.btn { background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
.btn:hover { background: #0056b3; }
.disabled { background: #ccc; cursor: not-allowed; }
</style>
</head>
<body>
<div class="container">
<h2>📋 Infosys Return Test Feedback Form</h2>

<form method="POST">
    <label>Enter Hall Ticket No:</label>
    <input type="text" name="htno" required value="<?= $_POST['htno'] ?? '' ?>">
    <button class="btn" name="search">Search</button>
</form>

<?php if ($message) echo "<p><b>$message</b></p>"; ?>

<?php if ($student): ?>
<form method="POST">
    <input type="hidden" name="htno" value="<?= $student['htno'] ?>">
    <input type="hidden" name="name" value="<?= $student['name'] ?>">

    <h3>Student: <?= $student['name'] ?> (<?= $student['htno'] ?>)</h3>
    <hr>

    <h4>🔍 Section A: Test Performance Overview</h4>
    <table>
        <tr><th>Area Assessed</th><th>Rating (1–5)</th><th>Comments</th></tr>
        <tr>
            <td>Coding Accuracy</td>
            <td><input type="number" name="coding_accuracy" min="1" max="5" <?= $feedback_exists ? "disabled" : "" ?>></td>
            <td rowspan="5"><textarea name="comments_a" <?= $feedback_exists ? "disabled" : "" ?>></textarea></td>
        </tr>
        <tr><td>Problem-Solving Approach</td><td><input type="number" name="problem_solving" min="1" max="5" <?= $feedback_exists ? "disabled" : "" ?>></td></tr>
        <tr><td>Time Management</td><td><input type="number" name="time_management" min="1" max="5" <?= $feedback_exists ? "disabled" : "" ?>></td></tr>
        <tr><td>Conceptual Clarity</td><td><input type="number" name="conceptual_clarity" min="1" max="5" <?= $feedback_exists ? "disabled" : "" ?>></td></tr>
        <tr><td>Application of Training</td><td><input type="number" name="application_training" min="1" max="5" <?= $feedback_exists ? "disabled" : "" ?>></td></tr>
    </table>

    <h4>📚 Section B: Training Effectiveness</h4>
    <table>
        <tr><th>Training Module</th><th>Relevance</th><th>Preparedness</th></tr>
        <?php
        $modules = ['prog_fund'=>'Programming Fundamentals','dsa'=>'DSA (Data Structures & Algorithms)','aptitude'=>'Aptitude & Logical Reasoning','mock'=>'Mock Test Practice'];
        foreach($modules as $key=>$title){
            echo "<tr><td>$title</td>
            <td>
                <select name='{$key}_relevance' ".($feedback_exists?"disabled":"")."><option>High</option><option>Medium</option><option>Low</option></select>
            </td>
            <td>
                <select name='{$key}_preparedness' ".($feedback_exists?"disabled":"")."><option>Excellent</option><option>Good</option><option>Needs Improvement</option></select>
            </td></tr>";
        }
        ?>
    </table>

    <h4>💬 Section C: Candidate Reflections</h4>
    <p>Confidence Level:
        <select name="confidence_level" <?= $feedback_exists ? "disabled" : "" ?>>
            <option>Very Confident</option>
            <option>Somewhat Confident</option>
            <option>Not Confident</option>
        </select>
    </p>
    <p>Which module helped most? <textarea name="best_module" <?= $feedback_exists ? "disabled" : "" ?>></textarea></p>
    <p>Gaps between training & test expectations: <textarea name="training_gaps" <?= $feedback_exists ? "disabled" : "" ?>></textarea></p>
    <p>Topics you lag after this test: <textarea name="lag_topic" <?= $feedback_exists ? "disabled" : "" ?>></textarea></p>

    <h4>✅ Section D: Final Remarks</h4>
    <p>Overall Readiness:
        <select name="overall_readiness" <?= $feedback_exists ? "disabled" : "" ?>>
            <option>Ready</option><option>Almost Ready</option><option>Needs Further Preparation</option>
        </select>
    </p>
    <p>Recommended Next Steps:</p>
    <label><input type="checkbox" name="next_steps[]" value="Additional Mock Tests" <?= $feedback_exists ? "disabled" : "" ?>> Additional Mock Tests</label><br>
    <label><input type="checkbox" name="next_steps[]" value="Focused DSA Practice" <?= $feedback_exists ? "disabled" : "" ?>> Focused DSA Practice</label><br>
    <label><input type="checkbox" name="next_steps[]" value="Peer-led Revision Sessions" <?= $feedback_exists ? "disabled" : "" ?>> Peer-led Revision Sessions</label><br>
    <label><input type="checkbox" name="next_steps[]" value="Trainer Intervention" <?= $feedback_exists ? "disabled" : "" ?>> Trainer Intervention</label><br>

    <br>
    <button class="btn" name="submit_feedback" <?= $feedback_exists ? "disabled class='disabled'" : "" ?>>Submit Feedback</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
