<?php
include 'db.php';

$message = "";

if (isset($_POST['upload'])) {

    if (!empty($_FILES['csv_file']['name'])) {

        $file = $_FILES['csv_file']['tmp_name'];

        if (($handle = fopen($file, "r")) !== FALSE) {

            $row = 0;
            $updated = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                // Skip header
                if ($row == 0) {
                    $row++;
                    continue;
                }

                $htno     = mysqli_real_escape_string($conn, trim($data[0]));
                $refempid = mysqli_real_escape_string($conn, trim($data[1]));
                $ref      = mysqli_real_escape_string($conn, trim($data[2]));

                if ($htno != "") {
                    $sql = "
                        UPDATE STUDENTS 
                        SET REFEMPID = '$refempid', ref = '$ref'
                        WHERE htno = '$htno'
                    ";

                    if (mysqli_query($conn, $sql)) {
                        if (mysqli_affected_rows($conn) > 0) {
                            $updated++;
                        }
                    }
                }

                $row++;
            }

            fclose($handle);
            $message = "Update completed. Records updated: $updated";

        } else {
            $message = "File open failed.";
        }

    } else {
        $message = "Please upload a CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update REFEMPID & REF using CSV</title>
</head>
<body>

<h2>Update REFEMPID and REF using HTNO (CSV Upload)</h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <br><br>
    <button type="submit" name="upload">Upload & Update</button>
</form>

<p style="color:green;"><?php echo $message; ?></p>

<p>CSV Format:</p>
<pre>
htno,REFEMPID,ref
25B21A42A3,101,ABC Reference
25JN1A4313,100,XYZ Reference
</pre>

</body>
</html>
