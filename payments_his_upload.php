<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'db.php';

if (isset($_POST['submit'])) {
    if (is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

        // Skip header row
        fgetcsv($file);

        $inserted = 0;
        $failed = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 14) {
                $failed++;
                continue;
            }

            $id         = (int) $row[0];
            $htno       = $conn->real_escape_string($row[1]);
            $name       = $conn->real_escape_string($row[2]);
            $teamid     = $conn->real_escape_string($row[3]);
            $paid_tf    = (float) $row[4];
            $paid_ot    = (float) $row[5];
            $paid_bus   = (float) $row[6];
            $paid_hos   = (float) $row[7];
            $paid_old   = (float) $row[8];
            $paid_mess  = (float) $row[9];
            $pay_date   = $conn->real_escape_string($row[10]);
            $receiptno  = $conn->real_escape_string($row[11]);
            $method     = $conn->real_escape_string($row[12]);
            $created_at = $conn->real_escape_string($row[13]);

            // Check for valid method
            if (!in_array($method, ['ONLINE', 'COUNTER'])) {
                $failed++;
                continue;
            }

            $sql = "INSERT INTO PAYMENTS 
                (id, htno, name, teamid, paid_tf, paid_ot, paid_bus, paid_hos, paid_old, paid_mess, pay_date, receiptno, method, created_at)
                VALUES 
                ('$id', '$htno', '$name', '$teamid', '$paid_tf', '$paid_ot', '$paid_bus', '$paid_hos', '$paid_old', '$paid_mess', '$pay_date', '$receiptno', '$method', '$created_at')";

            if ($conn->query($sql) === TRUE) {
                $inserted++;
            } else {
                $failed++;
            }
        }

        fclose($file);

        echo "<p>Upload completed: <strong>$inserted</strong> rows inserted, <strong>$failed</strong> failed.</p>";
    } else {
        echo "<p>Error: No file uploaded.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Payment History</title>
</head>
<body>
    <h2>Upload Payment History (CSV)</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <input type="submit" name="submit" value="Upload">
    </form>
    <p><strong>Note:</strong> CSV file must have the following columns in order:</p>
    <code>
        id,htno,name,teamid,paid_tf,paid_ot,paid_bus,paid_hos,paid_old,paid_mess,pay_date,receiptno,method,created_at
    </code>
</body>
</html>
