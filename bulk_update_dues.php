<?php
session_start();
include 'db.php'; // database connection

$message = '';
$skipped_htnos = [];

if(isset($_POST['upload_csv'])){
    if($_FILES['csv_file']['name'] != ''){
        $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if($file_ext != 'csv'){
            $message = "Please upload a valid CSV file.";
        } else {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $row = 0;
            $updated_count = 0;
            $skipped_count = 0;

            while(($data = fgetcsv($file, 1000, ",")) !== FALSE){
                // Skip header
                if($row == 0){ $row++; continue; }

                $htno = trim($data[0]);
                $tfdue = trim($data[1]);
                $busdue = trim($data[2]);
                $hosdue = trim($data[3]);

                if(!empty($htno)){
                    // Update STUDENTS table
                    $stmt = $conn->prepare("UPDATE STUDENTS SET tfdue_12_9 = ?, busdue_12_9 = ?, hosdue_12_9 = ? WHERE htno = ?");
                    $stmt->bind_param("ddds", $tfdue, $busdue, $hosdue, $htno);
                    $stmt->execute();

                    if($stmt->affected_rows > 0){
                        $updated_count++;
                    } else {
                        $skipped_count++;
                        $skipped_htnos[] = $htno;
                    }
                } else {
                    $skipped_count++;
                    $skipped_htnos[] = $htno;
                }

                $row++;
            }

            fclose($file);
            $total_rows = $row - 1;
            $message = "CSV processed: $total_rows rows. Successfully updated: $updated_count. Skipped: $skipped_count.";

            if(!empty($skipped_htnos)){
                $message .= "<br>Skipped HTNOs: ".implode(", ", $skipped_htnos);
            }
        }
    } else {
        $message = "Please select a CSV file to upload.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk Update Dues</title>
</head>
<body>
    <h2>Bulk Update Dues via CSV</h2>
    <?php if($message != ''){ echo "<p>$message</p>"; } ?>
    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <input type="submit" name="upload_csv" value="Upload & Update">
    </form>
</body>
</html>
