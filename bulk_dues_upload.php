<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db2.php';

$message = "";

// Handle CSV upload
if (isset($_POST['upload'])) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $filename = $_FILES['file']['tmp_name'];
        $handle = fopen($filename, "r");
        $count = 0;
        $updated = 0;
        $not_found = 0;

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $count++;

            // Expected CSV columns:
            // HTNO, tfdue_12_9, tfdue_today, otdues_12_9, otdues_today, busdue_12_9, busdue_today, hosdue_12_9, hosdue_today, olddue_12_9, olddue_today
            $htno = trim($data[0]);

            // Sanitize numeric fields
            $values = [];
            for ($i = 1; $i <= 10; $i++) {
                $values[$i] = is_numeric($data[$i]) ? $data[$i] : 0;
            }

            // Check if student exists
            $check = $conn->query("SELECT id FROM STUDENTS WHERE htno='$htno'");
            if ($check && $check->num_rows > 0) {
                // Update dues
                $sql = "UPDATE STUDENTS SET 
                    tfdue_12_9='{$values[1]}',
                    tfdue_today='{$values[2]}',
                    otdues_12_9='{$values[3]}',
                    otdues_today='{$values[4]}',
                    busdue_12_9='{$values[5]}',
                    busdue_today='{$values[6]}',
                    hosdue_12_9='{$values[7]}',
                    hosdue_today='{$values[8]}',
                    olddue_12_9='{$values[9]}',
                    olddue_today='{$values[10]}'
                    WHERE htno='$htno'";
                $conn->query($sql);
                $updated++;
            } else {
                $not_found++;
            }
        }

        fclose($handle);
        $message = "<p style='color:green;'>Processed: $count | Updated: $updated | Not Found: $not_found</p>";
    } else {
        $message = "<p style='color:red;'>Please upload a valid CSV file.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bulk Upload Dues</title>
<style>
    body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 40px; }
    .container {
        max-width: 700px; margin: auto; background: #fff;
        padding: 25px; border-radius: 12px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    h2 { text-align: center; color: #333; }
    input[type=file] { width: 100%; padding: 10px; margin-top: 10px; }
    input[type=submit] {
        background: #007bff; color: white;
        border: none; padding: 10px 20px;
        border-radius: 6px; cursor: pointer;
        margin-top: 15px;
    }
    input[type=submit]:hover { background: #0056b3; }
    .msg { text-align: center; margin-bottom: 15px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #f2f2f2; }
</style>
</head>
<body>
<div class="container">
    <h2>Bulk Upload Student Dues (CSV)</h2>
    <div class="msg"><?= $message ?></div>

    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="file" accept=".csv" required>
        <input type="submit" name="upload" value="Upload & Update">
    </form>

    <h3>📘 CSV Format (Example)</h3>
    <table>
        <tr>
            <th>HTNO</th>
            <th>tfdue_12_9</th>
            <th>tfdue_today</th>
            <th>otdues_12_9</th>
            <th>otdues_today</th>
            <th>busdue_12_9</th>
            <th>busdue_today</th>
            <th>hosdue_12_9</th>
            <th>hosdue_today</th>
            <th>olddue_12_9</th>
            <th>olddue_today</th>
        </tr>
        <tr>
            <td>550962010379</td>
            <td>1000</td><td>500</td>
            <td>0</td><td>0</td>
            <td>500</td><td>0</td>
            <td>0</td><td>0</td>
            <td>100</td><td>50</td>
        </tr>
    </table>

    <p style="margin-top:10px; color:#555; font-size:14px;">
        💡 Save the file as <b>CSV (Comma delimited)</b> before uploading.<br>
        Only existing students (matched by HTNO) will be updated.
    </p>
</div>
</body>
</html>
