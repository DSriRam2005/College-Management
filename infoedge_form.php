<?php
include 'db.php';
$message = "";
$student_name = "";
$htno = "";

// Step 1: When student submits HTNO
if (isset($_POST['check_htno'])) {
    $htno = trim($_POST['htno']);

    // Check in placements
    $sql = "SELECT * FROM placements WHERE htno = '$htno'";
    $res = $conn->query($sql);

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();

        if ($row['SDET'] == 1) {
            // Fetch student name from STUDENTS table
            $st_sql = "SELECT name FROM STUDENTS WHERE htno = '$htno'";
            $st_res = $conn->query($st_sql);
            if ($st_res->num_rows > 0) {
                $st_row = $st_res->fetch_assoc();
                $student_name = $st_row['name'];
            } else {
                $student_name = "Name not found";
            }

            // Check if already submitted
            if (!empty($row['infoedge_selected'])) {
                $message = "<p style='color:red;'>You have already submitted your InfoEdge selection status.</p>";
            } else {
                $allow_form = true;
            }
        } else {
            $message = "<p style='color:red;'>Access denied. You are not marked as infoedge.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Invalid HTNO. Please check and try again.</p>";
    }
}

// Step 2: When student submits selection form
if (isset($_POST['submit_selection'])) {
    $htno = $_POST['htno'];
    $infoedge_selected = $_POST['infoedge_selected'];

    $sql = "UPDATE placements SET infoedge_selected = '$infoedge_selected' WHERE htno = '$htno'";
    if ($conn->query($sql)) {
        $message = "<p style='color:green;'>✅ Your InfoEdge selection status has been submitted successfully.</p>";
    } else {
        $message = "<p style='color:red;'>Database error: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>InfoEdge Selection Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fa;
            padding: 30px;
        }
        form {
            background: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
            margin: auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        input[type=text], select {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        input[type=submit] {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        input[type=submit]:hover {
            background: #0056b3;
        }
        .details {
            margin-bottom: 15px;
            padding: 10px;
            background: #eef6ff;
            border-radius: 8px;
        }
        p { text-align: center; }
    </style>
</head>
<body>

<h2>InfoEdge Selection Confirmation</h2>

<?= $message ?>

<?php if (!isset($allow_form)) { ?>
<form method="POST">
    <label><b>Enter Your Hall Ticket Number (HTNO):</b></label>
    <input type="text" name="htno" required>
    <input type="submit" name="check_htno" value="Check Eligibility">
</form>
<?php } else { ?>
<form method="POST">
    <div class="details">
        <p><b>Name:</b> <?= htmlspecialchars($student_name) ?></p>
        <p><b>HTNO:</b> <?= htmlspecialchars($htno) ?></p>
    </div>

    <input type="hidden" name="htno" value="<?= htmlspecialchars($htno) ?>">
    <label><b>Are you selected for InfoEdge Round 1 ?</b></label>
    <select name="infoedge_selected" required>
        <option value="">--Select--</option>
        <option value="Yes">Yes</option>
        <option value="No">No</option>
    </select>
    <input type="submit" name="submit_selection" value="Submit">
</form>
<?php } ?>

</body>
</html>
