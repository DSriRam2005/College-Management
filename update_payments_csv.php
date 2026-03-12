<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db.php';



$message = "";
$updated_count = 0;

if (isset($_POST['submit'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file_tmp, 'r');

        if ($handle !== FALSE) {
            $header = fgetcsv($handle); // Skip header row

            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // CSV columns: id, pay_date, created_at
                $id = mysqli_real_escape_string($conn, trim($data[0]));
                $pay_date = mysqli_real_escape_string($conn, trim($data[1]));
                $created_at = mysqli_real_escape_string($conn, trim($data[2]));

                if (!empty($id)) {
                    $update_sql = "UPDATE PAYMENTS 
                                   SET pay_date = '$pay_date', created_at = '$created_at'
                                   WHERE id = '$id'";
                    if ($conn->query($update_sql)) {
                        $updated_count++;
                    }
                }
            }

            fclose($handle);
            $message = "✅ Successfully updated <b>$updated_count</b> record(s).";
        } else {
            $message = "❌ Unable to open CSV file.";
        }
    } else {
        $message = "❌ Please select a valid CSV file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Payments using CSV</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h4>Update PAYMENTS (pay_date & created_at) via CSV Upload</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select CSV File:</label>
                    <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                </div>
                <button type="submit" name="submit" class="btn btn-success">Upload & Update</button>
                <a href="sample_payments_update.csv" class="btn btn-secondary ms-2">📥 Download Sample CSV</a>
            </form>

            <hr>
            <p class="text-muted">
                <b>CSV Format:</b> id, pay_date (YYYY-MM-DD), created_at (YYYY-MM-DD HH:MM:SS)<br>
                Example: <code>1,2025-10-10,2025-10-10 14:35:00</code>
            </p>
        </div>
    </div>
</div>
</body>
</html>
