<?php
session_start();
include 'db.php';

// Show PHP errors temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only HTPO allowed
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'HTPO'){
    die("Access denied. Only HTPO allowed.");
}

$htpo_username = $_SESSION['username'];

// Fetch assigned data for this HTPO (ignore classid)
$stmt = $conn->prepare("
    SELECT DISTINCT year, college 
    FROM USERS 
    WHERE username=? AND role='HTPO'
");
$stmt->bind_param("s", $htpo_username);
$stmt->execute();
$result = $stmt->get_result();
$assigned_data = $result->fetch_assoc();

if(!$assigned_data){
    die("No assigned data found for your account.");
}

$year = $assigned_data['year'];
$college = $assigned_data['college'];

// Fetch students for this year and college (ignore classid)
$stmt2 = $conn->prepare("
    SELECT id, htno, name, teamid 
    FROM STUDENTS 
    WHERE year=? AND college=?
    ORDER BY name ASC
");
$stmt2->bind_param("is", $year, $college);
$stmt2->execute();
$students = $stmt2->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>HTPO Attendance Report</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h2>HTPO Attendance Report</h2>
<p>Year: <?= $year ?> | College: <?= $college ?></p>

<table>
    <tr>
        <th>S.No</th>
        <th>HTNO</th>
        <th>Name</th>
        <th>TeamID</th>
        <th>Total Days</th>
        <th>Present Days</th>
        <th>Absent Days</th>
    </tr>

<?php
if($students->num_rows > 0){
    $sno = 1;
    while($row = $students->fetch_assoc()){
        $htno = $row['htno'];

        // Attendance counts
        $totalDaysResult = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE htno='$htno'");
        $totalDays = $totalDaysResult ? $totalDaysResult->fetch_assoc()['total'] : 0;

        $presentDaysResult = $conn->query("SELECT COUNT(*) as present FROM attendance WHERE htno='$htno' AND status='Present'");
        $presentDays = $presentDaysResult ? $presentDaysResult->fetch_assoc()['present'] : 0;

        $absentDaysResult = $conn->query("SELECT COUNT(*) as absent FROM attendance WHERE htno='$htno' AND status='Absent'");
        $absentDays = $absentDaysResult ? $absentDaysResult->fetch_assoc()['absent'] : 0;

        echo "<tr>
                <td>{$sno}</td>
                <td>{$htno}</td>
                <td>{$row['name']}</td>
                <td>{$row['teamid']}</td>
                <td>{$totalDays}</td>
                <td>{$presentDays}</td>
                <td>{$absentDays}</td>
              </tr>";

        $sno++;
    }
} else {
    echo "<tr><td colspan='7'>No students assigned to you.</td></tr>";
}
?>
</table>
</body>
</html>
