<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// SQL Query for counts
$sql = "
SELECT 
    s.classid AS Class,
    SUM(CASE WHEN p.tcs_codevita_reg = 'Yes' THEN 1 ELSE 0 END) AS TCS_Yes,
    SUM(CASE WHEN p.tcs_codevita_reg = 'No' THEN 1 ELSE 0 END) AS TCS_No,
    SUM(CASE WHEN p.gate_applied = 'yes' THEN 1 ELSE 0 END) AS GATE_Yes,
    SUM(CASE WHEN p.gate_applied = 'no' THEN 1 ELSE 0 END) AS GATE_No,
    SUM(CASE WHEN p.emailverifys = 'correct' THEN 1 ELSE 0 END) AS Email_Correct,
    SUM(CASE WHEN p.emailverifys = 'wrong' THEN 1 ELSE 0 END) AS Email_Wrong
FROM STUDENTS s
LEFT JOIN placements p ON s.htno = p.htno
WHERE s.prog = 'B.TECH' 
  AND s.year = 22
GROUP BY s.classid
ORDER BY s.classid
";

$result = $conn->query($sql);

// Initialize totals
$totalTCS_Yes = $totalTCS_No = 0;
$totalGATE_Yes = $totalGATE_No = 0;
$totalEmail_Correct = $totalEmail_Wrong = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class-wise Placement Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Class-wise Placement Report (B.TECH, Year 22)</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Class</th>
                <th>TCS CodeVita - Yes</th>
                <th>TCS CodeVita - No</th>
                <th>GATE Applied - Yes</th>
                <th>GATE Applied - No</th>
                <th>Email Verified - Correct</th>
                <th>Email Verified - Wrong</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    $class = $row['Class'];

                    // Create clickable links pointing to 22_students.php
                    echo "<tr>
                        <td>{$class}</td>
                        <td><a href='22_students.php?class={$class}&field=tcs_codevita_reg&value=Yes'>{$row['TCS_Yes']}</a></td>
                        <td><a href='22_students.php?class={$class}&field=tcs_codevita_reg&value=No'>{$row['TCS_No']}</a></td>
                        <td><a href='22_students.php?class={$class}&field=gate_applied&value=yes'>{$row['GATE_Yes']}</a></td>
                        <td><a href='22_students.php?class={$class}&field=gate_applied&value=no'>{$row['GATE_No']}</a></td>
                        <td><a href='22_students.php?class={$class}&field=emailverifys&value=correct'>{$row['Email_Correct']}</a></td>
                        <td><a href='22_students.php?class={$class}&field=emailverifys&value=wrong'>{$row['Email_Wrong']}</a></td>
                    </tr>";

                    // Add to totals
                    $totalTCS_Yes += $row['TCS_Yes'];
                    $totalTCS_No += $row['TCS_No'];
                    $totalGATE_Yes += $row['GATE_Yes'];
                    $totalGATE_No += $row['GATE_No'];
                    $totalEmail_Correct += $row['Email_Correct'];
                    $totalEmail_Wrong += $row['Email_Wrong'];
                }

                // Total row
                echo "<tr class='table-secondary fw-bold'>
                    <td>Total</td>
                    <td><a href='22_students.php?field=tcs_codevita_reg&value=Yes'>{$totalTCS_Yes}</a></td>
                    <td><a href='22_students.php?field=tcs_codevita_reg&value=No'>{$totalTCS_No}</a></td>
                    <td><a href='22_students.php?field=gate_applied&value=yes'>{$totalGATE_Yes}</a></td>
                    <td><a href='22_students.php?field=gate_applied&value=no'>{$totalGATE_No}</a></td>
                    <td><a href='22_students.php?field=emailverifys&value=correct'>{$totalEmail_Correct}</a></td>
                    <td><a href='22_students.php?field=emailverifys&value=wrong'>{$totalEmail_Wrong}</a></td>
                </tr>";
            } else {
                echo "<tr><td colspan='7' class='text-center'>No data found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
$conn->close();
?>
