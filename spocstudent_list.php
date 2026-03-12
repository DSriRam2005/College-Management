<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

$conn->query("SET SQL_BIG_SELECTS=1");
date_default_timezone_set('Asia/Kolkata');

// ✅ Allow PR, ADMIN, CPTO only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR', 'ADMIN', 'CPTO'])) {
    header("Location: index.php");
    exit();
}

// ✅ Get classid (PR/Admin via GET, CPTO via session)
$classid = $_GET['classid'] ?? ($_SESSION['classid'] ?? null);
if (!$classid) {
    die("<h3 style='color:red;text-align:center;'>❌ No class selected.</h3>");
}

// ✅ Fetch students of the given class
$stmt = $conn->prepare("
    SELECT htno, name, teamid, phone, ZONE, prog, year, college
    FROM STUDENTS 
    WHERE classid = ?
    ORDER BY 
        SUBSTRING_INDEX(teamid, '_', 1) ASC,
        CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) ASC,
        name ASC
");
$stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List - <?= htmlspecialchars($classid) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        h3 { font-weight: bold; margin-bottom: 20px; }
        table th, table td { vertical-align: middle; }
    </style>
</head>
<body>

<div class="container bg-white shadow rounded p-4">
    <h3 class="text-center text-primary">📘 Student List for Class: <?= htmlspecialchars($classid) ?></h3>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>HT No</th>
                    <th>Name</th>
                    <th>Team ID</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $sno = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$sno}</td>";
                        echo "<td>" . htmlspecialchars($row['htno']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['teamid'] ?? '—') . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone'] ?? '—') . "</td>";
                        echo "</tr>";
                        $sno++;
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center text-muted'>No students found for this class.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
