
<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='CPTO'){
die("ACCESS DENIED");
}

include "db.php";

$username=$_SESSION['username'];

/* GET CLASS */

$q=$conn->query("SELECT classid FROM USERS WHERE username='$username'");
$user=$q->fetch_assoc();
$classid=$user['classid'];


/* SAVE */

if(isset($_POST['save_all']))
{

/* SEMI */
if(isset($_POST['semi']))
{
foreach($_POST['semi'] as $htno=>$status){

$status=strtoupper($status);

$check=$conn->query("SELECT id FROM semi_residencial WHERE htno='$htno'");

if($check->num_rows>0)
$conn->query("UPDATE semi_residencial SET stayinhostel='$status' WHERE htno='$htno'");
else
$conn->query("INSERT INTO semi_residencial(htno,stayinhostel) VALUES('$htno','$status')");

}
}

/* HACKATHON */
if(isset($_POST['hack']))
{
foreach($_POST['hack'] as $htno=>$status){

$status=strtolower($status);

$check=$conn->query("SELECT id FROM hackathon_march WHERE htno='$htno'");

if($check->num_rows>0)
$conn->query("UPDATE hackathon_march SET interested='$status' WHERE htno='$htno'");
else
$conn->query("INSERT INTO hackathon_march(htno,interested) VALUES('$htno','$status')");

}
}

/* KIOT */
if(isset($_POST['kiot']))
{
foreach($_POST['kiot'] as $htno=>$status){

$status=strtolower($status);

$check=$conn->query("SELECT id FROM kiot_tour WHERE htno='$htno'");

if($check->num_rows>0)
$conn->query("UPDATE kiot_tour SET interested='$status' WHERE htno='$htno'");
else
$conn->query("INSERT INTO kiot_tour(htno,interested) VALUES('$htno','$status')");

}
}

echo "<script>alert('Saved Successfully');</script>";

}


/* GET STUDENTS */

$sql="
SELECT htno,name,branch,gen,prog,year,CLG_TYPE
FROM STUDENTS
WHERE classid='$classid'
ORDER BY htno
";

$result=$conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Student Activities</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:10px;
margin:0;
}

h2{
text-align:center;
margin-bottom:20px;
}

.table-scroll{
overflow-x:auto;
}

table{
width:100%;
min-width:900px;
border-collapse:collapse;
background:white;
box-shadow:0 2px 10px rgba(0,0,0,0.1);
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

.radio-box{
display:flex;
justify-content:center;
gap:10px;
}

.yes{
color:green;
font-weight:bold;
}

.no{
color:red;
font-weight:bold;
}

.save-btn{
background:#27ae60;
color:white;
padding:12px 25px;
border:none;
font-size:16px;
cursor:pointer;
display:block;
margin:20px auto;
}

</style>

</head>
<body>

<h2>Student Activity Selection</h2>

<form method="post">

<div class="table-scroll">

<table>

<tr>
<th>HTNO</th>
<th>Name</th>
<th>Branch</th>
<th>Gender</th>
<th>Semi Residential</th>
<th>Hackathon March</th>
<th>KIOT Tour</th>
</tr>

<?php

while($row=$result->fetch_assoc())
{

$htno=$row['htno'];
$prog=$row['prog'];
$year=$row['year'];
$clg=$row['CLG_TYPE'];


/* EXISTING VALUES */

$semi=$conn->query("SELECT stayinhostel FROM semi_residencial WHERE htno='$htno'")->fetch_assoc()['stayinhostel'] ?? 'NO';

$hack=$conn->query("SELECT interested FROM hackathon_march WHERE htno='$htno'")->fetch_assoc()['interested'] ?? 'no';

$kiot=$conn->query("SELECT interested FROM kiot_tour WHERE htno='$htno'")->fetch_assoc()['interested'] ?? 'no';

?>

<tr>

<td><?php echo $row['htno']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['branch']; ?></td>
<td><?php echo $row['gen']; ?></td>


<!-- SEMI -->

<td>

<?php
if(
(
($prog=='B.TECH' && in_array($year,[23,24,25]))
||
($prog=='DIP' && $year==25)
)
&& $clg=='D'
){
?>

<div class="radio-box">

<label class="yes">
<input type="radio" name="semi[<?php echo $htno;?>]" value="YES" <?php if($semi=="YES") echo "checked"; ?>>YES
</label>

<label class="no">
<input type="radio" name="semi[<?php echo $htno;?>]" value="NO" <?php if($semi=="NO") echo "checked"; ?>>NO
</label>

</div>

<?php } ?>

</td>



<!-- HACKATHON -->

<td>

<?php
if($prog=='B.TECH' && $year==24){
?>

<div class="radio-box">

<label class="yes">
<input type="radio" name="hack[<?php echo $htno;?>]" value="yes" <?php if($hack=="yes") echo "checked"; ?>>YES
</label>

<label class="no">
<input type="radio" name="hack[<?php echo $htno;?>]" value="no" <?php if($hack=="no") echo "checked"; ?>>NO
</label>

</div>

<?php } ?>

</td>



<!-- KIOT -->

<td>

<?php
if($prog=='B.TECH' && $year==25){
?>

<div class="radio-box">

<label class="yes">
<input type="radio" name="kiot[<?php echo $htno;?>]" value="yes" <?php if($kiot=="yes") echo "checked"; ?>>YES
</label>

<label class="no">
<input type="radio" name="kiot[<?php echo $htno;?>]" value="no" <?php if($kiot=="no") echo "checked"; ?>>NO
</label>

</div>

<?php } ?>

</td>


</tr>

<?php } ?>

</table>

</div>

<button class="save-btn" name="save_all">SAVE ALL</button>

</form>

</body>
</html>

