<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include "db.php";

$district = $_GET['dist'] ?? "";
$company  = $_GET['company'] ?? "";
$year     = $_GET['year'] ?? "";
$view     = $_GET['view'] ?? "district";


/* YEARS WITH PLACEMENTS */

$years=[];
$res=$conn->query("
SELECT DISTINCT S.year
FROM STUDENTS S
JOIN PLACEMENT_DETAILS P ON S.htno=P.htno
ORDER BY S.year DESC
");

while($r=$res->fetch_assoc()){
$years[]=$r['year'];
}


/* TOTAL PLACEMENTS */

$total_sql="
SELECT COUNT(P.id) as total
FROM PLACEMENT_DETAILS P
JOIN STUDENTS S ON S.htno=P.htno
WHERE 1
";

if($district!=""){
$total_sql.=" AND S.dist='".$conn->real_escape_string($district)."'";
}

if($company!=""){
$total_sql.=" AND P.placed_company='".$conn->real_escape_string($company)."'";
}

if($year!=""){
$total_sql.=" AND S.year='".$conn->real_escape_string($year)."'";
}

$total=$conn->query($total_sql)->fetch_assoc()['total'];


/* DISTRICT COUNTS */

$dist_sql="
SELECT 
S.dist,
COUNT(P.id) as total
FROM STUDENTS S
JOIN PLACEMENT_DETAILS P ON S.htno=P.htno
WHERE S.dist!=''
";

if($year!=""){
$dist_sql.=" AND S.year='$year'";
}

$dist_sql.=" GROUP BY S.dist ORDER BY total DESC";

$dist_result=$conn->query($dist_sql);


/* COMPANY COUNTS */

$comp_sql="
SELECT 
P.placed_company,
MAX(P.company_logo) as company_logo,
COUNT(P.id) as total
FROM PLACEMENT_DETAILS P
JOIN STUDENTS S ON S.htno=P.htno
WHERE 1
";

if($year!=""){
$comp_sql.=" AND S.year='$year'";
}

$comp_sql.=" GROUP BY P.placed_company ORDER BY total DESC";

$comp_result=$conn->query($comp_sql);


/* STUDENT LIST */

$students=[];

if($district!="" || $company!=""){

$sql="
SELECT 
S.htno,
S.name,
S.photo,
S.village,
S.mandal,
S.dist,
P.placed_company,
P.package,
P.company_logo
FROM STUDENTS S
JOIN PLACEMENT_DETAILS P ON S.htno=P.htno
WHERE 1
";

$params=[];
$types="";

if($district!=""){
$sql.=" AND S.dist=?";
$params[]=$district;
$types.="s";
}

if($company!=""){
$sql.=" AND P.placed_company=?";
$params[]=$company;
$types.="s";
}

if($year!=""){
$sql.=" AND S.year=?";
$params[]=$year;
$types.="s";
}

$sql.=" ORDER BY P.placed_company";

$stmt=$conn->prepare($sql);

if($params){
$stmt->bind_param($types,...$params);
}

$stmt->execute();
$res=$stmt->get_result();

while($row=$res->fetch_assoc()){
$students[]=$row;
}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Placement Report</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>

<style>

body{
font-family:Segoe UI;
background:#eef1f5;
margin:0;
padding:20px;
}

.container{
max-width:1300px;
margin:auto;
}

.student-card-gradient{
background:linear-gradient(0deg, rgba(16,21,34,0.95) 0%, rgba(16,21,34,0.6) 30%, rgba(16,21,34,0) 60%);
}

</style>

</head>

<body>

<div class="container">


<!-- HEADER -->

<div class="flex justify-between items-center mb-6 flex-wrap gap-4">

<h1 class="text-2xl md:text-3xl font-bold">
Placement Report
</h1>

<form method="GET">

<input type="hidden" name="view" value="<?php echo $view ?>">

<select name="year" onchange="this.form.submit()" class="border p-2 rounded">

<option value="">All Years</option>

<?php
foreach($years as $y){
$sel=($y==$year)?"selected":"";
echo "<option value='$y' $sel>$y</option>";
}
?>

</select>

</form>

</div>



<!-- TOTAL -->

<div class="bg-white rounded-xl shadow p-6 mb-8 text-center">

<div class="text-gray-500 text-sm">
Total Placements
</div>

<div class="text-4xl font-bold text-blue-600">
<?php echo $total ?>
</div>

</div>



<!-- TOGGLE -->

<div class="flex flex-wrap justify-center gap-3 mb-6">

<a href="?view=district<?php if($year!='') echo '&year='.$year;?>"
class="px-4 py-2 rounded <?php echo $view=='district'?'bg-blue-600 text-white':'bg-gray-200';?>">
District Wise
</a>

<a href="?view=company<?php if($year!='') echo '&year='.$year;?>"
class="px-4 py-2 rounded <?php echo $view=='company'?'bg-blue-600 text-white':'bg-gray-200';?>">
Company Wise
</a>

</div>



<?php if($district=="" && $company==""){ ?>


<!-- DISTRICT VIEW -->

<?php if($view=="district"){ ?>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

<?php

while($row=$dist_result->fetch_assoc()){

$dist=$row['dist'];
$count=$row['total'];

$link="?view=district&dist=$dist";

if($year!=""){
$link.="&year=$year";
}

echo "

<a href='$link'>

<div class='bg-white rounded-xl shadow-lg p-4 text-center hover:shadow-xl min-h-[120px]'>

<div class='text-sm md:text-lg font-semibold break-words'>$dist</div>

<div class='text-2xl md:text-3xl font-bold text-blue-600 mt-2'>$count</div>

</div>

</a>

";

}

?>

</div>

<?php } ?>


<!-- COMPANY VIEW -->

<?php if($view=="company"){ ?>

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

<?php

while($row=$comp_result->fetch_assoc()){

$company=$row['placed_company'];
$count=$row['total'];

$logo=trim($row['company_logo']);
if($logo=="") $logo="no_logo.png";

$link="?view=company&company=".urlencode($company);

if($year!=""){
$link.="&year=$year";
}

echo "

<a href='$link'>

<div class='bg-white rounded-xl shadow-lg p-4 text-center hover:shadow-xl'>

<img src='$logo' style='height:40px;margin:auto;margin-bottom:10px;object-fit:contain'>

<div class='text-sm md:text-lg font-semibold break-words'>$company</div>

<div class='text-2xl md:text-3xl font-bold text-green-600 mt-2'>$count</div>

</div>

</a>

";

}

?>

</div>

<?php } ?>

<?php } ?>


<!-- STUDENT CARDS -->

<?php if($district!="" || $company!=""){ ?>

<a href="placements.php?view=<?php echo $view ?>&year=<?php echo $year ?>"
class="bg-blue-600 text-white px-4 py-2 rounded mb-6 inline-block">
← Back
</a>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

<?php

foreach($students as $s){

$photo=$s['photo'] ?: "no_photo.png";

$logo=trim($s['company_logo']);
if($logo=="") $logo="no_logo.png";

echo "

<div class='bg-white rounded-xl overflow-hidden shadow-xl border'>

<div class='relative w-full aspect-[3/4] overflow-hidden'>

<img src='$photo' class='w-full h-full object-cover'>

<div class='absolute inset-0 student-card-gradient flex flex-col justify-end p-4'>

<h2 class='text-white text-lg font-bold'>{$s['name']}</h2>

</div>

</div>

<div class='p-4 space-y-3'>

<div class='flex justify-between border-b pb-2'>

<div>
<p class='text-xs text-gray-400'>HTNO</p>
<p class='font-semibold'>{$s['htno']}</p>
</div>

<div>
<p class='text-xs text-gray-400'>Package</p>
<p class='text-green-600 font-semibold'>{$s['package']}</p>
</div>

</div>

<div class='flex items-center gap-3'>

<img src='$logo' style='width:80px;height:40px;object-fit:contain'>

<div class='font-semibold'>{$s['placed_company']}</div>

</div>

<div class='text-sm text-gray-600'>
{$s['village']}, {$s['mandal']}<br>
{$s['dist']}
</div>

</div>

</div>

";

}

?>

</div>

<?php } ?>

</div>

</body>
</html>