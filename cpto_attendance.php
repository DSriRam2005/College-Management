<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

/* ========= AUTH ========= */
if (!isset($_SESSION['username'])) die("Login required");

$username=$_SESSION['username'];

$stmt=$conn->prepare("SELECT EMP_ID FROM USERS WHERE username=? AND role='CPTO'");
$stmt->bind_param("s",$username);
$stmt->execute();
$res=$stmt->get_result();
if($res->num_rows==0) die("Access denied");

$empid=$res->fetch_assoc()['EMP_ID'];

/* ========= CPTO CLASSES ========= */
$stmt=$conn->prepare("
SELECT classid
FROM USERS
WHERE EMP_ID=? AND role='CPTO'
AND classid IS NOT NULL AND classid!=''
");
$stmt->bind_param("s",$empid);
$stmt->execute();
$res=$stmt->get_result();

$classes=[];
while($r=$res->fetch_assoc()) $classes[]=$r['classid'];
if(!$classes) die("No class assigned");

/* ========= INPUTS ========= */
$from_date=$_GET['from_date'] ?? date('Y-m-01');
$to_date=$_GET['to_date'] ?? date('Y-m-t');

/* DATE LIST */
$dates=[];
$start=new DateTime($from_date);
$end=new DateTime($to_date);
while($start<=$end){
    $dates[]=$start->format('Y-m-d');
    $start->modify('+1 day');
}

/* ========= STUDENTS ========= */
$ph=implode(',',array_fill(0,count($classes),'?'));
$types=str_repeat('s',count($classes));

$stmt=$conn->prepare("
SELECT htno,name,classid,teamid,college,stpo
FROM STUDENTS
WHERE debarred=0
AND classid IN ($ph)
ORDER BY classid,teamid,htno
");
$stmt->bind_param($types,...$classes);
$stmt->execute();
$students=$stmt->get_result();

/* ========= CTPO MAP ========= */
$ctpo_map=[];
$stmt=$conn->prepare("
SELECT classid,EMP_ID
FROM USERS
WHERE role='CPTO'
AND classid IN ($ph)
");
$stmt->bind_param($types,...$classes);
$stmt->execute();
$res=$stmt->get_result();
while($r=$res->fetch_assoc()){
    $ctpo_map[$r['classid']]=$r['EMP_ID'];
}

/* ========= ATTENDANCE MAP ========= */
$att_map=[];
$stmt=$conn->prepare("
SELECT htno,att_date,status
FROM attendance
WHERE att_date BETWEEN ? AND ?
");
$stmt->bind_param("ss",$from_date,$to_date);
$stmt->execute();
$res=$stmt->get_result();

while($r=$res->fetch_assoc()){
    $att_map[$r['htno']][$r['att_date']]=$r['status'];
}

/* ========= PROCESS ========= */
$rows=[];
$day_present=[];
$day_absent=[];

while($s=$students->fetch_assoc()){

    $ht=$s['htno'];
    $working=0;
    $present_days=0;
    $daily=[];

    foreach($dates as $d){

        if(isset($att_map[$ht][$d])){
            $working++;

            if($att_map[$ht][$d]=='Present'){
                $present_days++;
                $daily[$d]='P';
                $day_present[$d]=($day_present[$d]??0)+1;
            }else{
                $daily[$d]='A';
                $day_absent[$d]=($day_absent[$d]??0)+1;
            }
        }else{
            $daily[$d]='-';
        }
    }

    $rows[]=[
        'htno'=>$s['htno'],
        'name'=>$s['name'],
        'classid'=>$s['classid'],
        'teamid'=>$s['teamid'],
        'college'=>$s['college'],
        'stpo'=>$s['stpo'],
        'ctpo'=>$ctpo_map[$s['classid']] ?? '',
        'daily'=>$daily,
        'working'=>$working,
        'present'=>$present_days,
        'percent'=>$working?round(($present_days/$working)*100,1):0
    ];
}

/* ========= EXCEL EXPORT ========= */
if(isset($_GET['export']) && $_GET['export']=='excel'){
    header("Content-Type:text/csv");
    header("Content-Disposition:attachment; filename=cpto_attendance_$from_date-$to_date.csv");
    $out=fopen("php://output","w");

    $header=['HTNO','Name','Class','Team','College','CTPO','STPO'];
    foreach($dates as $d) $header[]=date('d',strtotime($d));
    $header[]='Working';
    $header[]='Present';
    $header[]='%';
    fputcsv($out,$header);

    foreach($rows as $r){
        $line=[
            $r['htno'],$r['name'],$r['classid'],$r['teamid'],
            $r['college'],$r['ctpo'],$r['stpo']
        ];
        foreach($dates as $d) $line[]=$r['daily'][$d];
        $line[]=$r['working'];
        $line[]=$r['present'];
        $line[]=$r['percent'];
        fputcsv($out,$line);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CPTO Attendance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f4f6fb;
    font-family:"Segoe UI", Arial, sans-serif;
}

h3{
    color:#1f2f86;
}

/* TABLE */
table{
    background:#fff;
    border-radius:8px;
}

th{
    background:#1f2f86;
    color:#fff;
    font-size:13px;
    font-weight:600;
}

td{
    font-size:13px;
    padding:6px 8px;
}
/* SIMPLE FONT COLOR LOGIC */
.present{
    color:#28a745 !important;   /* Green text */
    font-weight:700;
}

.absent{
    color:#dc3545 !important;   /* Red text */
    font-weight:700;
}

.not-marked{
    color:#6c757d !important;   /* Grey text */
}

/* BUTTONS */
.btn-primary{ background:#1f2f86; border:none; }
.btn-success{ background:#28a745; border:none; }
.btn-secondary{ background:#f26522; border:none; }

@media print{
    .btn, form{display:none}
    body{background:#fff}
}
</style>
</head>
<body>

<div class="container-fluid mt-4">

<h3 class="fw-bold">My Classes Attendance</h3>

<form method="get" class="row g-2 mb-3">
<div class="col-auto">
<input type="date" name="from_date" value="<?= $from_date ?>" class="form-control">
</div>
<div class="col-auto">
<input type="date" name="to_date" value="<?= $to_date ?>" class="form-control">
</div>
<div class="col-auto">
<button class="btn btn-primary">Apply</button>
</div>
<div class="col-auto">
<a href="?<?= $_SERVER['QUERY_STRING'] ?>&export=excel" class="btn btn-success">Excel</a>
</div>
<div class="col-auto">
<button type="button" onclick="window.print()" class="btn btn-secondary">PDF</button>
</div>
</form>

<div class="table-responsive">
<table class="table table-bordered text-center">

<thead>
<tr>
<th>#</th>
<th>HTNO</th>
<th>Name</th>
<th>Class</th>
<th>Team</th>
<th>College</th>
<th>CTPO</th>
<th>STPO</th>

<?php foreach($dates as $d): ?>
<th><?= date('d',strtotime($d)) ?></th>
<?php endforeach; ?>

<th>Work</th>
<th>Pres</th>
<th>%</th>
</tr>
</thead>

<tbody>
<?php if($rows): $i=1; foreach($rows as $r): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $r['htno'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= $r['classid'] ?></td>
<td><?= $r['teamid'] ?></td>
<td><?= $r['college'] ?></td>
<td><?= $r['ctpo'] ?></td>
<td><?= $r['stpo'] ?></td>

<?php foreach($dates as $d):
$val=$r['daily'][$d];
$cls=$val=='P'?'present':($val=='A'?'absent':'not-marked');
?>
<td class="<?= $cls ?>"><?= $val ?></td>
<?php endforeach; ?>

<td><b><?= $r['working'] ?></b></td>
<td><b><?= $r['present'] ?></b></td>
<td><b><?= $r['percent'] ?>%</b></td>
</tr>
<?php endforeach; endif; ?>
</tbody>

</table>
</div>

</div>
</body>
</html>