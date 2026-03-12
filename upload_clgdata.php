<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php'; // ensure this connects to your MySQL database

$success = 0;
$skipped = 0;
$total = 0;

if (isset($_POST['upload'])) {
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $file = fopen($_FILES['file']['tmp_name'], 'r');

        // Skip the header row if present
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== FALSE) {
            $total++;
            $htno = trim($row[0]);
            $inter_clg = trim($row[1]);
            $inter_clg_loc = trim($row[2]);

            if (empty($htno)) {
                $skipped++;
                continue;
            }

            $stmt = $conn->prepare("UPDATE STUDENTS SET inter_clg = ?, inter_clg_loc = ? WHERE htno = ?");
            $stmt->bind_param("sss", $inter_clg, $inter_clg_loc, $htno);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success++;
            } else {
                $skipped++;
            }
        }

        fclose($file);

        echo "
        <div class='max-w-xl mx-auto mt-6 bg-green-50 p-4 rounded-lg shadow'>
            <h3 class='text-lg font-semibold text-green-700 mb-2'>✅ Update Summary</h3>
            <p><b>Total Records:</b> $total</p>
            <p><b>Successfully Updated:</b> $success</p>
            <p><b>Skipped / No Match Found:</b> $skipped</p>
        </div>";
    } else {
        echo "<div class='max-w-xl mx-auto mt-6 bg-red-50 p-4 rounded-lg shadow text-red-700'>
                ⚠️ Please upload a valid CSV file.
              </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Inter College Details</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Update Inter College Details</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block font-medium text-gray-600 mb-2">Upload CSV File</label>
                <input type="file" name="file" accept=".csv" required
                       class="w-full border border-gray-300 rounded p-2 focus:ring focus:ring-blue-200">
            </div>
            <button type="submit" name="upload"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                Upload & Update
            </button>
        </form>

        <p class="mt-6 text-sm text-gray-600">
            <b>CSV Format:</b><br>
            htno,inter_clg,inter_clg_loc<br>
            Example:<br>
            22A91A0501,Sri Chaitanya Jr College,Vizag<br>
            22A91A0502,Narayana Jr College,Kakinada
        </p>
    </div>
</body>
</html>
