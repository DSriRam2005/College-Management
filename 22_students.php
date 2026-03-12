<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$class = $_GET['class'] ?? '';
$field = $_GET['field'] ?? '';
$value = $_GET['value'] ?? '';

$sql = "SELECT s.htno, s.name, s.email, s.phone 
        FROM STUDENTS s
        LEFT JOIN placements p ON s.htno = p.htno
        WHERE s.prog='B.TECH' AND s.year=22";

if ($class) {
    $sql .= " AND s.classid = '{$class}'";
}
if ($field && $value) {
    $sql .= " AND p.{$field} = '{$value}'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Students List <?= $class ? " - Class $class" : "" ?> (<?= ucfirst($field) ?> = <?= $value ?>)</h2>
    <a href="finalyearreport.php" class="btn btn-primary mb-3">Back to Report</a>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>HT No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()){
                echo "<tr>
                        <td>{$row['htno']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['phone']}</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No students found</td></tr>";
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
