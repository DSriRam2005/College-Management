<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

include "db2.php";

$msg="";

/* -------- DEDUCT FUNCTION -------- */

function deduct(&$due,$amt){

if($amt<=0) return $amt;

if($amt >= $due){
$amt -= $due;
$due = 0;
}
else{
$due -= $amt;
$amt = 0;
}

return $amt;

}

/* -------- CSV UPLOAD -------- */

if(isset($_POST['upload'])){

$file=$_FILES['csv']['tmp_name'];

$handle=fopen($file,"r");

fgetcsv($handle); // skip header

while(($data=fgetcsv($handle,1000,","))!==FALSE){

$htno=mysqli_real_escape_string($conn,$data[0]);
$amount=floatval($data[1]);

$q=mysqli_query($conn,"SELECT * FROM STUDENTS WHERE htno='$htno'");

if(mysqli_num_rows($q)==0){
continue;
}

$row=mysqli_fetch_assoc($q);

$olddue=floatval($row['olddue_12_9']);
$tfdue=floatval($row['tfdue_12_9']);
$otdue=floatval($row['otdues_12_9']);
$busdue=floatval($row['busdue_12_9']);
$hosdue=floatval($row['hosdue_12_9']);

$amount=deduct($olddue,$amount);
$amount=deduct($tfdue,$amount);
$amount=deduct($otdue,$amount);
$amount=deduct($busdue,$amount);
$amount=deduct($hosdue,$amount);

mysqli_query($conn,"UPDATE STUDENTS SET

olddue_12_9='$olddue',
tfdue_12_9='$tfdue',
otdues_12_9='$otdue',
busdue_12_9='$busdue',
hosdue_12_9='$hosdue',

olddue_today='$olddue',
tfdue_today='$tfdue',
otdues_today='$otdue',
busdue_today='$busdue',
hosdue_today='$hosdue'

WHERE htno='$htno'");

}

$msg="Bulk Payment Updated Successfully";

}

?>

<!DOCTYPE html>
<html>
<head>

<title>Bulk Fee Update</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
}

.box{
width:420px;
margin:100px auto;
background:#fff;
padding:30px;
border-radius:8px;
box-shadow:0 0 10px rgba(0,0,0,0.2);
}

h2{
text-align:center;
margin-bottom:20px;
}

input{
width:100%;
padding:10px;
margin-top:10px;
margin-bottom:15px;
border:1px solid #ccc;
border-radius:4px;
}

button{
width:100%;
padding:12px;
background:#007bff;
color:white;
border:none;
border-radius:4px;
font-size:16px;
cursor:pointer;
}

button:hover{
background:#0056b3;
}

.msg{
background:#d4edda;
padding:10px;
margin-bottom:15px;
border-radius:4px;
color:#155724;
}

</style>

</head>

<body>

<div class="box">

<h2>Bulk Fee Payment Upload</h2>

<?php
if($msg!=""){
echo "<div class='msg'>$msg</div>";
}
?>

<form method="post" enctype="multipart/form-data">

<label>Select CSV File</label>
<input type="file" name="csv" required>

<button type="submit" name="upload">Upload CSV</button>

</form>

</div>

</body>
</html>