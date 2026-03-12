
<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

if(!isset($_SESSION['role']) || $_SESSION['role']!='PR'){
die("ACCESS DENIED");
}

include "db.php";


/* FILTER */

$prog=$_GET['prog'] ?? '';
$year=$_GET['year'] ?? '';

$filter="";

if($prog!=''){
$filter.=" AND s.prog='$prog'";
}

if($year!=''){
$filter.=" AND s.year='$year'";
}


/* CLASS LIST */

$sql="
SELECT DISTINCT u.classid,u.name,u.EMP_ID
FROM USERS u
JOIN STUDENTS s ON s.classid=u.classid
WHERE u.role='CPTO'
$filter
ORDER BY u.classid
";

$result=$conn->query($sql);


/* GRAND TOTALS */

$g_tm=$g_tf=$g_td=$g_th=0;
$g_sm=$g_sf=$g_sd=$g_sh=0;
$g_hm=$g_hf=$g_hd=$g_hh=0;
$g_km=$g_kf=$g_kd=$g_kh=0;

?>

<!DOCTYPE html>
<html>
<head>

<title>PR act Report</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
padding:20px;
}

h2{
text-align:center;
}

/* FILTER */

.filter{
text-align:center;
margin-bottom:15px;
}

select,button{
padding:6px;
margin:5px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
background:white;
}

th{
padding:8px;
border:1px solid #ccc;
color:white;
}

td{
padding:8px;
text-align:center;
border:1px solid #ccc;
}

a{
font-weight:bold;
text-decoration:none;
color:#2980b9;
}

/* COLORS */

.total-head{background:#f1c40f;color:black;}
.semi-head{background:#2ecc71;}
.hack-head{background:#e67e22;}
.kiot-head{background:#3498db;}

.sub-head{
background:#ffff66;
color:black;
}

.total-row{
background:#ecf0f1;
font-weight:bold;
}

.print-btn{
background:#27ae60;
color:white;
border:none;
cursor:pointer;
}

@media print{

.filter{
display:none;
}

}

</style>

</head>

<body>

<h2>Student act Report</h2>


<div class="filter">

<form>

Program

<select name="prog">

<option value="">All</option>
<option value="B.TECH" <?php if($prog=='B.TECH') echo "selected"; ?>>B.TECH</option>
<option value="DIP" <?php if($prog=='DIP') echo "selected"; ?>>DIP</option>

</select>

Year

<select name="year">

<option value="">All</option>
<option value="25" <?php if($year=='25') echo "selected"; ?>>25</option>
<option value="24" <?php if($year=='24') echo "selected"; ?>>24</option>
<option value="23" <?php if($year=='23') echo "selected"; ?>>23</option>

</select>

<button type="submit">Filter</button>

<button type="button" class="print-btn" onclick="window.print()">Print</button>

</form>

</div>


<table>

<tr>

<th rowspan="2">ClassID</th>
<th rowspan="2">CPTO</th>

<th colspan="4" class="total-head">Total Strength</th>

<th colspan="4" class="semi-head">Semi Residential</th>

<th colspan="4" class="hack-head">Hackathon March</th>

<th colspan="4" class="kiot-head">KIOT Tour</th>

</tr>


<tr class="sub-head">

<th>M</th><th>F</th><th>D</th><th>H</th>

<th>M</th><th>F</th><th>D</th><th>H</th>

<th>M</th><th>F</th><th>D</th><th>H</th>

<th>M</th><th>F</th><th>D</th><th>H</th>

</tr>


<?php

while($row=$result->fetch_assoc()){

$classid=$row['classid'];

/* TOTAL */

$total_m=$conn->query("SELECT COUNT(*) c FROM STUDENTS WHERE classid='$classid' AND gen='M'")->fetch_assoc()['c'];
$total_f=$conn->query("SELECT COUNT(*) c FROM STUDENTS WHERE classid='$classid' AND gen='F'")->fetch_assoc()['c'];
$total_d=$conn->query("SELECT COUNT(*) c FROM STUDENTS WHERE classid='$classid' AND CLG_TYPE='D'")->fetch_assoc()['c'];
$total_h=$conn->query("SELECT COUNT(*) c FROM STUDENTS WHERE classid='$classid' AND CLG_TYPE='H'")->fetch_assoc()['c'];


/* SEMI */

$semi_m=$conn->query("SELECT COUNT(*) c FROM semi_residencial sr JOIN STUDENTS s ON s.htno=sr.htno WHERE s.classid='$classid' AND sr.stayinhostel='YES' AND s.gen='M'")->fetch_assoc()['c'];
$semi_f=$conn->query("SELECT COUNT(*) c FROM semi_residencial sr JOIN STUDENTS s ON s.htno=sr.htno WHERE s.classid='$classid' AND sr.stayinhostel='YES' AND s.gen='F'")->fetch_assoc()['c'];
$semi_d=$conn->query("SELECT COUNT(*) c FROM semi_residencial sr JOIN STUDENTS s ON s.htno=sr.htno WHERE s.classid='$classid' AND sr.stayinhostel='YES' AND s.CLG_TYPE='D'")->fetch_assoc()['c'];
$semi_h=$conn->query("SELECT COUNT(*) c FROM semi_residencial sr JOIN STUDENTS s ON s.htno=sr.htno WHERE s.classid='$classid' AND sr.stayinhostel='YES' AND s.CLG_TYPE='H'")->fetch_assoc()['c'];


/* HACK */

$hack_m=$conn->query("SELECT COUNT(*) c FROM hackathon_march hm JOIN STUDENTS s ON s.htno=hm.htno WHERE s.classid='$classid' AND hm.interested='YES' AND s.gen='M'")->fetch_assoc()['c'];
$hack_f=$conn->query("SELECT COUNT(*) c FROM hackathon_march hm JOIN STUDENTS s ON s.htno=hm.htno WHERE s.classid='$classid' AND hm.interested='YES' AND s.gen='F'")->fetch_assoc()['c'];
$hack_d=$conn->query("SELECT COUNT(*) c FROM hackathon_march hm JOIN STUDENTS s ON s.htno=hm.htno WHERE s.classid='$classid' AND hm.interested='YES' AND s.CLG_TYPE='D'")->fetch_assoc()['c'];
$hack_h=$conn->query("SELECT COUNT(*) c FROM hackathon_march hm JOIN STUDENTS s ON s.htno=hm.htno WHERE s.classid='$classid' AND hm.interested='YES' AND s.CLG_TYPE='H'")->fetch_assoc()['c'];


/* KIOT */

$kiot_m=$conn->query("SELECT COUNT(*) c FROM kiot_tour kt JOIN STUDENTS s ON s.htno=kt.htno WHERE s.classid='$classid' AND kt.interested='YES' AND s.gen='M'")->fetch_assoc()['c'];
$kiot_f=$conn->query("SELECT COUNT(*) c FROM kiot_tour kt JOIN STUDENTS s ON s.htno=kt.htno WHERE s.classid='$classid' AND kt.interested='YES' AND s.gen='F'")->fetch_assoc()['c'];
$kiot_d=$conn->query("SELECT COUNT(*) c FROM kiot_tour kt JOIN STUDENTS s ON s.htno=kt.htno WHERE s.classid='$classid' AND kt.interested='YES' AND s.CLG_TYPE='D'")->fetch_assoc()['c'];
$kiot_h=$conn->query("SELECT COUNT(*) c FROM kiot_tour kt JOIN STUDENTS s ON s.htno=kt.htno WHERE s.classid='$classid' AND kt.interested='YES' AND s.CLG_TYPE='H'")->fetch_assoc()['c'];


/* GRAND TOTAL */

$g_tm+=$total_m; $g_tf+=$total_f; $g_td+=$total_d; $g_th+=$total_h;
$g_sm+=$semi_m; $g_sf+=$semi_f; $g_sd+=$semi_d; $g_sh+=$semi_h;
$g_hm+=$hack_m; $g_hf+=$hack_f; $g_hd+=$hack_d; $g_hh+=$hack_h;
$g_km+=$kiot_m; $g_kf+=$kiot_f; $g_kd+=$kiot_d; $g_kh+=$kiot_h;

?>

<tr>

<td><?php echo $classid; ?></td>

<td><?php echo $row['name']." - ".$row['EMP_ID']; ?></td>


<!-- TOTAL -->

<td>
<a href="act_students.php?type=total&classid=<?php echo $classid; ?>&gen=M">
<?php echo $total_m; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&classid=<?php echo $classid; ?>&gen=F">
<?php echo $total_f; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&classid=<?php echo $classid; ?>&clg=D">
<?php echo $total_d; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&classid=<?php echo $classid; ?>&clg=H">
<?php echo $total_h; ?>
</a>
</td>


<!-- SEMI -->

<td>
<a href="act_students.php?type=semi&classid=<?php echo $classid; ?>&gen=M">
<?php echo $semi_m; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&classid=<?php echo $classid; ?>&gen=F">
<?php echo $semi_f; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&classid=<?php echo $classid; ?>&clg=D">
<?php echo $semi_d; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&classid=<?php echo $classid; ?>&clg=H">
<?php echo $semi_h; ?>
</a>
</td>


<!-- HACK -->

<td>
<a href="act_students.php?type=hack&classid=<?php echo $classid; ?>&gen=M">
<?php echo $hack_m; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&classid=<?php echo $classid; ?>&gen=F">
<?php echo $hack_f; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&classid=<?php echo $classid; ?>&clg=D">
<?php echo $hack_d; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&classid=<?php echo $classid; ?>&clg=H">
<?php echo $hack_h; ?>
</a>
</td>


<!-- KIOT -->

<td>
<a href="act_students.php?type=kiot&classid=<?php echo $classid; ?>&gen=M">
<?php echo $kiot_m; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&classid=<?php echo $classid; ?>&gen=F">
<?php echo $kiot_f; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&classid=<?php echo $classid; ?>&clg=D">
<?php echo $kiot_d; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&classid=<?php echo $classid; ?>&clg=H">
<?php echo $kiot_h; ?>
</a>
</td>

</tr>
<?php } ?>


<tr class="total-row">

<td colspan="2">GRAND TOTAL</td>


<!-- TOTAL -->

<td>
<a href="act_students.php?type=total&gen=M">
<?php echo $g_tm; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&gen=F">
<?php echo $g_tf; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&clg=D">
<?php echo $g_td; ?>
</a>
</td>

<td>
<a href="act_students.php?type=total&clg=H">
<?php echo $g_th; ?>
</a>
</td>


<!-- SEMI -->

<td>
<a href="act_students.php?type=semi&gen=M">
<?php echo $g_sm; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&gen=F">
<?php echo $g_sf; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&clg=D">
<?php echo $g_sd; ?>
</a>
</td>

<td>
<a href="act_students.php?type=semi&clg=H">
<?php echo $g_sh; ?>
</a>
</td>


<!-- HACK -->

<td>
<a href="act_students.php?type=hack&gen=M">
<?php echo $g_hm; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&gen=F">
<?php echo $g_hf; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&clg=D">
<?php echo $g_hd; ?>
</a>
</td>

<td>
<a href="act_students.php?type=hack&clg=H">
<?php echo $g_hh; ?>
</a>
</td>


<!-- KIOT -->

<td>
<a href="act_students.php?type=kiot&gen=M">
<?php echo $g_km; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&gen=F">
<?php echo $g_kf; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&clg=D">
<?php echo $g_kd; ?>
</a>
</td>

<td>
<a href="act_students.php?type=kiot&clg=H">
<?php echo $g_kh; ?>
</a>
</td>

</tr>

</table>

</body>
</html>