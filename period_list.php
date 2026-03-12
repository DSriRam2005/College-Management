<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include 'db.php';

// ✅ Only CPTO allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: spoclogin.php");
    exit();
}

// ✅ Must have classid in session
if (!isset($_SESSION['classid']) || empty($_SESSION['classid'])) {
    die("<b>ERROR:</b> classid missing.");
}

$classid = $_SESSION['classid'];
$spocName = $_SESSION['spoc_name'] ?? 'CPTO';

/* ✅ FIXED QUERY: classid included in SELECT and GROUP BY */
$sql = "
    SELECT date, classid,
        MAX(CASE WHEN period_no = 1 THEN CONCAT(subject_name, ' (', faculty_name, ')') END) AS p1,
        MAX(CASE WHEN period_no = 2 THEN CONCAT(subject_name, ' (', faculty_name, ')') END) AS p2,
        MAX(CASE WHEN period_no = 3 THEN CONCAT(subject_name, ' (', faculty_name, ')') END) AS p3,
        MAX(CASE WHEN period_no = 4 THEN CONCAT(subject_name, ' (', faculty_name, ')') END) AS p4
    FROM periods
    WHERE classid = ?
    GROUP BY date, classid
    ORDER BY date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Daily Period Log - View</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 25px;
    background: #f1f5f9;
    color: #1e293b;
}
h2 {
    font-weight: 600;
    font-size: 1.6rem;
    color: #1e293b;
    margin-bottom: 25px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 10px;
}
.table-container {
    overflow-x: auto;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 10px;
    background: white;
}
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}
th, td {
    padding: 12px 15px;
    border: 1px solid #e2e8f0;
    text-align: center;
}
th {
    background: #2563eb;
    color: white;
    font-weight: 600;
}
tr:nth-child(even) {
    background: #eef6ff;
}
td:first-child {
    background: #fafafa;
    font-weight: 600;
}
td:empty::before {
    content: "-";
    color: #94a3b8;
}
</style>
</head>
<body>

<h2>Daily Log Summary — <?php echo htmlspecialchars($spocName); ?> (<?php echo htmlspecialchars($classid); ?>)</h2>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Period 1</th>
            <th>Period 2</th>
            <th>Period 3</th>
            <th>Period 4</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['p1'] ?: "-"); ?></td>
                    <td><?php echo htmlspecialchars($row['p2'] ?: "-"); ?></td>
                    <td><?php echo htmlspecialchars($row['p3'] ?: "-"); ?></td>
                    <td><?php echo htmlspecialchars($row['p4'] ?: "-"); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="padding:20px; color:#64748b;">No period logs found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php $stmt->close(); ?>
</body>
</html>
