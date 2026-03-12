<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Restrict access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

$htpo_username = $_SESSION['username'];

// Fetch HTPO info
$stmt = $conn->prepare("SELECT college, prog, year FROM USERS WHERE username=? LIMIT 1");
$stmt->bind_param("s", $htpo_username);
$stmt->execute();
$htpo_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prog = $htpo_data['prog'];
$year = $htpo_data['year'];
$colleges = explode(',', $htpo_data['college']);

// Fetch all class IDs under this HTPO
$class_list = [];
foreach ($colleges as $college) {
    $college = trim($college);
    $stmt = $conn->prepare("
        SELECT DISTINCT classid 
        FROM STUDENTS 
        WHERE FIND_IN_SET(?, college) > 0 AND prog=? AND year=? 
        ORDER BY classid
    ");
    $stmt->bind_param("ssi", $college, $prog, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $class_list[] = $row['classid'];
    }
    $stmt->close();
}
$class_list = array_unique($class_list);
sort($class_list);

$selected_class = $_GET['classid'] ?? null;
$selected_test_id = $_GET['test_id'] ?? null;

// Build Overall Class Summary
$report = [];
foreach ($class_list as $classid) {
    $stmt = $conn->prepare("SELECT test_id, total_marks FROM class_test WHERE classid=? AND created_by=?");
    $stmt->bind_param("ss", $classid, $htpo_username);
    $stmt->execute();
    $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_tests = count($tests);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM STUDENTS WHERE classid=? AND debarred=0");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $total_students = $stmt->get_result()->fetch_assoc()['total_students'];
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT m.marks_obtained, t.total_marks
        FROM class_test_marks m 
        JOIN class_test t ON m.test_id=t.test_id
        WHERE t.classid=? AND t.created_by=?
    ");
    $stmt->bind_param("ss", $classid, $htpo_username);
    $stmt->execute();
    $res = $stmt->get_result();
    $attempts = 0;
    $percent_sum = 0;
    while ($row = $res->fetch_assoc()) {
        $attempts++;
        $marks = floatval($row['marks_obtained']);
        $total = floatval($row['total_marks']);
        if ($total > 0 && is_numeric($marks)) {
            $percent_sum += ($marks / $total) * 100;
        }
    }
    $stmt->close();

    $avg_percentage = ($attempts > 0) ? round($percent_sum / $attempts, 2) : 0;

    $report[] = [
        'classid' => $classid,
        'total_tests' => $total_tests,
        'total_students' => $total_students,
        'attempts' => $attempts,
        'avg_percentage' => $avg_percentage
    ];
}
usort($report, fn($a, $b) => $b['avg_percentage'] <=> $a['avg_percentage']);

// Class or Test-level data
$students_report = [];
$test_list = [];
$test_info = null;

if ($selected_class) {
    $stmt = $conn->prepare("SELECT test_id, test_name, total_marks, test_date FROM class_test WHERE classid=? AND created_by=? ORDER BY test_date DESC");
    $stmt->bind_param("ss", $selected_class, $htpo_username);
    $stmt->execute();
    $test_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($selected_test_id) {
        $stmt = $conn->prepare("SELECT test_name, total_marks FROM class_test WHERE test_id=? AND classid=? AND created_by=?");
        $stmt->bind_param("iss", $selected_test_id, $selected_class, $htpo_username);
        $stmt->execute();
        $test_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT s.htno, s.name, s.teamid, COALESCE(m.marks_obtained, '-') AS marks
            FROM STUDENTS s
            LEFT JOIN class_test_marks m ON s.htno=m.htno AND m.test_id=?
            WHERE s.classid=? AND s.debarred=0
            ORDER BY s.teamid, s.name
        ");
        $stmt->bind_param("is", $selected_test_id, $selected_class);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $marks = floatval($row['marks']);
            $total = floatval($test_info['total_marks']);
            $percent = ($total > 0 && is_numeric($marks)) ? round(($marks / $total) * 100, 2) : 0;
            $students_report[] = [
                'htno' => $row['htno'],
                'name' => $row['name'],
                'teamid' => $row['teamid'],
                'marks' => $row['marks'],
                'percent' => $percent
            ];
        }
        $stmt->close();
        usort($students_report, fn($a, $b) => $b['percent'] <=> $a['percent']);
    } else {
        $stmt = $conn->prepare("SELECT htno, name, teamid FROM STUDENTS WHERE classid=? AND debarred=0 ORDER BY teamid, name");
        $stmt->bind_param("s", $selected_class);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $conn->prepare("SELECT test_id, total_marks FROM class_test WHERE classid=? AND created_by=?");
        $stmt->bind_param("ss", $selected_class, $htpo_username);
        $stmt->execute();
        $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($students as $s) {
            $htno = $s['htno'];
            $name = $s['name'];
            $teamid = $s['teamid'];
            $attempted = 0;
            $total_percentage = 0;

            foreach ($tests as $t) {
                $stmt = $conn->prepare("SELECT marks_obtained FROM class_test_marks WHERE test_id=? AND htno=?");
                $stmt->bind_param("is", $t['test_id'], $htno);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    $marks = floatval($res->fetch_assoc()['marks_obtained']);
                    $total = floatval($t['total_marks']);
                    $percent = ($total > 0 && is_numeric($marks)) ? ($marks / $total) * 100 : 0;
                    $total_percentage += $percent;
                    $attempted++;
                }
                $stmt->close();
            }

            $avg_percent = ($attempted > 0) ? round($total_percentage / $attempted, 2) : 0;

            $students_report[] = [
                'htno' => $htno,
                'name' => $name,
                'teamid' => $teamid,
                'tests_attempted' => $attempted,
                'avg_percent' => $avg_percent
            ];
        }
        usort($students_report, fn($a, $b) => $b['avg_percent'] <=> $a['avg_percent']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HTPO - Class Test Report</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4">
<div class="max-w-6xl mx-auto bg-white p-6 rounded-2xl shadow-md">
<h1 class="text-2xl font-bold text-center text-green-600 mb-4">📊 HTPO Class Test Report</h1>

<?php if (!$selected_class): ?>
<!-- Overall Class Summary -->
<div class="overflow-x-auto">
<table class="w-full border-collapse text-sm">
<thead class="bg-green-600 text-white">
<tr>
<th class="py-2 px-3">Rank</th>
<th class="py-2 px-3">Class ID</th>
<th class="py-2 px-3">Total Tests</th>
<th class="py-2 px-3">Total Students</th>
<th class="py-2 px-3">Tests Attempted</th>
<th class="py-2 px-3">Average %</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($report as $r): ?>
<tr class="border-b hover:bg-gray-50">
<td class="py-2 px-3 text-center"><?= $rank++ ?></td>
<td class="py-2 px-3 text-blue-600 font-semibold">
<a href="?classid=<?= urlencode($r['classid']) ?>"><?= htmlspecialchars($r['classid']) ?></a>
</td>
<td class="py-2 px-3"><?= $r['total_tests'] ?></td>
<td class="py-2 px-3"><?= $r['total_students'] ?></td>
<td class="py-2 px-3"><?= $r['attempts'] ?></td>
<td class="py-2 px-3 font-bold"><?= $r['avg_percentage'] ?>%</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: ?>
<!-- Class/Test View -->
<a href="htpo_class_test_report.php" class="inline-block mb-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">← Back</a>

<h2 class="text-lg font-bold mb-3">Class: <?= htmlspecialchars($selected_class) ?></h2>

<form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
<input type="hidden" name="classid" value="<?= htmlspecialchars($selected_class) ?>">
<label class="font-medium text-gray-700">Select Test:</label>
<select name="test_id" onchange="this.form.submit()" class="border border-gray-300 rounded-md p-2">
<option value="">-- Overall Report --</option>
<?php foreach($test_list as $t): ?>
<option value="<?= $t['test_id'] ?>" <?= ($selected_test_id==$t['test_id'])?'selected':'' ?>>
<?= htmlspecialchars($t['test_name']) ?> (<?= htmlspecialchars($t['test_date']) ?>)
</option>
<?php endforeach; ?>
</select>
</form>

<div class="overflow-x-auto">
<?php if ($selected_test_id): ?>
<!-- Test-wise report -->
<h3 class="text-base font-semibold text-gray-700 mb-2">
Test: <?= htmlspecialchars($test_info['test_name']) ?> | Total Marks: <?= htmlspecialchars($test_info['total_marks']) ?>
</h3>
<table class="w-full border-collapse text-sm">
<thead class="bg-green-600 text-white">
<tr>
<th class="py-2 px-3">Rank</th>
<th class="py-2 px-3">HT No</th>
<th class="py-2 px-3">Name</th>
<th class="py-2 px-3">Team ID</th>
<th class="py-2 px-3">Marks</th>
<th class="py-2 px-3">Percentage</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($students_report as $s): ?>
<tr class="border-b hover:bg-gray-50">
<td class="py-2 px-3"><?= $rank++ ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['htno']) ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['name']) ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['teamid']) ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['marks']) ?></td>
<td class="py-2 px-3 font-bold"><?= $s['percent'] ?>%</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php else: ?>
<!-- Class-wise overall report -->
<h3 class="text-base font-semibold text-gray-700 mb-2">Overall Student Performance</h3>
<table class="w-full border-collapse text-sm">
<thead class="bg-green-600 text-white">
<tr>
<th class="py-2 px-3">Rank</th>
<th class="py-2 px-3">HT No</th>
<th class="py-2 px-3">Name</th>
<th class="py-2 px-3">Team ID</th>
<th class="py-2 px-3">Tests Attempted</th>
<th class="py-2 px-3">Average %</th>
</tr>
</thead>
<tbody>
<?php $rank=1; foreach($students_report as $s): ?>
<tr class="border-b hover:bg-gray-50">
<td class="py-2 px-3"><?= $rank++ ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['htno']) ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['name']) ?></td>
<td class="py-2 px-3"><?= htmlspecialchars($s['teamid']) ?></td>
<td class="py-2 px-3"><?= $s['tests_attempted'] ?></td>
<td class="py-2 px-3 font-bold"><?= $s['avg_percent'] ?>%</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>
