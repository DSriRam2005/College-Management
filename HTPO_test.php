<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Only allow HTPO
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

// Build class options from permitted colleges
$colleges = explode(',', $htpo_data['college']);
$class_options = [];
foreach ($colleges as $college) {
    $college = trim($college);
    if ($college === '') continue;
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
        $class_options[] = $row['classid'];
    }
    $stmt->close();
}
$class_options = array_values(array_unique($class_options));
sort($class_options);

$message = "";

/* =========================================
   EXPORT MARKS AS CSV  (must run before any output)
   Fields: htno, name, teamid, marks
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    $test_id = intval($_POST['export_csv']);

    // Verify test belongs to this HTPO and get classid + test info
    $stmt = $conn->prepare("SELECT test_name, classid, total_marks, test_date, test_time 
                            FROM class_test 
                            WHERE test_id=? AND created_by=? LIMIT 1");
    $stmt->bind_param("is", $test_id, $htpo_username);
    $stmt->execute();
    $test_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$test_info) {
        header('HTTP/1.1 403 Forbidden');
        echo "Invalid test.";
        exit();
    }

    $classid = $test_info['classid'];

    // Fetch students in class
    $students = [];
    $stmt = $conn->prepare("SELECT htno, name, teamid FROM STUDENTS WHERE classid=? AND debarred=0 ORDER BY teamid, htno");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[$row['htno']] = $row;
    }
    $stmt->close();

    // Fetch marks for this test
    $marks = [];
    $stmt = $conn->prepare("SELECT htno, marks_obtained FROM class_test_marks WHERE test_id=?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $marks[$row['htno']] = $row['marks_obtained'];
    }
    $stmt->close();

    // Output CSV
    $filename = "marks_{$test_id}_" . preg_replace('/\W+/', '_', $test_info['test_name']) . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $output = fopen('php://output', 'w');

    // Header metadata row(s)
    fputcsv($output, ['Test Name', $test_info['test_name']]);
    fputcsv($output, ['Class ID', $classid]);
    fputcsv($output, ['Total Marks', $test_info['total_marks']]);
    fputcsv($output, ['Date', $test_info['test_date'], 'Time', $test_info['test_time']]);
    fputcsv($output, []); // blank line

    // Column headers
    fputcsv($output, ['HT No', 'Name', 'Team ID', 'Marks']);

    // Data
    foreach ($students as $htno => $s) {
        $m = array_key_exists($htno, $marks) ? $marks[$htno] : '';
        fputcsv($output, [$htno, $s['name'], $s['teamid'], $m]);
    }

    fclose($output);
    exit();
}

/* =========================================
   DELETE TEST (and its marks)
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test'])) {
    $test_id = intval($_POST['delete_test']);

    // Verify ownership
    $stmt = $conn->prepare("SELECT test_id FROM class_test WHERE test_id=? AND created_by=? LIMIT 1");
    $stmt->bind_param("is", $test_id, $htpo_username);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        // Delete marks first
        $stmt = $conn->prepare("DELETE FROM class_test_marks WHERE test_id=?");
        $stmt->bind_param("i", $test_id);
        $stmt->execute();
        $stmt->close();

        // Delete the test
        $stmt = $conn->prepare("DELETE FROM class_test WHERE test_id=? AND created_by=?");
        $stmt->bind_param("is", $test_id, $htpo_username);
        $stmt->execute();
        $stmt->close();

        $message = "<p class='text-green-600 font-semibold mt-4'>Test deleted.</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold mt-4'>Invalid test.</p>";
    }
}

/* =========================================
   UPDATE TEST (Edit submit)
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_test'])) {
    $test_id    = intval($_POST['test_id']);
    $test_name  = trim($_POST['test_name']);
    $total_marks= intval($_POST['total_marks']);
    $test_date  = $_POST['test_date'];
    $test_time  = $_POST['test_time'];
    $classid    = $_POST['classid'];

    if ($test_name && $total_marks > 0 && $test_date && $test_time && $classid) {
        $stmt = $conn->prepare("
            UPDATE class_test 
            SET test_name=?, total_marks=?, test_date=?, test_time=?, classid=?
            WHERE test_id=? AND created_by=?
        ");
        $stmt->bind_param("sisssis", $test_name, $total_marks, $test_date, $test_time, $classid, $test_id, $htpo_username);
        $stmt->execute();
        $stmt->close();

        $message = "<p class='text-green-600 font-semibold mt-4'>Test updated successfully.</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold mt-4'>Invalid input.</p>";
    }
}

/* =========================================
   LOAD TEST INTO EDIT FORM (when Edit clicked)
========================================= */
$edit_test_id = $_POST['edit_test'] ?? 0;
$edit_data = null;
if ($edit_test_id) {
    $stmt = $conn->prepare("SELECT * FROM class_test WHERE test_id=? AND created_by=? LIMIT 1");
    $stmt->bind_param("is", $edit_test_id, $htpo_username);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* =========================================
   CREATE TEST
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    $classids    = $_POST['classid'] ?? [];
    $test_name   = trim($_POST['test_name'] ?? '');
    $total_marks = intval($_POST['total_marks'] ?? 0);
    $test_date   = $_POST['test_date'] ?? '';
    $test_time   = $_POST['test_time'] ?? '';

    if (!empty($classids) && $test_name && $total_marks > 0 && $test_date && $test_time) {
        $count = 0;
        $stmt = $conn->prepare("
            INSERT INTO class_test 
            (test_name, classid, total_marks, test_date, test_time, year, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        foreach ($classids as $cid) {
            $stmt->bind_param("ssissss", $test_name, $cid, $total_marks, $test_date, $test_time, $year, $htpo_username);
            if ($stmt->execute()) $count++;
        }
        $stmt->close();
        $message = "<p class='text-green-600 font-semibold mt-4'>Successfully created $count test(s).</p>";
    } else {
        $message = "<p class='text-red-600 font-semibold mt-4'>Please fill all fields and select at least one class.</p>";
    }
}

/* =========================================
   FILTERS (Search / Filter Tests)
   GET params: q, f_class, from, to
========================================= */
$q        = trim($_GET['q']        ?? '');
$f_class  = trim($_GET['f_class']  ?? '');
$f_from   = trim($_GET['from']     ?? '');
$f_to     = trim($_GET['to']       ?? '');

/* =========================================
   FETCH TESTS (with filters)
========================================= */
$sql = "
    SELECT test_id, test_name, classid, total_marks, test_date, test_time
    FROM class_test
    WHERE created_by=?
";
$params = [$htpo_username];
$types  = "s";

if ($q !== '') {
    $sql .= " AND (test_name LIKE CONCAT('%', ?, '%') OR classid LIKE CONCAT('%', ?, '%'))";
    $params[] = $q; $params[] = $q;
    $types   .= "ss";
}
if ($f_class !== '') {
    $sql .= " AND classid = ?";
    $params[] = $f_class;
    $types   .= "s";
}
if ($f_from !== '') {
    $sql .= " AND test_date >= ?";
    $params[] = $f_from;
    $types   .= "s";
}
if ($f_to !== '') {
    $sql .= " AND test_date <= ?";
    $params[] = $f_to;
    $types   .= "s";
}

$sql .= " ORDER BY test_date DESC, test_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$tests = [];
while ($row = $res->fetch_assoc()) $tests[] = $row;
$stmt->close();

/* =========================================
   VIEW MARKS (when View Marks clicked)
========================================= */
$selected_test_id = $_POST['view_marks'] ?? 0;
$students = [];
$marks_map = [];
$selected_test = null;

if ($selected_test_id) {
    $stmt = $conn->prepare("SELECT * FROM class_test WHERE test_id=? AND created_by=?");
    $stmt->bind_param("is", $selected_test_id, $htpo_username);
    $stmt->execute();
    $selected_test = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($selected_test) {
        $stmt = $conn->prepare("SELECT htno, name, teamid FROM STUDENTS WHERE classid=? AND debarred=0 ORDER BY teamid, htno");
        $stmt->bind_param("s", $selected_test['classid']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($s = $res->fetch_assoc()) {
            $students[$s['htno']] = $s;
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT htno, marks_obtained FROM class_test_marks WHERE test_id=?");
        $stmt->bind_param("i", $selected_test_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $marks_map[$r['htno']] = $r['marks_obtained'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HTPO - Create & View Tests</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-4">

<div class="max-w-6xl mx-auto bg-white shadow-md rounded-xl p-6 md:p-10 mt-6">

    <h2 class="text-2xl font-bold text-center text-green-600 mb-4">Class Test Management</h2>

    <?= $message ?>

    <!-- EDIT FORM ON TOP -->
    <?php if ($edit_data): ?>
        <h2 class="text-2xl font-bold text-center text-orange-600 mb-4">
            Edit Test: <?= htmlspecialchars($edit_data['test_name']) ?>
        </h2>

        <form method="POST" class="space-y-4 bg-orange-50 p-6 rounded-lg border border-orange-300">
            <input type="hidden" name="test_id" value="<?= $edit_data['test_id'] ?>">

            <div>
                <label class="block font-semibold mb-1">Test Name:</label>
                <input type="text" name="test_name" value="<?= htmlspecialchars($edit_data['test_name']) ?>"
                       class="w-full border px-3 py-2 rounded">
            </div>

            <div>
                <label class="block font-semibold mb-1">Total Marks:</label>
                <input type="number" name="total_marks" value="<?= $edit_data['total_marks'] ?>"
                       class="w-full border px-3 py-2 rounded">
            </div>

            <div>
                <label class="block font-semibold mb-1">Class ID:</label>
                <select name="classid" class="w-full border px-3 py-2 rounded">
                    <?php foreach ($class_options as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($edit_data['classid'] == $c ? 'selected' : '') ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold mb-1">Test Date:</label>
                    <input type="date" name="test_date" value="<?= htmlspecialchars($edit_data['test_date']) ?>"
                           class="w-full border px-3 py-2 rounded">
                </div>

                <div>
                    <label class="block font-semibold mb-1">Test Time:</label>
                    <input type="time" name="test_time" value="<?= htmlspecialchars($edit_data['test_time']) ?>"
                           class="w-full border px-3 py-2 rounded">
                </div>
            </div>

            <button type="submit" name="update_test"
                    class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded">
                Update Test
            </button>
        </form>

        <hr class="my-8 border-gray-300">
    <?php endif; ?>

    <!-- CREATE TEST FORM -->
    <h2 class="text-xl font-bold text-gray-700 mb-4">Create New Test</h2>
    <form method="POST" class="space-y-4">
        <div>
            <label class="block font-semibold mb-2 text-gray-700">Select Class ID(s):</label>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($class_options as $c): ?>
                    <label class="flex items-center space-x-2 text-sm">
                        <input type="checkbox" name="classid[]" value="<?= htmlspecialchars($c) ?>"
                               class="h-4 w-4 text-green-600 border-gray-300 rounded">
                        <span><?= htmlspecialchars($c) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div>
            <label class="block font-semibold mb-2">Test Name:</label>
            <input type="text" name="test_name" required class="w-full border px-3 py-2 rounded">
        </div>

        <div>
            <label class="block font-semibold mb-2">Total Marks:</label>
            <input type="number" name="total_marks" required min="1" class="w-full border px-3 py-2 rounded">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold mb-2">Test Date:</label>
                <input type="date" name="test_date" required class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block font-semibold mb-2">Test Time:</label>
                <input type="time" name="test_time" required class="w-full border px-3 py-2 rounded">
            </div>
        </div>

        <button type="submit" name="create_test"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Create Test
        </button>
    </form>

    <!-- FILTER BAR -->
    <h2 class="text-2xl font-bold text-center text-blue-600 mt-10 mb-4">Created Tests</h2>

    <form method="GET" class="mb-4 bg-gray-50 p-4 rounded-lg border flex flex-col md:flex-row gap-3 md:items-end">
        <div class="flex-1">
            <label class="block text-sm font-semibold mb-1">Search (Name/Class):</label>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g., Unit Test"
                   class="w-full border px-3 py-2 rounded">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">Class:</label>
            <select name="f_class" class="border px-3 py-2 rounded">
                <option value="">All</option>
                <?php foreach ($class_options as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $f_class===$c?'selected':'' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">From:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($f_from) ?>" class="border px-3 py-2 rounded">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">To:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($f_to) ?>" class="border px-3 py-2 rounded">
        </div>
        <div class="flex gap-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Filter</button>
            <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">Clear</a>
        </div>
    </form>

    <?php if (!empty($tests)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm">
                <thead class="bg-green-600 text-white">
                    <tr>
                        <th class="p-2 text-left">Test Name</th>
                        <th class="p-2 text-left">Class ID</th>
                        <th class="p-2 text-left">Total</th>
                        <th class="p-2 text-left">Date</th>
                        <th class="p-2 text-left">Time</th>
                        <th class="p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tests as $t): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2"><?= htmlspecialchars($t['test_name']) ?></td>
                        <td class="p-2"><?= htmlspecialchars($t['classid']) ?></td>
                        <td class="p-2"><?= (int)$t['total_marks'] ?></td>
                        <td class="p-2"><?= htmlspecialchars($t['test_date']) ?></td>
                        <td class="p-2"><?= htmlspecialchars($t['test_time']) ?></td>
                        <td class="p-2 text-center flex flex-wrap gap-2 justify-center">

                            <!-- View Marks -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="view_marks" value="<?= (int)$t['test_id'] ?>">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                    View Marks
                                </button>
                            </form>

                            <!-- Edit -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="edit_test" value="<?= (int)$t['test_id'] ?>">
                                <button class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">
                                    Edit
                                </button>
                            </form>

                            <!-- Export CSV -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="export_csv" value="<?= (int)$t['test_id'] ?>">
                                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded">
                                    Export CSV
                                </button>
                            </form>

                            <!-- Delete -->
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this test? This will also remove its marks.');">
                                <input type="hidden" name="delete_test" value="<?= (int)$t['test_id'] ?>">
                                <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                                    Delete
                                </button>
                            </form>

                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-600 text-center mt-4">No tests found.</p>
    <?php endif; ?>

    <?php if ($selected_test): ?>
        <h2 class="text-2xl font-bold text-center text-purple-600 mt-10 mb-4">
            Marks for <?= htmlspecialchars($selected_test['test_name']) ?> (Class: <?= htmlspecialchars($selected_test['classid']) ?>)
        </h2>

        <p class="text-center text-gray-700 mb-4">
            <b>Total Marks:</b> <?= (int)$selected_test['total_marks'] ?> |
            <b>Date:</b> <?= htmlspecialchars($selected_test['test_date']) ?> |
            <b>Time:</b> <?= htmlspecialchars($selected_test['test_time']) ?>
        </p>

        <div class="overflow-x-auto">
            <table class="min-w-full border text-sm">
                <thead class="bg-purple-600 text-white">
                    <tr>
                        <th class="p-2 text-left">S.No</th>
                        <th class="p-2 text-left">HT No</th>
                        <th class="p-2 text-left">Name</th>
                        <th class="p-2 text-left">Team ID</th>
                        <th class="p-2 text-left">Marks Obtained</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($students as $htno => $s): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2"><?= $i++ ?></td>
                            <td class="p-2"><?= htmlspecialchars($s['htno']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($s['name']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($s['teamid']) ?></td>
                            <td class="p-2"><?= isset($marks_map[$htno]) ? htmlspecialchars($marks_map[$htno]) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
