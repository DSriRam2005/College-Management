<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();
include 'db.php';

// ✅ Only allow ZONE role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ZONE') {
    header("Location: index.php");
    exit();
}

// ✅ Get Zone from session
$zone = $_SESSION['ZONE'] ?? "";

// ✅ Allow large result sets (InfinityFree restriction fix)
$conn->query("SET SQL_BIG_SELECTS = 1");

$query = $conn->prepare("
SELECT 
    s.htno,
    s.name,
    s.teamid,

    COUNT(DISTINCT ct.test_id) AS total_tests,
    COUNT(DISTINCT ctm.test_id) AS attended_tests,
    SUM(ct.total_marks) AS possible_total_marks,
    SUM(ctm.marks_obtained) AS secured_marks,

    -- ✅ Percentage Calculation
    CASE 
        WHEN SUM(ct.total_marks) > 0 
        THEN ROUND((SUM(ctm.marks_obtained) / SUM(ct.total_marks)) * 100, 2)
        ELSE 0
    END AS percentage

FROM STUDENTS s
LEFT JOIN class_test ct ON ct.classid = s.classid
LEFT JOIN class_test_marks ctm ON ctm.htno = s.htno AND ctm.test_id = ct.test_id

WHERE TRIM(UPPER(s.ZONE)) = TRIM(UPPER(?))

GROUP BY s.htno, s.name, s.teamid

-- ✅ ORDER BY TEAMID (numeric sorting after underscore)
ORDER BY CAST(SUBSTRING_INDEX(s.teamid, '_', -1) AS UNSIGNED), s.teamid, s.htno;
");

$query->bind_param("s", $zone);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Report - Zone Wise</title>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

<style>
body { font-family: Arial, sans-serif; }
h2 { text-align: center; margin-bottom: 20px; }
table.dataTable thead th { font-weight: bold; background: #007bff; color: white; }
</style>

</head>
<body>

<h2>TEST REPORT (ZONE: <?= $zone ?>)</h2>

<table id="reportTable" class="display" style="width:100%">
<thead>
<tr>
    <th>S.No</th>
    <th>HTNO</th>
    <th>Name</th>
    <th>TeamID</th>
    <th>Total Tests</th>
    <th>Attended Tests</th>
    <th>Possible Total Marks</th>
    <th>Secured Marks</th>
    <th>Total Marks %</th> <!-- ✅ NEW -->
</tr>
</thead>

<tbody>
<?php
$i = 1;
while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$i}</td>
        <td>{$row['htno']}</td>
        <td>{$row['name']}</td>
        <td>{$row['teamid']}</td>
        <td>{$row['total_tests']}</td>
        <td>{$row['attended_tests']}</td>
        <td>{$row['possible_total_marks']}</td>
        <td>{$row['secured_marks']}</td>
        <td>{$row['percentage']}%</td> <!-- ✅ NEW -->
    </tr>";
    $i++;
}
?>
</tbody>
</table>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $('#reportTable').DataTable({
        "pageLength": 50
    });
});
</script>

</body>
</html>
