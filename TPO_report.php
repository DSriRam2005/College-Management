<?php
session_start();
include 'db.php'; // your DB connection

// Fetch distinct programs & years
$prog_result = $conn->query("SELECT DISTINCT prog FROM STUDENTS ORDER BY prog");
$year_result = $conn->query("SELECT DISTINCT year FROM STUDENTS ORDER BY year");

// Default filters
$default_prog = $prog_result->fetch_assoc()['prog'] ?? 'B.TECH';
$prog_result->data_seek(0);
$default_year = $year_result->fetch_assoc()['year'] ?? 22;
$year_result->data_seek(0);

$prog = isset($_GET['prog']) ? $_GET['prog'] : $default_prog;
$year = isset($_GET['year']) ? $_GET['year'] : $default_year;

// ✅ Query: class-wise counts including InfoEdge
$sql = "SELECT 
            u.classid,
            COUNT(u.htno) AS total_students,
            SUM(CASE WHEN p.tcs_codevita_reg='Yes' THEN 1 ELSE 0 END) AS tcs_yes,
            SUM(CASE WHEN p.infosys_verified=1 THEN 1 ELSE 0 END) AS infosys_verified,
            SUM(CASE WHEN p.gate_applied='yes' THEN 1 ELSE 0 END) AS gate_applied,
            SUM(CASE WHEN p.SDET=1 THEN 1 ELSE 0 END) AS sdet_yes,
            SUM(CASE WHEN p.infoedge_selected='Yes' THEN 1 ELSE 0 END) AS infoedge_yes
        FROM STUDENTS u
        LEFT JOIN placements p ON u.htno = p.htno
        WHERE u.prog = ? AND u.year = ?
        GROUP BY u.classid
        ORDER BY u.classid";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $prog, $year);
$stmt->execute();
$result = $stmt->get_result();

// Initialize totals
$total_students = 0;
$total_tcs = 0;
$total_infosys = 0;
$total_gate = 0;
$total_sdet = 0;
$total_infoedge = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Class-wise Placement Report</title>
    <style>
        table { border-collapse: collapse; width: 90%; margin: 20px auto; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #f2f2f2; }
        .filter-box { width: 90%; margin: 20px auto; display: flex; gap: 20px; }
        .filter-box div { display: flex; flex-direction: column; }
        .filter-box label { font-weight: bold; margin-bottom: 5px; }
        a.count-link { text-decoration: none; color: blue; font-weight: bold; }
    </style>
</head>
<body>
<h2 style="text-align:center;">Class-wise Placement Report</h2>

<!-- Filters -->
<form method="GET" class="filter-box">
    <div>
        <label>Program</label>
        <select name="prog" onchange="this.form.submit()">
            <?php while($row = $prog_result->fetch_assoc()): ?>
                <option value="<?= $row['prog'] ?>" <?php if($prog == $row['prog']) echo "selected"; ?>><?= $row['prog'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Year</label>
        <select name="year" onchange="this.form.submit()">
            <?php while($row = $year_result->fetch_assoc()): ?>
                <option value="<?= $row['year'] ?>" <?php if($year == $row['year']) echo "selected"; ?>><?= $row['year'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</form>

<!-- Report Table -->
<table>
    <tr>
        <th>Class ID</th>
        <th>Total Students</th>
        <th>TCS CodeVita Registered</th>
        <th>Infosys Verified</th>
        <th>GATE Applied</th>
        <th>Infoedge Yes</th>
        <th>InfoEdge Round 1</th>
    </tr>
    <?php if($result->num_rows > 0):
        while($row = $result->fetch_assoc()):
            $total_students += $row['total_students'];
            $total_tcs += $row['tcs_yes'];
            $total_infosys += $row['infosys_verified'];
            $total_gate += $row['gate_applied'];
            $total_sdet += $row['sdet_yes'];
            $total_infoedge += $row['infoedge_yes'];
            $classid = $row['classid'];
    ?>
    <tr>
        <td><?= $classid ?></td>
        <td><?= $row['total_students'] ?></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=<?= $classid ?>&type=tcs"><?= $row['tcs_yes'] ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=<?= $classid ?>&type=infosys"><?= $row['infosys_verified'] ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=<?= $classid ?>&type=gate"><?= $row['gate_applied'] ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=<?= $classid ?>&type=sdet"><?= $row['sdet_yes'] ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=<?= $classid ?>&type=infoedge"><?= $row['infoedge_yes'] ?></a></td>
    </tr>
    <?php endwhile; ?>
    <!-- Totals Row -->
    <tr style="font-weight:bold; background-color:#f9f9f9;">
        <td>Total</td>
        <td><?= $total_students ?></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=all&type=tcs"><?= $total_tcs ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=all&type=infosys"><?= $total_infosys ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=all&type=gate"><?= $total_gate ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=all&type=sdet"><?= $total_sdet ?></a></td>
        <td><a class="count-link" href="TPO_students.php?prog=<?= $prog ?>&year=<?= $year ?>&classid=all&type=infoedge"><?= $total_infoedge ?></a></td>
    </tr>
    <?php else: ?>
    <tr><td colspan="7">No data found</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
