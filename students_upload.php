<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php"; // DB connection

// OPTIONAL: restrict access
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die("ACCESS DENIED");
// }

$msg = "";

if (isset($_POST['upload'])) {

    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== 0) {
        $msg = "❌ Please select a valid CSV file";
    } else {

        $file = fopen($_FILES['csv']['tmp_name'], "r");

        // Read header row
        $header = fgetcsv($file);

        $expected = ['prog','classid','year','branch','htno','name','gen','college','debarred'];

        if ($header !== $expected) {
            die("❌ CSV header mismatch. Required:<br>" . implode(", ", $expected));
        }

        $stmt = $conn->prepare("
            INSERT INTO STUDENTS
            (prog, classid, year, branch, htno, name, gen, college, debarred)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;

        while (($row = fgetcsv($file)) !== false) {

            list($prog,$classid,$year,$branch,$htno,$name,$gen,$college,$debarred) = $row;

            // Basic cleanup
            $year = (int)$year;
            $debarred = (int)$debarred;
            $gen = strtoupper(trim($gen)) === 'F' ? 'F' : 'M';

            $stmt->bind_param(
                "ssisssssi",
                $prog,
                $classid,
                $year,
                $branch,
                $htno,
                $name,
                $gen,
                $college,
                $debarred
            );

            if ($stmt->execute()) {
                $count++;
            }
        }

        fclose($file);
        $msg = "✅ Successfully uploaded $count students";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Students CSV</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:40px; }
        .box {
            width:400px; margin:auto; background:#fff;
            padding:25px; border-radius:8px;
            box-shadow:0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align:center; }
        input[type=file], button {
            width:100%; padding:10px; margin-top:10px;
        }
        button {
            background:#1f2f86; color:#fff;
            border:none; cursor:pointer;
        }
        .msg { margin-top:15px; text-align:center; font-weight:bold; }
    </style>
</head>
<body>

<div class="box">
    <h2>Upload Students CSV</h2>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv" accept=".csv" required>
        <button type="submit" name="upload">Upload</button>
    </form>

    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>
</div>

</body>
</html>
