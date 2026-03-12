<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'TPO') {
    die("ACCESS DENIED");
}

include "db.php";

$id = intval($_GET['id']);

$sql = "SELECT p.*, s.village, s.mandal, s.dist, s.photo
FROM PLACEMENT_DETAILS p
LEFT JOIN STUDENTS s ON p.htno=s.htno
WHERE p.id=$id";

$res = $conn->query($sql);
$data = $res->fetch_assoc();

if(isset($_POST['update'])){

$name=$_POST['name'];
$company=$_POST['company'];
$location=$_POST['location'];
$package=$_POST['package'];

$village=$_POST['village'];
$mandal=$_POST['mandal'];
$dist=$_POST['dist'];

$htno=$data['htno'];

$photo_path=$data['photo'];

if(!empty($_FILES['photo']['name'])){

$target_dir="uploads/";

if(!is_dir($target_dir)){
mkdir($target_dir);
}

$filename=time()."_".$_FILES['photo']['name'];
$target_file=$target_dir.$filename;

move_uploaded_file($_FILES['photo']['tmp_name'],$target_file);

$photo_path=$target_file;
}

/* UPDATE PLACEMENT */
$conn->query("UPDATE PLACEMENT_DETAILS SET

name='$name',
placed_company='$company',
placed_company_location='$location',
package='$package'

WHERE id=$id");

/* UPDATE STUDENT */
$conn->query("UPDATE STUDENTS SET

village='$village',
mandal='$mandal',
dist='$dist',
photo='$photo_path'

WHERE htno='$htno'");

echo "<script>
alert('Updated Successfully');
window.location='view_placements.php';
</script>";

}

?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Placement</title>

<style>

body{
font-family:Arial;
background:#f3f6ff;
}

.box{
width:420px;
margin:auto;
background:white;
padding:25px;
border-radius:10px;
margin-top:40px;
box-shadow:0 5px 15px rgba(0,0,0,0.2);
}

h3{
text-align:center;
}

input{
width:100%;
padding:10px;
margin-top:10px;
border:1px solid #ccc;
border-radius:6px;
}

img{
width:120px;
margin-top:10px;
border-radius:8px;
}

button{
padding:10px 20px;
background:#007bff;
color:white;
border:none;
border-radius:6px;
margin-top:15px;
cursor:pointer;
}

</style>

</head>

<body>

<div class="box">

<h3>Edit Placement</h3>

<form method="post" enctype="multipart/form-data">

Name
<input type="text" name="name" value="<?php echo $data['name']; ?>">

Company
<input type="text" name="company" value="<?php echo $data['placed_company']; ?>">

Location
<input type="text" name="location" value="<?php echo $data['placed_company_location']; ?>">

Package
<input type="text" name="package" value="<?php echo $data['package']; ?>">

<hr>

Village
<input type="text" name="village" value="<?php echo $data['village']; ?>">

Mandal
<input type="text" name="mandal" value="<?php echo $data['mandal']; ?>">

District
<input type="text" name="dist" value="<?php echo $data['dist']; ?>">

Photo

<?php if(!empty($data['photo'])){ ?>

<br>
<img src="<?php echo $data['photo']; ?>">

<?php } ?>

<input type="file" name="photo">

<button name="update">Update</button>

</form>

</div>

</body>
</html>