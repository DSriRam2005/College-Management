<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php"; // gives $conn

$prog = "DIP";
$year = "25";

// Fetch students (exclude debarred)
$students = $conn->query("
    SELECT id, classid, college, prog, year, ZONE, htno, name, st_phone, f_phone, ref, debarred
    FROM STUDENTS
    WHERE prog='$prog' 
      AND year='$year'
      AND (debarred = 0 OR debarred IS NULL)
    ORDER BY classid, htno
");

// Monthly stats
function getMonthStats($conn, $htno, $month, $yearFull) {
    $sql = "
        SELECT 
            COUNT(*) AS total_days,
            SUM(status='Present') AS present_days,
            SUM(status='Absent') AS absent_days
        FROM attendance
        WHERE htno='$htno'
        AND MONTH(att_date)='$month'
        AND YEAR(att_date)='$yearFull'
    ";
    return $conn->query($sql)->fetch_assoc();
}

// Total stats
function getTotalStats($conn, $htno) {
    $sql = "
        SELECT 
            COUNT(*) AS total_days,
            SUM(status='Present') AS present_days,
            SUM(status='Absent') AS absent_days
        FROM attendance
        WHERE htno='$htno'
    ";
    return $conn->query($sql)->fetch_assoc();
}

// Months from AUG → DEC
$months = [
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];

$currentYear = date("Y");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report (AUG–DEC)</title>
    <style>
        body { font-family: Arial; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        th {
            background: #e6e6e6;
        }
    </style>
</head>
<body>

<h2>Attendance Report – B.TECH | YEAR: 25 | AUG–DEC</h2>

<table>
    <tr>
        <th rowspan="2">SNO</th>
        
        <th rowspan="2">CLASSID</th>
        <th rowspan="2">COLLEGE</th>

        <th rowspan="2">ZONE</th>
        <th rowspan="2">HTNO</th>
        <th rowspan="2">NAME</th>
        <th rowspan="2">STU PHONE</th>
        <th rowspan="2">F PHONE</th>
        <th rowspan="2">REF</th>

        <?php foreach ($months as $num => $name): ?>
            <th colspan="4"><?= $name; ?></th>
        <?php endforeach; ?>

        <th colspan="4">TOTAL</th>
    </tr>

    <tr>
        <?php foreach ($months as $num => $name): ?>
            <th>T</th><th>P</th><th>A</th><th>PP%</th>
        <?php endforeach; ?>

        <th>T</th><th>P</th><th>A</th><th>PP%</th>
    </tr>

<?php
$sno = 1;
while ($stu = $students->fetch_assoc()) {

    $htno = $stu['htno'];

    // Total attendance
    $tstat = getTotalStats($conn, $htno);

    $tt  = $tstat['total_days']   ?? 0;
    $tp  = $tstat['present_days'] ?? 0;
    $ta  = $tstat['absent_days']  ?? 0;
    $tpp = ($tt > 0) ? round(($tp / $tt) * 100, 2) : 0;
?>
    <tr>
        <td><?= $sno++; ?></td>
        
        <td><?= $stu['classid']; ?></td>
        <td><?= $stu['college']; ?></td>
       
        <td><?= $stu['ZONE']; ?></td>
        <td><?= $stu['htno']; ?></td>
        <td><?= $stu['name']; ?></td>
        <td><?= $stu['st_phone']; ?></td>
        <td><?= $stu['f_phone']; ?></td>
        <td><?= $stu['ref']; ?></td>

        <?php
        // Month-wise AUG–DEC
        foreach ($months as $mnum => $mname) {

            $ms = getMonthStats($conn, $htno, $mnum, $currentYear);

            $mt  = $ms['total_days']   ?? 0;
            $mp  = $ms['present_days'] ?? 0;
            $ma  = $ms['absent_days']  ?? 0;
            $mpp = ($mt > 0) ? round(($mp / $mt) * 100, 2) : 0;

            echo "<td>$mt</td>";
            echo "<td>$mp</td>";
            echo "<td>$ma</td>";
            echo "<td>$mpp%</td>";
        }
        ?>

        <td><?= $tt; ?></td>
        <td><?= $tp; ?></td>
        <td><?= $ta; ?></td>
        <td><?= $tpp; ?>%</td>
    </tr>

<?php } ?>

</table>

</body>
</html>
