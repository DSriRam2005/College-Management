<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    die("ACCESS DENIED");
}

$error = "";
$success = 0;
$failed = 0;

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
        $error = "Upload a valid CSV file.";
    } else {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

        // Skip header row
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $htno = trim($row[0]);
            $st_phone = trim($row[1]);
            $f_phone = trim($row[2]);
            $classid = trim($row[3]);

            if ($htno == "") {
                $failed++;
                continue;
            }

            $stmt = $conn->prepare("
                UPDATE STUDENTS 
                SET st_phone=?, f_phone=?, classid=? 
                WHERE htno=?
            ");

            $stmt->bind_param("ssss", $st_phone, $f_phone, $classid, $htno);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success++;
            } else {
                $failed++;
            }
        }

        fclose($file);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Student Phone Numbers</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .box { padding: 20px; border: 1px solid #aaa; width: 400px; }
    </style>
</head>
<body>

<h2>Upload CSV to Update Students (Phones + Class)</h2>

<div class="box">
    <form method="POST" enctype="multipart/form-data">
        <label>Select CSV File:</label><br>
        <input type="file" name="csv_file" required><br><br>
        <button type="submit">Upload & Update</button>
    </form>
</div>

<?php if ($success + $failed > 0): ?>
    <h3>Update Report</h3>
    <p><b>Updated:</b> <?= $success ?></p>
    <p><b>Failed / No Record Found:</b> <?= $failed ?></p>
<?php endif; ?>

</body>
</html>
