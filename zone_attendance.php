<?php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Kolkata');

// ------------------------------
// ✅ Access control
// ------------------------------
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['PR', 'CPTO', 'ZONE'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// ------------------------------
// ✅ Get Zone of logged-in user
// ------------------------------
$stmt = $conn->prepare("SELECT ZONE FROM USERS WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData || empty($userData['ZONE'])) {
    die("<h3 style='color:red;text-align:center;'>⚠️ Zone not set for this user.</h3>");
}
$zone = $userData['ZONE'];

// ------------------------------
// ✅ Fetch Attendance Data
// ------------------------------
$query = "
SELECT 
    s.htno,
    s.name,
    s.classid,
    a.att_date,
    a.status
FROM STUDENTS s
LEFT JOIN attendance a ON s.htno = a.htno
WHERE s.ZONE = ?
ORDER BY a.att_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $zone);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zone Attendance Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f9f9f9; }
        .container { margin-top: 40px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; }
        table { font-size: 14px; }
        .status-present { color: green; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>📋 Attendance Report - <?= htmlspecialchars($zone) ?> Zone</h2>
    <hr>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr class="text-center">
                    <th>#</th>
                    <th>HT No</th>
                    <th>Student Name</th>
                    <th>Class ID</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                <tr class="text-center">
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['htno'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['classid'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['att_date'] ?? '—') ?></td>
                    <td class="<?= $row['status'] == 'Present' ? 'status-present' : 'status-absent' ?>">
                        <?= htmlspecialchars($row['status'] ?? '—') ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center text-danger">No attendance records found for students in <?= htmlspecialchars($zone) ?> zone.</p>
    <?php endif; ?>
</div>
</body>
</html>
