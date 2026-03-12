<?php
session_start();
if (!isset($_SESSION['role'])) { die("ACCESS DENIED"); }

include "db.php";

$target_date = "2025-12-01";
$classid = $_GET['classid'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance - <?php echo $target_date; ?></title>
    <style>
        table { border-collapse: collapse; width: 80%; margin-top:20px; }
        th,td { border:1px solid #555; padding:8px; text-align:center; }
        th { background:#ddd; }
        input[type='text'] { padding:6px; width:250px; }
        button { padding:6px 18px; }
    </style>
</head>
<body>

<h2>Mark Attendance for <?php echo $target_date; ?></h2>

<form method="GET">
    <input type="text" name="classid" placeholder="Enter Class ID" value="<?php echo htmlspecialchars($classid); ?>" required>
    <button type="submit">Load Students</button>
</form>

<?php
if ($classid != "") {

    // Fetch only UNMARKED students
    $sql = "
        SELECT s.htno, s.name, s.classid
        FROM STUDENTS s
        WHERE s.classid = '$classid'
        AND s.htno NOT IN (SELECT htno FROM attendance WHERE att_date = '$target_date')
        ORDER BY s.htno
    ";

    $res = $conn->query($sql);

    if ($res->num_rows == 0) {
        echo "<p style='color:green; font-weight:bold;'>All attendance already marked for this class.</p>";
        exit;
    }

    echo "<form method='POST' action='save_attendance_01_12_2025.php'>";
    echo "<input type='hidden' name='classid' value='$classid'>";

    echo "<table>
            <tr>
                <th>HTNO</th>
                <th>Name</th>
                <th>Present</th>
            </tr>";

    while ($row = $res->fetch_assoc()) {
        echo "<tr>
                <td>{$row['htno']}</td>
                <td>{$row['name']}</td>
                <td>
                    <input type='checkbox' name='present[]' value='{$row['htno']}'>
                </td>
              </tr>";
    }

    echo "</table><br>";
    echo "<button type='submit'>Submit Attendance</button>";
    echo "</form>";
}
?>

</body>
</html>
