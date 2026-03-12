<?php
session_start();
require_once "db.php";

// ONLY ADMIN OR CALENDAR CAN ACCESS
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'CALENDAR'])) {
    die("ACCESS DENIED");
}

$msg = "";

// FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $sem = trim($_POST['sem']);
    $branch = trim($_POST['branch']);

    if ($subject_code == "" || $subject_name == "" || $sem == "" || $branch == "") {
        $msg = "<p style='color:red;'>All fields required</p>";
    } else {

        $stmt = $conn->prepare("INSERT INTO SEM_SUBJECTS (subject_code, subject_name, sem, branch) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $subject_code, $subject_name, $sem, $branch);

        if ($stmt->execute()) {
            $msg = "<p style='color:green;'>Subject Added Successfully</p>";
        } else {
            $msg = "<p style='color:red;'>Error: " . $stmt->error . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Subjects</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { width: 400px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; }
        button { padding: 10px; width: 100%; background: black; color: white; border: none; }
        table { border-collapse: collapse; margin-top: 20px; width: 100%; }
        table th, table td { border: 1px solid #999; padding: 8px; }
    </style>
</head>
<body>

<h2>Add Subject (Admin / Calendar Only)</h2>
<?= $msg ?>
<div class="box">
    <form method="post">
        <label>Subject Code</label>
        <input type="text" name="subject_code" >

        <label>Subject Name</label>
        <input type="text" name="subject_name" required>

        <label>Semester</label>
        <select name="sem" required>
            <option value="">Select Semester</option>
            <option value="1-1">1-1</option>
            <option value="1-2">1-2</option>
            <option value="2-1">2-1</option>
            <option value="2-2">2-2</option>
            <option value="3-1">3-1</option>
            <option value="3-2">3-2</option>
            <option value="4-1">4-1</option>
            <option value="4-2">4-2</option>
        </select>

        <label>Branch</label>
        <input type="text" name="branch" required>

        <button type="submit">Add Subject</button>
    </form>
</div>

<hr>
<h3>Available Subjects</h3>

<table>
    <tr>
        <th>ID</th>
        <th>Subject Code</th>
        <th>Subject Name</th>
        <th>Year</th>
        <th>Branch</th>
    </tr>

    <?php
    $q = $conn->query("SELECT * FROM SEM_SUBJECTS ORDER BY sem, branch, subject_code");

    if ($q->num_rows > 0) {
        while ($row = $q->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['subject_code']}</td>
                    <td>{$row['subject_name']}</td>
                    <td>{$row['sem']}</td>
                    <td>{$row['branch']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No Subjects Found</td></tr>";
    }
    ?>
</table>

</body>
</html>
