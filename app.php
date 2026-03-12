<?php
// Show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include DB connection
include 'db.php';

// Get Hall Ticket Number
$htno = $_GET['htno'] ?? '';
if (empty($htno)) {
    die("<p style='color:red;text-align:center;'>Hall Ticket Number not provided!</p>");
}

// Fetch student details
$sql = "SELECT * FROM STUDENTS WHERE htno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $htno);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("<p style='color:red;text-align:center;'>Student not found!</p>");
}

// Fetch registered subjects
$sub_sql = "SELECT code, name FROM STUDENT_SUBJECTS WHERE htno = ?";
$sub_stmt = $conn->prepare($sub_sql);
$sub_stmt->bind_param("s", $htno);
$sub_stmt->execute();
$sub_result = $sub_stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exam Application Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2, h3 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { border: 1px solid #000; padding: 6px; font-size: 14px; }
        .noborder td { border: none; }
        .sign { margin-top: 60px; display: flex; justify-content: space-between; }
        .sign div { text-align: center; width: 45%; }
        .print-btn { margin: 15px 0; text-align: center; }
        .print-btn button { padding: 10px 20px; font-size: 16px; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <h2>JAWAHARLAL NEHRU TECHNOLOGICAL UNIVERSITY, KAKINADA</h2>
    <h3>Application Form for Registration of B.Tech/B. Pharmacy End Examinations</h3>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <table class="noborder">
        <tr><td><b>Hall Ticket No:</b> <?= htmlspecialchars($student['htno']); ?></td></tr>
        <tr><td><b>Name of Candidate:</b> <?= htmlspecialchars($student['name']); ?></td></tr>
        <tr><td><b>Father's/Guardian's Name:</b> ________________________</td></tr>
        <tr><td><b>Branch & Specialization:</b> <?= htmlspecialchars($student['prog']); ?></td></tr>
        <tr><td><b>Class ID:</b> <?= htmlspecialchars($student['classid']); ?></td></tr>
        <tr><td><b>Year:</b> <?= htmlspecialchars($student['year']); ?></td></tr>
        <tr><td><b>Date of Birth:</b> <?= htmlspecialchars($student['dob'] ?? '________'); ?> 
                <b>Sex:</b> <?= htmlspecialchars($student['sex'] ?? 'Male / Female'); ?></td></tr>
    </table>

    <h3>Details of Fee Paid</h3>
    <table>
        <tr><th>DD/Challan No.</th><th>Date</th><th>Amount (Rs.)</th><th>Bank & Place</th></tr>
        <tr><td>________</td><td>________</td><td>________</td><td>________</td></tr>
    </table>

    <h3>Subjects Registered</h3>
    <table>
        <tr><th>SNo</th><th>Subject Code</th><th>Subject Name</th></tr>
        <?php
        $i = 1;
        while ($sub = $sub_result->fetch_assoc()) {
            echo "<tr><td>{$i}</td><td>".htmlspecialchars($sub['code'])."</td><td>".htmlspecialchars($sub['name'])."</td></tr>";
            $i++;
        }
        ?>
    </table>

    <div class="sign">
        <div>Signature of Candidate</div>
        <div>Signature of Principal with Seal</div>
    </div>
</body>
</html>
