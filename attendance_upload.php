<?php
require_once 'db.php'; // Use shared DB connection

if (isset($_POST["submit"])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csv_file']['tmp_name'];
        $file = fopen($fileTmpPath, "r");

        // Skip header row
        fgetcsv($file);

        $inserted = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            $htno = $conn->real_escape_string(trim($row[0]));
            $classid = $conn->real_escape_string(trim($row[1]));
            $att_date = $conn->real_escape_string(trim($row[2]));
            $status = $conn->real_escape_string(trim($row[3]));
            $remark = $conn->real_escape_string(trim($row[4]));

            // Basic validation (optional)
            if (empty($htno) || empty($classid) || empty($att_date) || empty($status)) {
                $skipped++;
                continue;
            }

            // Insert or update (handles duplicate htno + att_date due to UNIQUE constraint)
            $sql = "INSERT INTO attendance (htno, classid, att_date, status, remark)
                    VALUES ('$htno', '$classid', '$att_date', '$status', '$remark')
                    ON DUPLICATE KEY UPDATE
                        classid = VALUES(classid),
                        status = VALUES(status),
                        remark = VALUES(remark),
                        updated_at = CURRENT_TIMESTAMP";

            if ($conn->query($sql)) {
                $inserted++;
            } else {
                $skipped++;
            }
        }
        fclose($file);
        echo "<p><strong>$inserted</strong> records inserted/updated.<br><strong>$skipped</strong> records skipped.</p>";
    } else {
        echo "<p>Error uploading file.</p>";
    }
}
?>

<!-- HTML Upload Form -->
<!DOCTYPE html>
<html>
<head>
    <title>Upload Attendance CSV</title>
</head>
<body>
    <h2>Upload Attendance CSV</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required><br><br>
        <input type="submit" name="submit" value="Upload CSV">
    </form>
</body>
</html>
