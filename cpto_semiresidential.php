<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role']!='CPTO'){
    die("ACCESS DENIED");
}

include "db.php";

$username=$_SESSION['username'];

/* GET CPTO CLASS */
$q=$conn->query("SELECT classid FROM USERS WHERE username='$username'");
$user=$q->fetch_assoc();
$classid=$user['classid'];


/* SAVE ALL */
if(isset($_POST['save_all']))
{
    foreach($_POST['status'] as $htno=>$status)
    {
        $check=$conn->query("SELECT id FROM semi_residencial WHERE htno='$htno'");

        if($check->num_rows>0)
        {
            $conn->query("UPDATE semi_residencial SET stayinhostel='$status' WHERE htno='$htno'");
        }
        else
        {
            $conn->query("INSERT INTO semi_residencial(htno,stayinhostel)
                          VALUES('$htno','$status')");
        }
    }

    echo "<script>alert('Saved Successfully');</script>";
}


/* GET STUDENTS */
$sql="
SELECT s.htno,s.name,s.branch,s.gen
FROM STUDENTS s
WHERE s.CLG_TYPE='D'
AND s.classid='$classid'
ORDER BY s.htno,s.gen
";

$result=$conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Semi Residential</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:10px;
margin:0;
}

h2{
margin-bottom:15px;
text-align:center;
}

/* TABLE SCROLL CONTAINER */

.table-scroll{
width:100%;
overflow-x:auto;
}

/* TABLE */

table{
width:100%;
min-width:600px;
border-collapse:collapse;
background:white;
box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

th{
background:#34495e;
color:white;
padding:10px;
font-size:14px;
}

td{
padding:8px;
border-bottom:1px solid #ddd;
text-align:center;
font-size:14px;
}

tr:hover{
background:#f2f2f2;
}

/* RADIO */

.radio-box{
display:flex;
justify-content:center;
gap:15px;
}

.yes{
color:green;
font-weight:bold;
}

.no{
color:red;
font-weight:bold;
}

/* SAVE BUTTON */

.save-btn{
margin-top:15px;
background:#27ae60;
color:white;
padding:12px 25px;
border:none;
font-size:16px;
cursor:pointer;
width:100%;
max-width:300px;
display:block;
margin-left:auto;
margin-right:auto;
}

.save-btn:hover{
background:#1e8449;
}

/* MOBILE */

@media (max-width:768px){

th,td{
font-size:12px;
padding:6px;
}

.radio-box{
gap:10px;
}

}

</style>

</head>
<body>

<h2>Mark Semi Residential Students</h2>

<form method="post">

<div class="table-scroll">

<table>

<tr>
<th>HTNO</th>
<th>Name</th>
<th>Branch</th>
<th>Gender</th>
<th>Hostel Status</th>
</tr>

<?php

while($row=$result->fetch_assoc())
{

$htno=$row['htno'];

/* EXISTING STATUS */
$r=$conn->query("SELECT stayinhostel FROM semi_residencial WHERE htno='$htno'");
$d=$r->fetch_assoc();
$status=$d['stayinhostel'] ?? 'NO';

?>

<tr>

<td><?php echo $row['htno']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['branch']; ?></td>
<td><?php echo $row['gen']; ?></td>

<td>

<div class="radio-box">

<label class="yes">
<input type="radio" name="status[<?php echo $htno;?>]" value="YES"
<?php if($status=="YES") echo "checked"; ?>>
YES
</label>

<label class="no">
<input type="radio" name="status[<?php echo $htno;?>]" value="NO"
<?php if($status=="NO") echo "checked"; ?>>
NO
</label>

</div>

</td>

</tr>

<?php } ?>

</table>

</div>

<button class="save-btn" name="save_all">SAVE ALL</button>

</form>
<br><br><br>
</body>
</html>