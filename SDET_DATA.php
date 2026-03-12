<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$message = "";
$student = null;

// ---------------------------
// Step 1: Search by HTNO
// ---------------------------
if (isset($_POST['search'])) {
    $htno = trim($_POST['htno']);
    if ($htno != "") {
        $sql = "SELECT s.htno, s.name, p.SDET 
                FROM STUDENTS s 
                LEFT JOIN placements p ON s.htno = p.htno 
                WHERE s.htno='$htno'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
        } else {
            $message = "❌ No student found with that HTNO.";
        }
    } else {
        $message = "⚠️ Please enter a valid HTNO.";
    }
}

// ---------------------------
// Step 2: Submit SDET entry
// ---------------------------
if (isset($_POST['submit_sdet'])) {
    $htno = $_POST['htno_hidden'];
    $sdet_choice = $_POST['sdet_choice'];

    // Check if already entered
    $check = $conn->query("SELECT SDET FROM placements WHERE htno='$htno' AND SDET IS NOT NULL");
    if ($check && $check->num_rows > 0) {
        $message = "⚠️ You have already submitted your SDET response.";
    } else {
        $sdet_value = ($sdet_choice == 'Yes') ? 1 : 0;

        // If no record, insert new; otherwise update existing
        $exists = $conn->query("SELECT htno FROM placements WHERE htno='$htno'");
        if ($exists && $exists->num_rows > 0) {
            $conn->query("UPDATE placements SET SDET=$sdet_value WHERE htno='$htno'");
        } else {
            $conn->query("INSERT INTO placements (htno, SDET) VALUES ('$htno', $sdet_value)");
        }
        $message = "✅ Your SDET drive response has been recorded successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SDET Drive Entry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2f3;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 380px;
            text-align: center;
        }
        input[type="text"] {
            width: 80%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            padding: 8px 16px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .msg {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }
        .student-details {
            margin-top: 15px;
            text-align: left;
        }
        .radio-group {
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>SDET Drive Entry</h2>

    <form method="post">
        <input type="text" name="htno" placeholder="Enter HTNO" value="<?= isset($_POST['htno']) ? htmlspecialchars($_POST['htno']) : '' ?>">
        <br>
        <button type="submit" name="search">Search</button>
    </form>

    <?php if ($student): ?>
        <div class="student-details">
            <p><b>HTNO:</b> <?= htmlspecialchars($student['htno']) ?></p>
            <p><b>Name:</b> <?= htmlspecialchars($student['name']) ?></p>
        </div>

        <?php if ($student['SDET'] !== null): ?>
            <p style="color: green; font-weight: bold;">You already submitted: <?= ($student['SDET'] == 1) ? 'Yes' : 'No' ?></p>
        <?php else: ?>
            <form method="post">
    <input type="hidden" name="htno_hidden" value="<?= htmlspecialchars($student['htno']) ?>">

    <p><b>Question:</b> Have you applied for the SDET drive?</p>

    <div class="radio-group">
        <label><input type="radio" name="sdet_choice" value="Yes" required> Yes</label>
        <label><input type="radio" name="sdet_choice" value="No"> No</label>
    </div>
    <button type="submit" name="submit_sdet">Submit</button>
</form>

        <?php endif; ?>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="msg"><?= $message ?></div>
    <?php endif; ?>
</div>
</body>
</html>
