<?php
// upload_csv.php
session_start();
include 'db.php'; // ✅ DB connection

$msg = "";

if (isset($_POST['upload'])) {
    if (is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
        $file = fopen($_FILES['csvfile']['tmp_name'], "r");
        
        // Skip header row
        fgetcsv($file);

        $count = 0;
        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            $busname = trim($row[0]);
            $password = trim($row[1]);

            if (!empty($busname) && !empty($password)) {
                // ✅ Hash password
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                // Insert into DB
                $stmt = $conn->prepare("INSERT INTO DBUSES (BUSNAME, PASSWORD) VALUES (?, ?)");
                $stmt->bind_param("ss", $busname, $hashed);

                if ($stmt->execute()) {
                    $count++;
                }
                $stmt->close();
            }
        }
        fclose($file);
        $msg = "✅ Successfully uploaded $count records.";
    } else {
        $msg = "⚠️ Please choose a valid CSV file.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSV Upload - DBUSES</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        form { padding: 20px; border: 1px solid #ccc; border-radius: 10px; width: 400px; margin: auto; }
        input[type=file], button { margin-top: 10px; }
        button { background: #007bff; color: white; border: none; padding: 10px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .msg { margin: 20px; font-weight: bold; color: green; }
    </style>
</head>
<body>
    <h2>Upload Bus Passwords via CSV</h2>
    <?php if (!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Select CSV File:</label><br>
        <input type="file" name="csvfile" accept=".csv" required><br>
        <button type="submit" name="upload">Upload</button>
    </form>
</body>
</html>
