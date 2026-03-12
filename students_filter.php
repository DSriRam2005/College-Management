<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "db.php";

$year   = $_GET['year'] ?? '';
$dist   = $_GET['dist'] ?? '';
$mandal = $_GET['mandal'] ?? '';

/* ===== UNIQUE YEARS ===== */
$years = [];
$res = $conn->query("SELECT DISTINCT year 
                     FROM STUDENTS 
                     WHERE prog='B.TECH' 
                     ORDER BY year ASC");
while($row=$res->fetch_assoc()){
    $years[]=$row['year'];
}

/* ===== UNIQUE DISTRICTS (BASED ON YEAR) ===== */
$districts = [];
if($year!=''){
    $stmt=$conn->prepare("SELECT DISTINCT dist 
                          FROM STUDENTS 
                          WHERE prog='B.TECH' 
                          AND year=? 
                          AND dist!=''
                          ORDER BY dist ASC");
    $stmt->bind_param("i",$year);
    $stmt->execute();
    $result=$stmt->get_result();
    while($row=$result->fetch_assoc()){
        $districts[]=$row['dist'];
    }
}

/* ===== UNIQUE MANDALS (BASED ON YEAR + DISTRICT) ===== */
$mandals = [];
if($year!='' && $dist!=''){
    $stmt=$conn->prepare("SELECT DISTINCT mandal 
                          FROM STUDENTS 
                          WHERE prog='B.TECH' 
                          AND year=? 
                          AND dist=? 
                          AND mandal!=''
                          ORDER BY mandal ASC");
    $stmt->bind_param("is",$year,$dist);
    $stmt->execute();
    $result=$stmt->get_result();
    while($row=$result->fetch_assoc()){
        $mandals[]=$row['mandal'];
    }
}

/* ===== FETCH STUDENTS ===== */
$students=[];
if($year!='' && $dist!=''){

    $sql="SELECT htno,name,year,branch,inter_clg,dist,mandal 
          FROM STUDENTS 
          WHERE prog='B.TECH' 
          AND year=? 
          AND dist=?";

    $params=[$year,$dist];
    $types="is";

    if($mandal!=''){
        $sql.=" AND mandal=?";
        $params[]=$mandal;
        $types.="s";
    }

    $stmt=$conn->prepare($sql);
    $stmt->bind_param($types,...$params);
    $stmt->execute();
    $students=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>B.TECH Students Filter</title>
<style>
body{font-family:Arial;padding:20px;}
table{border-collapse:collapse;width:100%;margin-top:20px;}
th,td{border:1px solid #ccc;padding:8px;}
th{background:#f4f4f4;}
select,button{padding:8px;margin-right:10px;}
</style>
</head>
<body>

<h2>B.TECH Students Filter</h2>

<form method="GET">

<label>Year *</label>
<select name="year" onchange="this.form.submit()" required>
    <option value="">Select Year</option>
    <?php foreach($years as $y): ?>
        <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>>
            <?= $y ?>
        </option>
    <?php endforeach; ?>
</select>

<label>District *</label>
<select name="dist" onchange="this.form.submit()" required>
    <option value="">Select District</option>
    <?php foreach($districts as $d): ?>
        <option value="<?= $d ?>" <?= $dist==$d?'selected':'' ?>>
            <?= $d ?>
        </option>
    <?php endforeach; ?>
</select>

<label>Mandal (Optional)</label>
<select name="mandal">
    <option value="">All Mandals</option>
    <?php foreach($mandals as $m): ?>
        <option value="<?= $m ?>" <?= $mandal==$m?'selected':'' ?>>
            <?= $m ?>
        </option>
    <?php endforeach; ?>
</select>

<button type="submit">Search</button>
</form>

<?php if($year!='' && $dist!=''): ?>

<h3>Total Students: <?= count($students) ?></h3>

<table>
<tr>
<th>HT No</th>
<th>Name</th>
<th>Year</th>
<th>Branch</th>
<th>Inter College</th>
<th>District</th>
<th>Mandal</th>
</tr>

<?php if(count($students)>0): ?>
<?php foreach($students as $row): ?>
<tr>
<td><?= $row['htno'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['year'] ?></td>
<td><?= $row['branch'] ?></td>
<td><?= $row['inter_clg'] ?></td>
<td><?= $row['dist'] ?></td>
<td><?= $row['mandal'] ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="7">No students found</td></tr>
<?php endif; ?>

</table>

<?php endif; ?>

</body>
</html>