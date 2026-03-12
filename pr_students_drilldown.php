<?php
    error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'PR') {
    header("Location: index.php");
    exit();
}

$refempid = mysqli_real_escape_string($conn,$_GET['refempid']);
$phase    = mysqli_real_escape_string($conn,$_GET['phase']);
$clg      = mysqli_real_escape_string($conn,$_GET['clg']);

$sql = "
SELECT 
    s.htno,
    s.name,
    s.gen,
    s.classid,
    (
      IFNULL(s.tfdue_today,0)+
      IFNULL(s.otdues_today,0)+
      IFNULL(s.busdue_today,0)+
      IFNULL(s.hosdue_today,0)+
      IFNULL(s.olddue_today,0)
    ) AS totaltodaydue,

    IFNULL(
        ROUND((SUM(a.status='Present') / COUNT(a.id)) * 100,2),0
    ) AS attendancepercent,

    s.REFEMPID,
    s.ref
FROM STUDENTS s
LEFT JOIN attendance a ON s.htno=a.htno
WHERE s.REFEMPID='$refempid'
";

if($phase != 'ALL'){
    $sql .= " AND s.PHASE='$phase'";
}
if($clg != 'ALL'){
    $sql .= " AND s.CLG_TYPE='$clg'";
}

$sql .= " GROUP BY s.htno ORDER BY s.name";

$res = mysqli_query($conn,$sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>PR Student Drilldown</title>
<style>
body{font-family:Arial;background:#f0f0f0;}
table{border-collapse:collapse;width:98%;margin:auto;background:#fff;}
th,td{border:1px solid #000;padding:6px;text-align:center;font-size:13px;}
th{background:#222;color:#fff;}
</style>
</head>
<body>

<h3 align="center">
Students List | REFEMPID : <?=$refempid?> | PHASE : <?=$phase?> | TYPE : <?=$clg?>
</h3>

<table>
<tr>
<th>HTNO</th>
<th>NAME</th>
<th>GEN</th>
<th>CLASSID</th>
<th>TOTAL TODAY DUE</th>
<th>ATTENDANCE %</th>
<th>REFEMPID</th>
<th>REF</th>
</tr>

<?php
while($r=mysqli_fetch_assoc($res)){
echo "<tr>
<td>{$r['htno']}</td>
<td>{$r['name']}</td>
<td>{$r['gen']}</td>
<td>{$r['classid']}</td>
<td>₹".number_format($r['totaltodaydue'],2)."</td>
<td>{$r['attendancepercent']}%</td>
<td>{$r['REFEMPID']}</td>
<td>{$r['ref']}</td>
</tr>";
}
?>
</table>
</body>
</html>
