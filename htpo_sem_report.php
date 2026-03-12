<?php
session_start();
include 'db2.php'; // ✅ DB connection

// ✅ Allow only HTPO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

// ✅ Get HTPO’s year + username from session
$htpo_year  = $_SESSION['year'] ?? null;
$htpo_user  = $_SESSION['username'] ?? "";

// ✅ Detect college from HTPO username (example: 25KTHTPO → KIET)
$college_filter = null;
if (stripos($htpo_user, "KT") !== false) {
    $college_filter = "KIET";
} elseif (stripos($htpo_user, "KW") !== false) {
    $college_filter = "KIEW";
}

// ✅ Branch filter (optional)
$branch_filter = isset($_GET['branch']) ? $_GET['branch'] : "";

// ✅ Fetch distinct branches (restricted by year + college + B.TECH)
$branch_sql = "SELECT DISTINCT classid 
               FROM STUDENTS 
               WHERE year = ? AND prog = 'B.TECH' AND college = ?";
$stmt = $conn->prepare($branch_sql);
$stmt->bind_param("is", $htpo_year, $college_filter);
$stmt->execute();
$branch_result = $stmt->get_result();

// ✅ Build student query
$sql = "SELECT id, prog, classid, year, htno, name, college, teamid,
               tfdue_today, otdues_today, busdue_today, hosdue_today, olddue_today
        FROM STUDENTS
        WHERE year = ? AND prog = 'B.TECH' AND college = ?";

$params = [$htpo_year, $college_filter];
$types  = "is";

if (!empty($branch_filter)) {
    $sql .= " AND classid = ?";
    $params[] = $branch_filter;
    $types .= "s";
}

$sql .= " ORDER BY classid, name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HTPO - Students</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f7; margin: 0; padding: 20px; }
    h1 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
    form { text-align: center; margin-bottom: 20px; }
    select, button { padding: 8px 12px; font-size: 14px; margin: 5px; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th, td { padding: 10px 12px; border: 1px solid #ddd; text-align: center; }
    th { background: #34495e; color: #fff; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #f1f1f1; }
    .dues.red { font-weight: bold; color: #e74c3c; }
    .dues.green { font-weight: bold; color: green; }
</style>
</head>
<body>

<h1>📄 B.TECH Students (Year <?= htmlspecialchars($htpo_year); ?> – <?= htmlspecialchars($college_filter); ?>)</h1>

<!-- ✅ Branch Filter -->
<form method="get" action="">
    <label for="branch">Branch:</label>
    <select name="branch" id="branch">
        <option value="">-- All Branches --</option>
        <?php while ($branch = $branch_result->fetch_assoc()): ?>
            <option value="<?= $branch['classid']; ?>" 
                <?= ($branch_filter == $branch['classid']) ? "selected" : ""; ?>>
                <?= $branch['classid']; ?>
            </option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Apply</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Program</th>
        <th>Class</th>
        <th>Year</th>
        <th>Hall Ticket</th>
        <th>Name</th>
        <th>College</th>
        <th>Team ID</th>
        <th>TF Dues</th>
        <th>OT Dues</th>
        <th>Bus Dues</th>
        <th>Hostel Dues</th>
        <th>Old Dues</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= $row['prog']; ?></td>
            <td><?= $row['classid']; ?></td>
            <td><?= $row['year']; ?></td>
            <td><?= $row['htno']; ?></td>
            <td><?= $row['name']; ?></td>
            <td><?= $row['college']; ?></td>
            <td><?= $row['teamid']; ?></td>

            <!-- ✅ Conditional dues formatting -->
<!-- ✅ Conditional dues formatting, no negatives -->
<td class="dues <?= ($row['tfdue_today'] <= 0) ? 'green' : 'red'; ?>">
    <?= number_format(max(0, $row['tfdue_today']), 2); ?>
</td>
<td class="dues <?= ($row['otdues_today'] <= 0) ? 'green' : 'red'; ?>">
    <?= number_format(max(0, $row['otdues_today']), 2); ?>
</td>
<td class="dues <?= ($row['busdue_today'] <= 0) ? 'green' : 'red'; ?>">
    <?= number_format(max(0, $row['busdue_today']), 2); ?>
</td>
<td class="dues <?= ($row['hosdue_today'] <= 0) ? 'green' : 'red'; ?>">
    <?= number_format(max(0, $row['hosdue_today']), 2); ?>
</td>
<td class="dues <?= ($row['olddue_today'] <= 0) ? 'green' : 'red'; ?>">
    <?= number_format(max(0, $row['olddue_today']), 2); ?>
</td>

        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="13">No B.TECH students found.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
