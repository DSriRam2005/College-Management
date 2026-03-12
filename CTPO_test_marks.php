<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

// =========================
// ✅ Only CPTO role allowed
// =========================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$ctpo_username = $_SESSION['username'];

// =========================
// ✅ Get CPTO details
// =========================
$stmt = $conn->prepare("SELECT college, year, classid FROM USERS WHERE username=? LIMIT 1");
$stmt->bind_param("s", $ctpo_username);
$stmt->execute();
$ctpo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$college = $ctpo['college'];
$year = $ctpo['year'];
$classid = $ctpo['classid'] ?? '';

// =========================
// 🚫 Block page if missing student data
// =========================
$has_missing = false;
$missing_list = [];

if (!empty($classid)) {
    $stmt = $conn->prepare("
        SELECT htno, name, st_phone, f_phone 
        FROM STUDENTS 
        WHERE classid=? 
        AND (
            st_phone IS NULL OR st_phone='' 
            OR f_phone IS NULL OR f_phone='' 
            OR name IS NULL OR name=''
        )
    ");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $has_missing = true;
        while ($r = $res->fetch_assoc()) {
            $missing_list[] = $r;
        }
    }
    $stmt->close();
}

if ($has_missing) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Incomplete Data</title>
    <style>
    body { font-family:'Segoe UI',sans-serif; background:#f8d7da; color:#721c24; padding:40px; }
    .box { background:#fff; border-radius:8px; padding:20px; max-width:700px; margin:auto; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    h2 { color:#c82333; }
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { border:1px solid #f5c6cb; padding:8px; text-align:left; }
    th { background:#f5c6cb; }
    a.button { background:#721c24; color:white; padding:10px 15px; border-radius:5px; text-decoration:none; display:inline-block; margin-top:15px; }
    </style>
    <script>
    setTimeout(function(){ window.location.href='ctpo_student_details.php'; }, 5000);
    </script>
    </head><body>
    <div class='box'>
    <h2>⚠️ Student Details Incomplete</h2>
    <p>Some students in your class (<b>$classid</b>) have missing details. Please update all records before entering marks.</p>
    <table>
        <tr><th>HT No</th><th>Name</th><th>Student Phone</th><th>Parent Phone</th></tr>";
    foreach ($missing_list as $m) {
        echo "<tr>
            <td>{$m['htno']}</td>
            <td>" . htmlspecialchars($m['name'] ?: '❌ Missing') . "</td>
            <td>" . htmlspecialchars($m['st_phone'] ?: '❌ Missing') . "</td>
            <td>" . htmlspecialchars($m['f_phone'] ?: '❌ Missing') . "</td>
        </tr>";
    }
    echo "</table>
    <p><b>Action Required:</b> Update the missing student data.</p>
    <a href='ctpo_student_details.php' class='button'>Go to Student Details</a>
    <p><i>Redirecting automatically in 5 seconds...</i></p>
    </div></body></html>";
    exit();
}

// =========================
// ✅ Fetch all tests for this CTPO class
// =========================
$tests = [];
$stmt = $conn->prepare("
    SELECT test_id, test_name, test_date, total_marks, classid 
    FROM class_test 
    WHERE year=? AND FIND_IN_SET(?, classid) > 0
    ORDER BY test_date DESC
");
$stmt->bind_param("is", $year, $classid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $tests[] = $row;
}
$stmt->close();

// =========================
// ✅ Selected test & students
// =========================
$selected_test = $_POST['test_id'] ?? '';
$students = [];
$total_marks = 0;
$marks_map = [];

if ($selected_test) {
    // Get test details
    $stmt = $conn->prepare("SELECT total_marks, classid FROM class_test WHERE test_id=?");
    $stmt->bind_param("i", $selected_test);
    $stmt->execute();
    $test_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_marks = $test_data['total_marks'];
    $test_classid = $test_data['classid'];

    // Fetch students for this class (✅ Added team_id + ordered by team_id, name)
   $stmt = $conn->prepare("
    SELECT htno, name, classid, st_phone, f_phone, teamid 
    FROM STUDENTS 
    WHERE classid=? AND debarred=0 
    ORDER BY 
        SUBSTRING_INDEX(teamid, '_', -1) * 1 ASC, 
        name
");
    $stmt->bind_param("s", $test_classid);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch existing marks
    $stmt = $conn->prepare("SELECT htno, marks_obtained FROM class_test_marks WHERE test_id=?");
    $stmt->bind_param("i", $selected_test);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $marks_map[$r['htno']] = $r['marks_obtained'];
    }
    $stmt->close();
}

// =========================
// ✅ Handle submission
// =========================
$success_count = 0;
$error_msgs = [];

if (isset($_POST['submit_marks'])) {
    $marks = $_POST['marks'] ?? [];
    $htnos = $_POST['htno'] ?? [];
    $test_id_post = $_POST['test_id'] ?? '';

    $stmt_ins = $conn->prepare("
        INSERT INTO class_test_marks (test_id, htno, marks_obtained, entered_by)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained)
    ");

    foreach ($htnos as $i => $htno) {
        $mark = strtoupper(trim($marks[$i]));

        // Validate mark
        if ($mark !== 'A' && (!is_numeric($mark) || $mark < 0)) {
            $error_msgs[] = "Invalid marks for $htno";
            continue;
        }
        if (is_numeric($mark) && $mark > $total_marks) {
            $error_msgs[] = "Error: $htno marks ($mark) exceed total marks ($total_marks)";
            continue;
        }

        $stmt_ins->bind_param("isss", $test_id_post, $htno, $mark, $ctpo_username);
        if ($stmt_ins->execute()) $success_count++;
    }
    $stmt_ins->close();

    // Refresh marks_map
    $marks_map = [];
    $stmt = $conn->prepare("SELECT htno, marks_obtained FROM class_test_marks WHERE test_id=?");
    $stmt->bind_param("i", $test_id_post);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $marks_map[$r['htno']] = $r['marks_obtained'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CTPO - Enter Test Marks</title>
<style>
body { font-family:'Segoe UI', sans-serif; background:#f5f6fa; }
.container { max-width:900px; margin:40px auto; background:white; padding:25px; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#0066cc; }
label { font-weight:bold; display:block; margin-top:10px; }
select, input[type=text] { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:6px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
th { background:#007BFF; color:white; }
tr:nth-child(even){background:#f9f9f9;}
input[type=submit]{background:#28a745;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-size:16px;margin-top:15px;}
input[type=submit]:hover{background:#218838;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:6px;}
.warning{background:#fff3cd;color:#856404;padding:8px;border-radius:6px;}
</style>
</head>
<body>
<div class="container">
<h2>CTPO - Enter Test Marks</h2>

<?php
if(!empty($error_msgs)){
    foreach($error_msgs as $err){
        echo "<p class='warning'>$err</p>";
    }
}
if($success_count) echo "<p class='success'>✅ Successfully saved marks for $success_count student(s).</p>";
?>

<form method="POST">
    <label>Select Test:</label>
    <select name="test_id" onchange="this.form.submit()" required>
        <option value="">-- Select Test --</option>
        <?php foreach($tests as $t): ?>
            <option value="<?= $t['test_id'] ?>" <?= ($selected_test == $t['test_id'])?'selected':'' ?>>
                <?= htmlspecialchars($t['test_name']) ?> (<?= $t['test_date'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if(!empty($students) && $selected_test): ?>
<form method="POST">
<input type="hidden" name="test_id" value="<?= $selected_test ?>">
<p><b>Total Marks:</b> <?= $total_marks ?></p>

<table>
<tr><th>Team ID</th><th>HT No</th><th>Name</th><th>Class ID</th><th>Student No</th><th>Parent No</th><th>Marks</th></tr>
<?php foreach($students as $s): 
    $existing = $marks_map[$s['htno']] ?? '';
?>
<tr>
<td><?= htmlspecialchars($s['teamid']) ?></td>
<td><?= htmlspecialchars($s['htno']) ?><input type="hidden" name="htno[]" value="<?= $s['htno'] ?>"></td>
<td><?= htmlspecialchars($s['name']) ?></td>
<td><?= htmlspecialchars($s['classid']) ?></td>
<td><?= htmlspecialchars($s['st_phone']) ?></td>
<td><?= htmlspecialchars($s['f_phone']) ?></td>
<td><input type="text" name="marks[]" value="<?= htmlspecialchars($existing) ?>" placeholder="0 - <?= $total_marks ?> or A" oninput="checkMarks(this, <?= $total_marks ?>)" ></td>
</tr>
<?php endforeach; ?>
</table>

<input type="submit" name="submit_marks" value="Submit Marks">
</form>

<script>
function checkMarks(input, max) {
    let val = input.value.toUpperCase();
    if(val === "A") return;
    if(val !== '' && !isNaN(val)) {
        let num = Number(val);
        if(num > max){
            input.value = max;
            alert("Marks cannot exceed total marks: " + max);
        } else if(num < 0){
            input.value = 0;
        }
    } else if(val !== '') {
        input.value = '';
        alert("Only numbers (0-" + max + ") or 'A' allowed");
    }
}
</script>
<?php endif; ?>

</div>
</body>
</html>
