<?php
session_start();
include 'db.php';

// Only allow HTPO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

$htpo_username = $_SESSION['username'];

// Fetch HTPO user's program, year, college(s)
$stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username = ?");
$stmt->bind_param("s", $htpo_username);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$prog = $user['prog'];
$year = $user['year'];
$college_csv = $user['college']; // can be comma-separated

$colleges = array_map('trim', explode(',', $college_csv)); // array of colleges
$college_placeholders = implode(',', array_fill(0, count($colleges), '?'));

// Filters
$filter_classid = $_GET['classid'] ?? '';
$filter_debar = $_GET['debarred'] ?? '';

// --- Fetch distinct classids for dropdown ---
$class_sql = "SELECT DISTINCT classid FROM STUDENTS 
              WHERE prog=? AND year=? AND college IN ($college_placeholders) 
              ORDER BY classid";
$class_stmt = $conn->prepare($class_sql);

$types = 'ss' . str_repeat('s', count($colleges));
$params = array_merge([$prog, $year], $colleges);
$class_stmt->bind_param($types, ...$params);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

$class_options = [];
while($row = $class_result->fetch_assoc()){
    if(!empty($row['classid'])) $class_options[] = $row['classid'];
}

// --- Build main query ---
$sql = "SELECT htno, name AS full_name, classid, prog, year, debarred
        FROM STUDENTS
        WHERE prog=? AND year=? AND college IN ($college_placeholders)";

$params = [$prog, $year];
$types = 'ss';
$params = array_merge($params, $colleges);
$types .= str_repeat('s', count($colleges));

// Filters
if(!empty($filter_classid)){
    $sql .= " AND classid=?";
    $params[] = $filter_classid;
    $types .= 's';
}

if($filter_debar === "0" || $filter_debar === "1"){
    $sql .= " AND debarred=?";
    $params[] = $filter_debar;
    $types .= 'i';
}

$sql .= " ORDER BY classid, name";

$res = $conn->prepare($sql);
$res->bind_param($types, ...$params);
$res->execute();
$result = $res->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>HTPO Student List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="p-3">
<div class="container-fluid">
    <h2 class="mb-4">My Students (Debar Status)</h2>
    <p>College: <strong><?= htmlspecialchars($college_csv) ?></strong>, Program: <strong><?= htmlspecialchars($prog) ?></strong>, Year: <strong><?= $year ?></strong></p>

    <!-- Filters Form -->
    <form method="get" class="row g-3 mb-3 align-items-center">
        <div class="col-auto">
            <label for="classid" class="form-label">Class ID:</label>
            <select name="classid" id="classid" class="form-select">
                <option value="">-- All --</option>
                <?php foreach($class_options as $cid): ?>
                    <option value="<?= htmlspecialchars($cid) ?>" <?= $filter_classid == $cid ? 'selected' : '' ?>><?= htmlspecialchars($cid) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label for="debarred" class="form-label">Debarred:</label>
            <select name="debarred" id="debarred" class="form-select">
                <option value="">-- All --</option>
                <option value="1" <?= $filter_debar === "1" ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= $filter_debar === "0" ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-auto mt-4">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
        </div>
    </form>

    <!-- Student Table -->
    <table id="htpoTable" class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>HT No</th>
                <th>Full Name</th>
                <th>Class ID</th>
                <th>Program</th>
                <th>Year</th>
                <th>Debarred</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['htno'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['classid'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['prog'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['year'] ?? '') ?></td>
                    <td>
                        <?php if(($row['debarred'] ?? 0) == 1): ?>
                            <span class="badge bg-danger">Yes</span>
                        <?php else: ?>
                            <span class="badge bg-success">No</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No records found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="htpo_home.php" class="btn btn-secondary mt-3">Back to Home</a>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#htpoTable').DataTable({
        paging: true,
        info: true,
        searching: true,
        ordering: true
    });
});
</script>
</body>
</html>
