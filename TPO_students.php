<?php
session_start();
include 'db.php';

// Get parameters
$prog = $_GET['prog'] ?? '';
$year = $_GET['year'] ?? '';
$classid = $_GET['classid'] ?? '';
$type = $_GET['type'] ?? '';

// Determine columns & filter
$select_cols = "u.htno, u.firstname, u.middlename, u.lastname, u.phone, u.email";

if ($type == 'tcs') {
    $select_cols .= ", p.tcs_ctdt_id";
    $filter_col = "p.tcs_codevita_reg='Yes'";
} elseif ($type == 'infosys') {
    $select_cols .= ", p.emailverifys";
    $filter_col = "p.infosys_verified=1";
} elseif ($type == 'gate') {
    $select_cols .= ", p.gate_app_id";
    $filter_col = "p.gate_applied='yes'";
} elseif ($type == 'sdet') {
    $filter_col = "p.SDET=1";
} elseif ($type == 'infoedge') {
    $filter_col = "p.infoedge_selected='Yes'";
} else {
    $filter_col = "1"; // no filter if type is empty/unknown
}

// Include classid in SELECT if showing all classes
if ($classid == 'all') {
    $select_cols .= ", u.classid";
}

// Build SQL
if ($classid == 'all') {
    $sql = "SELECT $select_cols
            FROM STUDENTS u
            LEFT JOIN placements p ON u.htno = p.htno
            WHERE u.prog=? AND u.year=? AND $filter_col
            ORDER BY u.htno";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $prog, $year);
} else {
    $sql = "SELECT $select_cols
            FROM STUDENTS u
            LEFT JOIN placements p ON u.htno = p.htno
            WHERE u.prog=? AND u.year=? AND u.classid=? AND $filter_col
            ORDER BY u.htno";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $prog, $year, $classid);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Details</title>
    <style>
        table { border-collapse: collapse; width: 90%; margin: 20px auto; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #f2f2f2; }
        .back-btn { width: 90%; margin: 20px auto; text-align: right; }
        a { text-decoration: none; padding: 5px 10px; border: 1px solid #333; border-radius: 5px; background-color: #f2f2f2; color: black; }
    </style>
</head>
<body>
<h2 style="text-align:center;">Student Details (<?= strtoupper($type ?: 'ALL') ?>)</h2>

<div class="back-btn">
    <a href="TPO_report.php?prog=<?= $prog ?>&year=<?= $year ?>">← Back to Report</a>
</div>

<table>
    <tr>
        <th>Roll No</th>
        <th>First Name</th>
        <th>Middle Name</th>
        <th>Last Name</th>
        <th>Phone</th>
        <th>Email</th>
        <?php if ($type == 'infosys') echo "<th>Email Verified</th>"; ?>
        <?php if ($type == 'tcs') echo "<th>TCS CTDT ID</th>"; ?>
        <?php if ($type == 'gate') echo "<th>GATE App ID</th>"; ?>
        <?php if ($type == 'sdet') echo "<th>Infoedge</th>"; ?>
        <?php if ($type == 'infoedge round1') echo "<th>InfoEdge Round1</th>"; ?>
        <?php if ($classid == 'all') echo "<th>Class ID</th>"; ?>
    </tr>

    <?php if ($result->num_rows > 0): 
        while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['htno']) ?></td>
        <td><?= htmlspecialchars($row['firstname']) ?></td>
        <td><?= htmlspecialchars($row['middlename']) ?></td>
        <td><?= htmlspecialchars($row['lastname']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <?php if ($type == 'infosys') echo "<td>" . htmlspecialchars($row['emailverifys']) . "</td>"; ?>
        <?php if ($type == 'tcs') echo "<td>" . htmlspecialchars($row['tcs_ctdt_id']) . "</td>"; ?>
        <?php if ($type == 'gate') echo "<td>" . htmlspecialchars($row['gate_app_id']) . "</td>"; ?>
        <?php if ($type == 'sdet') echo "<td>Yes</td>"; ?>
        <?php if ($type == 'infoedge') echo "<td>Yes</td>"; ?>
        <?php if ($classid == 'all') echo "<td>" . htmlspecialchars($row['classid']) . "</td>"; ?>
    </tr>
    <?php endwhile; 
    else: 
        $colspan = 6;
        if (in_array($type, ['infosys','tcs','gate','sdet','infoedge'])) $colspan += 1;
        if ($classid == 'all') $colspan += 1;
    ?>
    <tr><td colspan="<?= $colspan ?>">No students found</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
