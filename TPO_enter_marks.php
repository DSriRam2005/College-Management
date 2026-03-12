<?php
include 'db.php';
$message = '';

$selected_test_id = $_POST['test_id'] ?? '';
$selected_classid = $_POST['classid'] ?? '';

// --- SAVE STUDENT MARKS ---
if (isset($_POST['save_student'])) {
    $htno = $_POST['htno'];
    $test_id = $_POST['test_id'];
    $section_ids = $_POST['section_id'];
    $marks = $_POST['marks'];

    for ($i = 0; $i < count($section_ids); $i++) {
        $stmt = $conn->prepare("
            INSERT INTO student_marks (htno, test_id, section_id, marks_obtained) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained)
        ");
        $stmt->bind_param("siii", $htno, $test_id, $section_ids[$i], $marks[$i]);
        $stmt->execute();
    }

    $message = "Marks saved for student: " . htmlspecialchars($htno);

    // Preserve selection after saving
    $selected_test_id = $_POST['test_id'];
    $selected_classid = $_POST['classid'];
}

// --- DROPDOWNS ---
$class_res = $conn->query("
    SELECT DISTINCT classid FROM STUDENTS 
    WHERE prog='B.TECH' AND year=22 AND gen='F' 
    ORDER BY classid
");
$tests = $conn->query("SELECT * FROM tests ORDER BY test_name");

// --- FETCH SECTIONS ---
$sections = [];
if ($selected_test_id != '') {
    $sec_res = $conn->query("SELECT * FROM test_sections WHERE test_id=$selected_test_id ORDER BY section_id");
    while ($s = $sec_res->fetch_assoc()) {
        $sections[] = $s;
    }
}

// --- FETCH STUDENTS ---
$student_sql = "SELECT * FROM STUDENTS WHERE prog='B.TECH' AND year=22 AND gen='F'";
if ($selected_classid != '') {
    $student_sql .= " AND classid='" . $conn->real_escape_string($selected_classid) . "'";
}
$student_sql .= " ORDER BY htno";
$students = $conn->query($student_sql);

// --- FETCH EXISTING MARKS ---
$marks_data = [];
if ($selected_test_id != '') {
    $mark_res = $conn->query("SELECT * FROM student_marks WHERE test_id=$selected_test_id");
    while ($m = $mark_res->fetch_assoc()) {
        $marks_data[$m['htno']][$m['section_id']] = $m['marks_obtained'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enter Marks</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 20px;}
        table, th, td {border:1px solid #ccc; border-collapse: collapse; padding:6px;}
        table {width:100%; margin-top:10px;}
        th {background:#f0f0f0;}
        input[type=number]{width:60px; padding:4px;}
        .save-btn{padding:5px 10px; background:#0069d9; color:#fff; border:none; cursor:pointer;}
        .save-btn:hover{background:#0053b3;}
        select{padding:5px;}
    </style>
</head>
<body>
<h2>Enter Marks</h2>
<?php if ($message != '') echo "<p style='color:green;'>$message</p>"; ?>

<form method="post">
    <label><strong>Select Test:</strong></label>
    <select name="test_id" onchange="this.form.submit()">
        <option value="">--Select Test--</option>
        <?php while ($t = $tests->fetch_assoc()) { ?>
            <option value="<?= $t['test_id'] ?>" <?= ($selected_test_id == $t['test_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['test_name']) ?>
            </option>
        <?php } ?>
    </select>

    &nbsp;&nbsp;
    <label><strong>Select Class:</strong></label>
    <select name="classid" onchange="this.form.submit()">
        <option value="">--All Classes--</option>
        <?php while ($c = $class_res->fetch_assoc()) { ?>
            <option value="<?= $c['classid'] ?>" <?= ($selected_classid == $c['classid']) ? 'selected' : '' ?>>
                <?= $c['classid'] ?>
            </option>
        <?php } ?>
    </select>
</form>

<?php if ($selected_test_id != '' && count($sections) > 0): ?>
<table>
<tr>
    <th>HT No</th>
    <th>Name</th>
    <?php foreach ($sections as $sec) { ?>
        <th><?= htmlspecialchars($sec['section_name']) ?><br>(<?= $sec['section_marks'] ?>)</th>
    <?php } ?>
    <th>Action</th>
</tr>

<?php while ($stu = $students->fetch_assoc()) { ?>
<tr>
    <form method="post">
        <td><?= $stu['htno'] ?><input type="hidden" name="htno" value="<?= $stu['htno'] ?>"></td>
        <td><?= htmlspecialchars($stu['name']) ?></td>
        <?php foreach ($sections as $sec) {
            $val = $marks_data[$stu['htno']][$sec['section_id']] ?? '';
        ?>
            <td>
                <input type="hidden" name="test_id" value="<?= $selected_test_id ?>">
                <input type="hidden" name="classid" value="<?= htmlspecialchars($selected_classid) ?>">
                <input type="hidden" name="section_id[]" value="<?= $sec['section_id'] ?>">
                <input type="number" name="marks[]" max="<?= $sec['section_marks'] ?>" value="<?= $val ?>">
            </td>
        <?php } ?>
        <td><button type="submit" name="save_student" class="save-btn">Save</button></td>
    </form>
</tr>
<?php } ?>
</table>
<?php elseif ($selected_test_id != ''): ?>
<p>No sections found for this test.</p>
<?php endif; ?>

</body>
</html>
