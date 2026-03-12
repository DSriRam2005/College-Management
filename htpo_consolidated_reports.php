<?php
session_start();
include 'db.php'; // your database connection

// ✅ Check if user is logged in and role is HTPO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

// Get HTPO user's prog, year, and college
$htpo_username = $_SESSION['username'];

// Fetch user info from USERS table
$user_stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username = ?");
$user_stmt->bind_param("s", $htpo_username);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

$prog = $user['prog'];
$year = $user['year'];
$college = $user['college'];

// Fetch class-wise report for this prog, year, and college
$query = "
    SELECT 
        classid,
        COUNT(*) AS total_students,
        SUM(tfdue_12_9 + otdues_12_9 + busdue_12_9 + hosdue_12_9 + olddue_12_9) AS total_fee,
        SUM(tfdue_today + otdues_today + busdue_today + hosdue_today + olddue_today) AS total_dues,
        SUM(
            (tfdue_12_9 + otdues_12_9 + busdue_12_9 + hosdue_12_9 + olddue_12_9)
            - (tfdue_today + otdues_today + busdue_today + hosdue_today + olddue_today)
        ) AS total_paid
    FROM STUDENTS
  WHERE prog = ? AND year = ?
      AND (debarred = 0 OR debarred IS NULL)
      AND FIND_IN_SET(college, ?)
    GROUP BY classid
    ORDER BY classid
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sis", $prog, $year, $college);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Calculate grand totals
$grand_total_students = 0;
$grand_total_fee = 0;
$grand_total_dues = 0;
$grand_total_paid = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HTPO Class-wise Fee Report</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f2f2f2; }
    tfoot td { font-weight: bold; background-color: #e2e2e2; }
    a { color: blue; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
</head>
<body>

<h2>Class-wise Fee Report</h2>
<p>Program: <?= htmlspecialchars($prog) ?>, Year: <?= $year ?>, College: <?= htmlspecialchars($college) ?></p>

<table>
    <thead>
        <tr>
            <th>Class ID</th>
            <th>Total Students</th>
            <th>Total Fee</th>
            <th>Total Dues</th>
            <th>Total Paid</th>
            <th>Paid Percentage</th>
        </tr>
    </thead>
    <tbody>
    <?php if($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php 
                $paid_percentage = $row['total_fee'] > 0 ? ($row['total_paid'] / $row['total_fee'] * 100) : 0;

                // ✅ Add to grand totals
                $grand_total_students += $row['total_students'];
                $grand_total_fee += $row['total_fee'];
                $grand_total_dues += $row['total_dues'];
                $grand_total_paid += $row['total_paid'];

                // URL encode parameters for safe GET
                $classid_param = urlencode($row['classid']);
                $prog_param = urlencode($prog);
                $year_param = urlencode($year);
                $college_param = urlencode($college);
            ?>
            <tr>
                <td>
                    <a href="view_due_reports.php?classid=<?= $classid_param ?>&prog=<?= $prog_param ?>&year=<?= $year_param ?>&college=<?= $college_param ?>">
                        <?= htmlspecialchars($row['classid']) ?>
                    </a>
                </td>
                <td><?= $row['total_students'] ?></td>
                <td><?= number_format($row['total_fee'], 2) ?></td>
                <td><?= number_format($row['total_dues'], 2) ?></td>
                <td><?= number_format($row['total_paid'], 2) ?></td>
                <td><?= number_format($paid_percentage, 2) ?>%</td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <td><strong>Total</strong></td>
            <td><?= $grand_total_students ?></td>
            <td><?= number_format($grand_total_fee, 2) ?></td>
            <td><?= number_format($grand_total_dues, 2) ?></td>
            <td><?= number_format($grand_total_paid, 2) ?></td>
            <td><?= $grand_total_fee > 0 ? number_format(($grand_total_paid / $grand_total_fee * 100), 2) : 0 ?>%</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
