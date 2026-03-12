<?php
session_start();
include 'db.php';

// ✅ Only allow Admin/CPTO (adjust role check as needed)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN','CPTO'])) {
    header("Location: index.php");
    exit();
}

// ✅ Handle file upload
if (isset($_POST['upload'])) {
    if (isset($_FILES['csvfile']) && $_FILES['csvfile']['error'] == 0) {
        $file = fopen($_FILES['csvfile']['tmp_name'], "r");

        // Skip header row if present
        fgetcsv($file);

        $updated = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            // CSV Format: htno,classid,teamid
            $htno    = trim($row[0]);
            $classid = trim($row[1]);
            $teamid  = trim($row[2]);

            if (!empty($htno)) {
                $stmt = $conn->prepare("UPDATE STUDENTS SET classid=?, teamid=? WHERE htno=?");
                $stmt->bind_param("sss", $classid, $teamid, $htno);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $updated++;
                }
                $stmt->close();
            }
        }
        fclose($file);

        $message = "✅ $updated records updated successfully.";
    } else {
        $message = "❌ Error uploading CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update CLASSID & TEAMID</title>
    <style>
        body { font-family: Arial, sans-serif; margin:40px; }
        .container { max-width:600px; margin:auto; padding:20px; border:1px solid #ccc; border-radius:10px; }
        input[type=file], input[type=submit] { margin:10px 0; padding:10px; }
        .msg { margin-top:20px; font-weight:bold; color:green; }
    </style>
</head>
<body>
<div class="container">
    <h2>Upload CSV to Update CLASSID & TEAMID</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label><br>
        <input type="file" name="csvfile" accept=".csv" required><br>
        <input type="submit" name="upload" value="Upload & Update">
    </form>
    <?php if (!empty($message)) echo "<div class='msg'>$message</div>"; ?>
    <p><b>CSV Format:</b> htno,classid,teamid</p>
</div>
</body>
</html>
