<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

if(!isset($_SESSION['role']) || $_SESSION['role']!='PR'){
die("ACCESS DENIED");
}

include "db.php";

/* GET PARAMETERS */

$type=$_GET['type'] ?? 'total';
$classid=$_GET['classid'] ?? '';
$gen=$_GET['gen'] ?? '';
$clg=$_GET['clg'] ?? '';

/* BASE QUERY */

$sql="
SELECT s.htno,s.name,s.branch,s.gen,s.CLG_TYPE
FROM STUDENTS s
";

/* JOIN BASED ON TYPE */

if($type=='semi'){
$sql.=" JOIN semi_residencial sr ON sr.htno=s.htno AND sr.stayinhostel='YES' ";
$title="Semi Residential Students";
}

elseif($type=='hack'){
$sql.=" JOIN hackathon_march hm ON hm.htno=s.htno AND hm.interested='YES' ";
$title="Hackathon Students";
}

elseif($type=='kiot'){
$sql.=" JOIN kiot_tour kt ON kt.htno=s.htno AND kt.interested='YES' ";
$title="KIOT Tour Students";
}

else{
$title="Total Students";
}

/* WHERE */

$where=" WHERE 1 ";

if($classid!=''){
$where.=" AND s.classid='$classid'";
}

if($gen!=''){
$where.=" AND s.gen='$gen'";
}

if($clg!=''){
$where.=" AND s.CLG_TYPE='$clg'";
}

$sql.=$where." ORDER BY s.htno";

$result=$conn->query($sql);

?>

<!DOCTYPE html>
<html>

<head>

<title><?php echo $title; ?></title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:20px;
}

h2{
text-align:center;
}

.controls{
text-align:center;
margin-bottom:15px;
}

button,input{
padding:6px;
margin:5px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
}

th{
background:#34495e;
color:white;
padding:10px;
}

td{
padding:8px;
border-bottom:1px solid #ddd;
text-align:center;
}

tr:hover{
background:#f2f2f2;
}

</style>

<script>

function searchTable(){

let input=document.getElementById("search").value.toLowerCase();
let rows=document.querySelectorAll("tbody tr");

rows.forEach(r=>{
let txt=r.innerText.toLowerCase();
r.style.display=txt.includes(input) ? "" : "none";
});

}

</script>

</head>

<body>

<h2><?php echo $title; ?></h2>


<div class="controls">

<button onclick="history.back()">⬅ Back</button>

<button onclick="window.print()">🖨 Print</button>

<input type="text" id="search" onkeyup="searchTable()" placeholder="Search student">

</div>


<table>

<thead>

<tr>
<th>HTNO</th>
<th>Name</th>
<th>Branch</th>
<th>Gender</th>
<th>CLG TYPE</th>
</tr>

</thead>

<tbody>

<?php

while($row=$result->fetch_assoc()){
?>

<tr>

<td><?php echo $row['htno']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['branch']; ?></td>
<td><?php echo $row['gen']; ?></td>
<td><?php echo $row['CLG_TYPE']; ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</body>
</html>

