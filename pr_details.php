<?php
session_start();
include 'db.php';

/* Only PR */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'PR') {
    header("Location: index.php");
    exit();
}

/* GET FILTERS */
$ref   = $_GET['ref']   ?? null;
$phase = $_GET['phase'] ?? null;
$type  = $_GET['type']  ?? null;   // H or D
$year  = $_GET['year']  ?? 25;
$prog  = $_GET['prog']  ?? 'B.TECH';

/* BUILD WHERE */
$where = [];
$where[] = "s.year = '".mysqli_real_escape_string($conn,$year)."'";
$where[] = "s.prog = '".mysqli_real_escape_string($conn,$prog)."'";

if($ref){
    $where[] = "s.REFEMPID = '".mysqli_real_escape_string($conn,$ref)."'";
}
if($phase){
    $where[] = "s.PHASE = '".mysqli_real_escape_string($conn,$phase)."'";
}
if($type){
    $where[] = "s.CLG_TYPE = '".mysqli_real_escape_string($conn,$type)."'";
}

$whereSQL = implode(" AND ", $where);

/* QUERY */
$sql = "
SELECT 
    s.htno,
    s.name,
    s.PHASE,
    s.CLG_TYPE,
    s.branch,
    s.classid,
    s.phone,
    s.EAPCET_NO,
    k.NAME AS staff_name
FROM STUDENTS s
LEFT JOIN kiet_staff k ON s.REFEMPID = k.EMPID
WHERE $whereSQL
ORDER BY s.name
";

$result = mysqli_query($conn,$sql);
if(!$result){ die(mysqli_error($conn)); }
?>

<!DOCTYPE html>
<html>
<head>
<title>PR Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:Segoe UI;background:#f4f6fb;margin:0}
.header{background:#1f2937;color:#fff;padding:10px}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #ccc;padding:6px;font-size:13px;text-align:center}
th{background:#2563eb;color:white}
.back{color:white;text-decoration:none;font-weight:bold}
</style>
</head>
<body>

<div class="header">
<a class="back" href="javascript:history.back()">⬅ Back</a>
<h3>Filtered Students List</h3>
<p>
<?php
echo "Year: $year | Program: $prog ";
if($ref)   echo "| REFEMPID: $ref ";
if($phase) echo "| PHASE: $phase ";
if($type)  echo "| TYPE: ".($type=='H'?'HOSTEL':'DAYSCHOLAR');
?>
</p>
</div>

<table>
<tr>
    <th>S.No</th>
    <th>HTNO</th>
    <th>NAME</th>
    <th>PHASE</th>
    <th>TYPE</th>
    <th>BRANCH</th>
    <th>CLASS</th>
    <th>PHONE</th>
    <th>EAPCET NO</th>
    <th>STAFF</th>
</tr>

<?php
$i=1;
while($r=mysqli_fetch_assoc($result)){
    echo "<tr>";
    echo "<td>".$i++."</td>";
    echo "<td>{$r['htno']}</td>";
    echo "<td>{$r['name']}</td>";
    echo "<td>{$r['PHASE']}</td>";
    echo "<td>".($r['CLG_TYPE']=='H'?'HOSTEL':'DAYSCHOLAR')."</td>";
    echo "<td>{$r['branch']}</td>";
    echo "<td>{$r['classid']}</td>";
    echo "<td>{$r['phone']}</td>";
    echo "<td>{$r['EAPCET_NO']}</td>";
    echo "<td>{$r['staff_name']}</td>";
    echo "</tr>";
}
?>
</table>

</body>
</html>
