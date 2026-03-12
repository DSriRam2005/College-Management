<?php
session_start();
include 'db.php'; // your database connection

$message = '';
$updatedCount = 0;

if (isset($_POST['submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $fileName = $_FILES['csv_file']['tmp_name'];

        if (($handle = fopen($fileName, "r")) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;

                // Skip header row
                if ($row == 1) continue;

                $teamid = mysqli_real_escape_string($conn, $data[0]);
                $teamleadno = mysqli_real_escape_string($conn, $data[1]);
                $eapcet_no = mysqli_real_escape_string($conn, $data[2]);
                $htno = mysqli_real_escape_string($conn, $data[3]);

                // Update STUDENTS table
                $sql = "UPDATE STUDENTS 
                        SET teamid='$teamid', teamleadno='$teamleadno', EAPCET_NO='$eapcet_no'
                        WHERE htno='$htno'";

                if (mysqli_query($conn, $sql)) {
                    if (mysqli_affected_rows($conn) > 0) {
                        $updatedCount++;
                    }
                }
            }
            fclose($handle);
            $message = "CSV processed successfully. Total records updated: $updatedCount";
        } else {
            $message = "Could not open the CSV file.";
        }
    } else {
        $message = "Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Students</title>
</head>
<body>
    <h2>Upload CSV to Update Students</h2>
    <?php if($message != '') echo "<p>$message</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <input type="submit" name="submit" value="Upload & Update">
    </form>
</body>
</html>
