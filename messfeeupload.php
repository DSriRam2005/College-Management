<?php
session_start();
include 'db.php';


$msg = "";

// ✅ When form is submitted
if (isset($_POST['upload'])) {
    $month = $_POST['month']; // format: YYYY-MM
    $month_year = date("Y-m-01", strtotime($month . "-01")); // First date of that month

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if ($handle) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Skip header row
                if ($row == 0) {
                    $row++;
                    continue;
                }

                $htno = mysqli_real_escape_string($conn, $data[0]);
                $ttamt = floatval($data[1]);
                $due = floatval($data[2]);

                $query = "INSERT INTO messfee (htno, ttamt, due, month_year)
                          VALUES ('$htno', '$ttamt', '$due', '$month_year')";
                $conn->query($query);
                $row++;
            }
            fclose($handle);
            $msg = "<div class='alert alert-success'>Mess Fee uploaded successfully for $month.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Failed to open the file.</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning'>Please upload a valid CSV file.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Mess Fee (Month-wise)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .container {
            margin-top: 80px;
            max-width: 600px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h3 class="text-center mb-3">Upload Mess Fee (Month-wise)</h3>
        <?= $msg ?>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Select Month and Year:</label>
                <input type="month" name="month" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Upload CSV File:</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <small class="text-muted">CSV format: htno, ttamt, due</small>
            </div>
            <button type="submit" name="upload" class="btn btn-success w-100">Upload Mess Fee</button>
        </form>
    </div>
</div>
</body>
</html>
