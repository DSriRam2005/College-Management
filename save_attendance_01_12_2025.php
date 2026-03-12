<?php
session_start();
if (!isset($_SESSION['role'])) { die("ACCESS DENIED"); }

include "db.php";

$target_date = "2025-12-01";

$classid = $_POST['classid'];
$present_list = $_POST['present'] ?? [];  // checkboxes

// Get all unmarked students again
$students = $conn->query("
    SELECT htno FROM STUDENTS
    WHERE classid = '$classid'
    AND htno NOT IN (SELECT htno FROM attendance WHERE att_date = '$target_date')
");

while ($row = $students->fetch_assoc()) {
    $htno = $row['htno'];

    // If checkbox present → Present else Absent
    $status = in_array($htno, $present_list) ? "Present" : "Absent";

    $conn->query("
        INSERT INTO attendance (htno, classid, att_date, status)
        VALUES ('$htno', '$classid', '$target_date', '$status')
    ");
}

echo "<script>
alert('Attendance Saved Successfully!');
window.location='mark_attendance_01_12_2025.php?classid=$classid';
</script>";
?>
