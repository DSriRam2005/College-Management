<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$servername = "sql211.infinityfree.com";
$username   = "if0_39689452";
$password   = "0JaTuFZVF3U0L";
$dbname     = "if0_39689452_12_9";

// ✅ Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// ✅ Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Set character set
$conn->set_charset("utf8mb4");

$message = "";
$updated = 0;
$skipped = 0;

if (isset($_POST['upload'])) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");

        if ($handle !== false) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $row++;
                if ($row == 1) continue; // Skip header row

                $htno  = trim($data[0] ?? '');
                $email = strtolower(trim($data[1] ?? ''));

                // ✅ Validate both fields
                if ($htno !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stmt = $conn->prepare("UPDATE STUDENTS SET email=? WHERE htno=?");
                    if ($stmt) {
                        $stmt->bind_param('ss', $email, $htno);
                        $stmt->execute();

                        if ($stmt->affected_rows > 0) {
                            $updated++;
                        } else {
                            $skipped++; // HTNO not found or no change
                        }
                        $stmt->close();
                    } else {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            }
            fclose($handle);
            $message = "✅ Upload completed. Updated: {$updated}, Skipped: {$skipped}.";
        } else {
            $message = "❌ Error opening the CSV file.";
        }
    } else {
        $message = "⚠️ Please select a CSV file to upload.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Upload Email CSV</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Bulk Update Student Emails from CSV</h3>

  <?php if (!empty($message)): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="card p-3 shadow-sm">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="csv_file" class="form-label">Select CSV File</label>
        <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
      </div>
      <button type="submit" name="upload" class="btn btn-primary">Upload & Update</button>
    </form>
  </div>

  <div class="mt-4">
    <h6>📘 CSV Format Example:</h6>
    <pre class="bg-white p-2 border rounded">htno,email
22A91A0501,student1@gmail.com
22A91A0502,student2@yahoo.com</pre>
  </div>
</div>
</body>
</html>
