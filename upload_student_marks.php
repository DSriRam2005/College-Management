<?php
session_start();
include 'db.php'; // Your database connection

// Handle CSV download
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_marks_sample.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['htno','test_id','section_id','marks_obtained']);
    fputcsv($output, ['25B25A001',1,1,85]);
    fputcsv($output, ['25B25A002',1,1,90]);
    fputcsv($output, ['25B25A003',1,1,78]);
    fputcsv($output, ['25B25A001',1,2,88]);
    fputcsv($output, ['25B25A002',1,2,92]);
    fputcsv($output, ['25B25A003',1,2,80]);
    fputcsv($output, ['25B25A001',2,1,75]);
    fputcsv($output, ['25B25A002',2,1,82]);
    fputcsv($output, ['25B25A003',2,1,70]);
    fclose($output);
    exit;
}

// Handle CSV upload
$uploadMessage = '';
$uploadClass = '';
if (isset($_POST['submit'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (!file_exists($file)) {
        $uploadMessage = "Please select a CSV file.";
        $uploadClass = "text-danger";
    } else {
        $handle = fopen($file, "r");
        if ($handle === false) {
            $uploadMessage = "Could not open the file.";
            $uploadClass = "text-danger";
        } else {
            $rowCount = 0;
            $errors = [];
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowCount++;
                if ($rowCount == 1 && strtolower($data[0]) == 'htno') continue;
                if (count($data) < 4) {
                    $errors[] = "Row $rowCount has missing columns.";
                    continue;
                }

                $htno = trim($data[0]);
                $test_id = (int)$data[1];
                $section_id = (int)$data[2];
                $marks_obtained = (float)$data[3];

                $testCheck = $conn->prepare("SELECT test_id FROM tests WHERE test_id=?");
                $testCheck->bind_param("i", $test_id);
                $testCheck->execute();
                $testCheck->store_result();
                if ($testCheck->num_rows == 0) {
                    $errors[] = "Row $rowCount: test_id $test_id does not exist.";
                    continue;
                }

                $sectionCheck = $conn->prepare("SELECT section_id FROM test_sections WHERE section_id=?");
                $sectionCheck->bind_param("i", $section_id);
                $sectionCheck->execute();
                $sectionCheck->store_result();
                if ($sectionCheck->num_rows == 0) {
                    $errors[] = "Row $rowCount: section_id $section_id does not exist.";
                    continue;
                }

                $stmt = $conn->prepare("INSERT INTO student_marks (htno, test_id, section_id, marks_obtained) VALUES (?, ?, ?, ?)
                                        ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained)");
                $stmt->bind_param("siid", $htno, $test_id, $section_id, $marks_obtained);
                if (!$stmt->execute()) {
                    $errors[] = "Row $rowCount failed: " . $stmt->error;
                }
            }
            fclose($handle);

            if (count($errors) > 0) {
                $uploadMessage = "Upload completed with errors:<br>" . implode("<br>", $errors);
                $uploadClass = "text-warning";
            } else {
                $uploadMessage = "CSV data uploaded successfully!";
                $uploadClass = "text-success";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Student Marks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f9fc;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        h2 {
            margin-bottom: 25px;
            text-align: center;
            color: #333333;
        }
        .btn-upload {
            width: 100%;
        }
        .info-text {
            font-size: 0.9rem;
            color: #555555;
            margin-top: 10px;
        }
        a.download-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Upload Student Marks CSV</h2>

    <?php if ($uploadMessage != ''): ?>
        <div class="<?php echo $uploadClass; ?> mb-3"><?php echo $uploadMessage; ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input class="form-control mb-3" type="file" name="csv_file" accept=".csv" required>
        <button class="btn btn-primary btn-upload" type="submit" name="submit">Upload</button>
    </form>

    <p class="info-text">CSV format: <b>htno,test_id,section_id,marks_obtained</b></p>
    <a class="download-link btn btn-outline-secondary btn-sm" href="?download_sample=1">Download Sample CSV</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
