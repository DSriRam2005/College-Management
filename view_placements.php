<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'TPO') {
    die("ACCESS DENIED");
}

include "db.php";

/* ---------------- DELETE LOGIC ---------------- */
if (isset($_GET['delete_id'])) {

    $delete_id = intval($_GET['delete_id']);

    $delete_sql = "DELETE FROM PLACEMENT_DETAILS WHERE id = $delete_id";

    if ($conn->query($delete_sql)) {
        echo "<script>
                alert('Deleted Successfully');
                window.location.href='view_placements.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Delete Failed');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Placed Students</title>

<style>

body {
margin:0;
padding:0;
font-family:"Segoe UI", Arial;
background:#f3f6ff;
}

h2{
text-align:center;
padding:15px;
background:linear-gradient(90deg,#0066ff,#0099ff,#00ccff);
color:white;
margin:0 0 20px 0;
}

.search-box{
text-align:center;
margin:10px auto 25px auto;
}

.search-box input{
width:70%;
max-width:400px;
padding:10px;
border:2px solid #0077ff;
border-radius:25px;
}

.search-box button{
padding:10px 20px;
border:none;
background:#007bff;
color:white;
border-radius:25px;
cursor:pointer;
}

.container{
display:flex;
flex-wrap:wrap;
justify-content:center;
padding-bottom:40px;
}

.card{
width:300px;
padding:15px;
border-radius:15px;
margin:12px;
background:white;
box-shadow:0 5px 15px rgba(0,0,0,0.15);
}

.card img{
width:100%;
height:180px;
object-fit:cover;
border-radius:12px;
border:2px solid #00aaff;
}

.title{
font-size:20px;
font-weight:700;
margin-top:10px;
color:#003366;
}

.info{
margin-top:7px;
line-height:22px;
font-size:14px;
color:#333;
}

.edit-btn{
display:inline-block;
padding:8px 15px;
background:#009933;
color:white;
text-decoration:none;
border-radius:8px;
font-size:13px;
margin-top:10px;
margin-right:6px;
}

.delete-btn{
display:inline-block;
padding:8px 15px;
background:#ff3b3b;
color:white;
text-decoration:none;
border-radius:8px;
font-size:13px;
margin-top:10px;
}

.edit-btn:hover{background:#006600;}
.delete-btn:hover{background:#cc0000;}

</style>

</head>

<body>

<h2>Placed Students</h2>

<form method="GET" class="search-box">

<input type="text" name="search"
placeholder="Search HTNO / Name / Company"
value="<?php echo $_GET['search'] ?? ''; ?>">

<button type="submit">Search</button>

</form>

<div class="container">

<?php

$search=$_GET['search'] ?? "";

if($search!=""){

$search=$conn->real_escape_string($search);

$sql="SELECT p.*,s.photo,s.village,s.mandal,s.dist
FROM PLACEMENT_DETAILS p
LEFT JOIN STUDENTS s ON p.htno=s.htno
WHERE p.htno LIKE '%$search%'
OR p.name LIKE '%$search%'
OR p.placed_company LIKE '%$search%'
ORDER BY p.id DESC";

}
else{

$sql="SELECT p.*,s.photo,s.village,s.mandal,s.dist
FROM PLACEMENT_DETAILS p
LEFT JOIN STUDENTS s ON p.htno=s.htno
ORDER BY p.id DESC";

}

$res=$conn->query($sql);

if($res->num_rows>0){

while($r=$res->fetch_assoc()){

$photo = (!empty($r['photo']) && file_exists($r['photo']))
? $r['photo']
: "no-photo.png";

?>

<div class="card">

<img src="<?php echo $photo; ?>">

<div class="title"><?php echo htmlspecialchars($r['name']); ?></div>

<div class="info">

<b>HTNO:</b> <?php echo $r['htno']; ?><br>

<b>Village:</b> <?php echo $r['village'] ?: "-"; ?><br>
<b>Mandal:</b> <?php echo $r['mandal'] ?: "-"; ?><br>
<b>District:</b> <?php echo $r['dist'] ?: "-"; ?><br><br>

<b>Company:</b> <?php echo $r['placed_company']; ?><br>
<b>Location:</b> <?php echo $r['placed_company_location']; ?><br>
<b>Package:</b> <?php echo $r['package']; ?><br>

</div>

<a href="edit_placement.php?id=<?php echo $r['id']; ?>" class="edit-btn">
Edit
</a>

<a href="?delete_id=<?php echo $r['id']; ?>"
class="delete-btn"
onclick="return confirm('Delete this record?')">
Delete
</a>

</div>

<?php
}
}
else{
echo "<p style='text-align:center;'>No Records Found</p>";
}

?>

</div>

</body>
</html>