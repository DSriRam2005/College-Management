<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

include "db.php";

$updated = 0;
$notfound = [];
$duplicates = [];
$total = 0;

if(isset($_POST['upload'])){

    if($_FILES['csv']['name']!=""){

        $file = fopen($_FILES['csv']['tmp_name'],"r");

        $seen = [];

        while(($data = fgetcsv($file,1000,",")) !== FALSE){

            $total++;

            $htno = trim($data[0]);
            $clg_type = trim($data[1]);

            if(isset($seen[$htno])){
                $duplicates[] = $htno;
                continue;
            }

            $seen[$htno]=1;

            $check = $conn->query("SELECT id FROM STUDENTS WHERE htno='$htno'");

            if($check->num_rows > 0){

                $conn->query("UPDATE STUDENTS SET CLG_TYPE='$clg_type' WHERE htno='$htno'");
                $updated++;

            }else{
                $notfound[] = $htno;
            }

        }

        fclose($file);
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Upload CLG TYPE</title>

<style>

body{
font-family:Arial;
background:#f4f6fa;
padding:40px;
}

.container{
width:600px;
margin:auto;
background:white;
padding:30px;
border-radius:8px;
box-shadow:0 0 10px rgba(0,0,0,0.1);
}

h2{
text-align:center;
}

input[type=file]{
width:100%;
padding:10px;
margin:15px 0;
}

button{
background:#1f2f86;
color:white;
border:none;
padding:10px 20px;
cursor:pointer;
border-radius:5px;
}

.report{
margin-top:30px;
background:#fafafa;
padding:15px;
border-radius:5px;
}

.error{
color:red;
}

.success{
color:green;
}

</style>

</head>

<body>

<div class="container">

<h2>Upload CSV (HTNO - CLG TYPE)</h2>

<form method="post" enctype="multipart/form-data">

<input type="file" name="csv" required>

<button type="submit" name="upload">Upload</button>

</form>

<?php if(isset($_POST['upload'])){ ?>

<div class="report">

<h3>Upload Report</h3>

<p>Total Rows: <b><?php echo $total; ?></b></p>

<p class="success">Updated: <b><?php echo $updated; ?></b></p>

<p class="error">Not Found: <b><?php echo count($notfound); ?></b></p>

<p class="error">Duplicates in CSV: <b><?php echo count($duplicates); ?></b></p>

<?php if(count($notfound)>0){ ?>

<h4>Not Found HTNO</h4>
<textarea style="width:100%;height:120px;"><?php echo implode("\n",$notfound); ?></textarea>

<?php } ?>

<?php if(count($duplicates)>0){ ?>

<h4>Duplicate HTNO in CSV</h4>
<textarea style="width:100%;height:120px;"><?php echo implode("\n",$duplicates); ?></textarea>

<?php } ?>

</div>

<?php } ?>

</div>

</body>
</html>