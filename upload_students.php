<?php
session_start();
require_once 'db.php'; // mysqli connection: $conn

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN','CPTO'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$error = "";

/* ================= UPLOAD LOGIC ================= */
if (isset($_POST['upload'])) {

    if (!is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $error = "Please select a CSV file.";
    } else {

        $file = fopen($_FILES['csv']['tmp_name'], 'r');
        $rowCount = 0;
        $line = 0;

        while (($row = fgetcsv($file, 2000, ",")) !== FALSE) {

            // 🔹 Skip header
            if ($line === 0) {
                $line++;
                continue;
            }

            $prog         = trim($row[0] ?? null);
            $classid      = trim($row[1] ?? null);
            $year         = trim($row[2] ?? null);
            $htno         = trim($row[3] ?? null);
            $name         = trim($row[4] ?? null);
            $teamid       = trim($row[5] ?? null);
            $tfdue_12_9   = $row[6] ?? null;
            $tfdue_today  = $row[7] ?? null;
            $otdues_12_9  = $row[8] ?? null;
            $otdues_today = $row[9] ?? null;
            $busdue_12_9  = $row[10] ?? null;
            $busdue_today = $row[11] ?? null;
            $hosdue_12_9  = $row[12] ?? null;
            $hosdue_today = $row[13] ?? null;
            $olddue_12_9  = $row[14] ?? null;
            $olddue_today = $row[15] ?? null;

            $sql = "
            INSERT INTO STUDENTS (
                prog, classid, year, htno, name, teamid,
                tfdue_12_9, tfdue_today, otdues_12_9, otdues_today,
                busdue_12_9, busdue_today, hosdue_12_9, hosdue_today,
                olddue_12_9, olddue_today
            ) VALUES (
                ".($prog ? "'".$conn->real_escape_string($prog)."'" : "NULL").",
                ".($classid ? "'".$conn->real_escape_string($classid)."'" : "NULL").",
                ".(is_numeric($year) ? (int)$year : "NULL").",
                ".($htno ? "'".$conn->real_escape_string($htno)."'" : "NULL").",
                ".($name ? "'".$conn->real_escape_string($name)."'" : "NULL").",
                ".($teamid ? "'".$conn->real_escape_string($teamid)."'" : "NULL").",
                ".(is_numeric($tfdue_12_9) ? (float)$tfdue_12_9 : "NULL").",
                ".(is_numeric($tfdue_today) ? (float)$tfdue_today : "NULL").",
                ".(is_numeric($otdues_12_9) ? (float)$otdues_12_9 : "NULL").",
                ".(is_numeric($otdues_today) ? (float)$otdues_today : "NULL").",
                ".(is_numeric($busdue_12_9) ? (float)$busdue_12_9 : "NULL").",
                ".(is_numeric($busdue_today) ? (float)$busdue_today : "NULL").",
                ".(is_numeric($hosdue_12_9) ? (float)$hosdue_12_9 : "NULL").",
                ".(is_numeric($hosdue_today) ? (float)$hosdue_today : "NULL").",
                ".(is_numeric($olddue_12_9) ? (float)$olddue_12_9 : "NULL").",
                ".(is_numeric($olddue_today) ? (float)$olddue_today : "NULL")."
            )";

            if (!$conn->query($sql)) {
                fclose($file);
                die("❌ Upload failed at line $line : " . $conn->error);
            }

            $rowCount++;
            $line++;
        }

        fclose($file);
        $message = "✅ $rowCount students uploaded successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Students CSV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:#f4f6f9;
            padding:30px;
        }
        .box {
            background:#fff;
            padding:25px;
            max-width:520px;
            margin:auto;
            border-radius:8px;
            box-shadow:0 0 10px rgba(0,0,0,.1);
        }
        h2 {
            margin-bottom:15px;
        }
        input[type=file],
        input[type=submit] {
            margin-top:15px;
            display:block;
        }
        .success {
            margin-top:20px;
            color:green;
            font-weight:bold;
        }
        .error {
            margin-top:20px;
            color:red;
            font-weight:bold;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Upload Students (CSV)</h2>
    <p>CSV must contain header row.</p>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="csv" accept=".csv" required>
        <input type="submit" name="upload" value="Upload">
    </form>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
</div>

</body>
</html>
