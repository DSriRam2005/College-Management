<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$message = "";

// Search student by HTNO
$student = null;
if (isset($_POST['search'])) {
    $htno = trim($_POST['htno']);
    $query = "SELECT * FROM STUDENTS WHERE htno = '$htno'";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $message = "<p style='color:red;'>No student found with HTNO: $htno</p>";
    }
}

// Update dues
if (isset($_POST['update'])) {
    $htno = $_POST['htno'];

    $tfdue_12_9 = $_POST['tfdue_12_9'] ?? 0;
    $tfdue_today = $_POST['tfdue_today'] ?? 0;
    $otdues_12_9 = $_POST['otdues_12_9'] ?? 0;
    $otdues_today = $_POST['otdues_today'] ?? 0;
    $busdue_12_9 = $_POST['busdue_12_9'] ?? 0;
    $busdue_today = $_POST['busdue_today'] ?? 0;
    $hosdue_12_9 = $_POST['hosdue_12_9'] ?? 0;
    $hosdue_today = $_POST['hosdue_today'] ?? 0;
    $olddue_12_9 = $_POST['olddue_12_9'] ?? 0;
    $olddue_today = $_POST['olddue_today'] ?? 0;

    $update = "UPDATE STUDENTS SET 
        tfdue_12_9='$tfdue_12_9', tfdue_today='$tfdue_today',
        otdues_12_9='$otdues_12_9', otdues_today='$otdues_today',
        busdue_12_9='$busdue_12_9', busdue_today='$busdue_today',
        hosdue_12_9='$hosdue_12_9', hosdue_today='$hosdue_today',
        olddue_12_9='$olddue_12_9', olddue_today='$olddue_today'
        WHERE htno='$htno'";

    if ($conn->query($update)) {
        $message = "<p style='color:green;'>Dues updated successfully for HTNO: $htno</p>";
    } else {
        $message = "<p style='color:red;'>Error updating record: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Student Dues</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 30px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        h2 { text-align: center; color: #333; }
        input[type="text"], input[type="number"] {
            width: 100%; padding: 8px; margin: 5px 0 10px;
            border: 1px solid #ccc; border-radius: 6px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            background: #007bff; color: white;
            border: none; border-radius: 6px;
            cursor: pointer;
        }
        input[type="submit"]:hover { background: #0056b3; }
        .msg { text-align: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; text-align: left; }
    </style>
</head>
<body>
<div class="container">
    <h2>Update Student Dues</h2>
    <div class="msg"><?= $message ?></div>

    <!-- Search form -->
    <form method="post">
        <label>Enter HTNO:</label>
        <input type="text" name="htno" value="<?= htmlspecialchars($_POST['htno'] ?? '') ?>" required>
        <input type="submit" name="search" value="Search Student">
    </form>

    <?php if ($student): ?>
        <hr>
        <h3>Student: <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['htno']) ?>)</h3>
        <form method="post">
            <input type="hidden" name="htno" value="<?= htmlspecialchars($student['htno']) ?>">

            <table>
                <tr><th>Field</th><th>12/9</th><th>Today</th></tr>

                <tr>
                    <td>Tuition Fee</td>
                    <td><input type="number" step="0.01" name="tfdue_12_9" value="<?= $student['tfdue_12_9'] ?>"></td>
                    <td><input type="number" step="0.01" name="tfdue_today" value="<?= $student['tfdue_today'] ?>"></td>
                </tr>

                <tr>
                    <td>Other Dues</td>
                    <td><input type="number" step="0.01" name="otdues_12_9" value="<?= $student['otdues_12_9'] ?>"></td>
                    <td><input type="number" step="0.01" name="otdues_today" value="<?= $student['otdues_today'] ?>"></td>
                </tr>

                <tr>
                    <td>Bus Dues</td>
                    <td><input type="number" step="0.01" name="busdue_12_9" value="<?= $student['busdue_12_9'] ?>"></td>
                    <td><input type="number" step="0.01" name="busdue_today" value="<?= $student['busdue_today'] ?>"></td>
                </tr>

                <tr>
                    <td>Hostel Dues</td>
                    <td><input type="number" step="0.01" name="hosdue_12_9" value="<?= $student['hosdue_12_9'] ?>"></td>
                    <td><input type="number" step="0.01" name="hosdue_today" value="<?= $student['hosdue_today'] ?>"></td>
                </tr>

                <tr>
                    <td>Old Dues</td>
                    <td><input type="number" step="0.01" name="olddue_12_9" value="<?= $student['olddue_12_9'] ?>"></td>
                    <td><input type="number" step="0.01" name="olddue_today" value="<?= $student['olddue_today'] ?>"></td>
                </tr>
            </table>

            <div style="text-align:center; margin-top:15px;">
                <input type="submit" name="update" value="Update Dues">
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
