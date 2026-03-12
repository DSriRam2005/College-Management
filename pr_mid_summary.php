<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: login.php");
    exit;
}

/* ================= FILTERS ================= */
$year = isset($_GET['year']) ? (int)$_GET['year'] : 25;
$prog = isset($_GET['prog']) ? strtoupper($_GET['prog']) : "B.TECH";
$mid  = isset($_GET['mid'])  ? (int)$_GET['mid']  : 1;
$sem  = isset($_GET['sem'])  ? $_GET['sem']       : "1-1";
?>

<!DOCTYPE html>
<html>
<head>
<title>PR – MID Summary Report</title>
<style>
body { font-family: Arial; background:#eef2f7; padding:20px; }
h2 { margin-bottom:20px; font-weight:bold; }
.box { background:white; padding:20px; border-radius:12px; margin-bottom:25px; }
.form-row { display:flex; gap:20px; margin-bottom:15px; }
.form-item { flex:1; }
label { font-weight:bold; margin-bottom:5px; display:block; }
select,button { width:100%; padding:10px; border-radius:6px; border:1px solid #bbb; }
button { background:#0066ff; color:white; font-weight:bold; cursor:pointer; }
@media(max-width:768px){ .form-row{flex-direction:column;} }
table { width:100%; border-collapse:collapse; background:white; }
th { background:#4c6ef5; color:white; padding:10px; }
td { padding:10px; border:1px solid #ddd; text-align:center; }
.good{background:#b6fcb6;font-weight:bold;}
.avg{background:#ffe6a1;font-weight:bold;}
.bad{background:#ffb3b3;font-weight:bold;}
a.class-link{color:#0066ff;font-weight:bold;text-decoration:none;}
</style>
</head>

<body>

<h2>PR MID Summary – <?= htmlspecialchars($prog) ?> | YEAR <?= $year ?></h2>

<div class="box">
<form method="GET">
<div class="form-row">
<div class="form-item">
<label>Year</label>
<select name="year">
<?php for($y=20;$y<=30;$y++): ?>
<option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
<?php endfor; ?>
</select>
</div>
<div class="form-item">
<label>Program</label>
<select name="prog">
<?php foreach(["B.TECH","DIP","MBA","MCA","M.TECH"] as $p): ?>
<option value="<?= $p ?>" <?= $prog==$p?'selected':'' ?>><?= $p ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div class="form-row">
<div class="form-item">
<label>MID</label>
<select name="mid">
<option value="1" <?= $mid==1?'selected':'' ?>>MID-1</option>
<option value="2" <?= $mid==2?'selected':'' ?>>MID-2</option>
<option value="3" <?= $mid==3?'selected':'' ?>>MID-3</option>
</select>
</div>
<div class="form-item">
<label>SEM</label>
<select name="sem">
<?php foreach(['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'] as $s): ?>
<option value="<?= $s ?>" <?= $sem==$s?'selected':'' ?>><?= $s ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<button>View Report</button>
</form>
</div>

<?php
/* ================= CLASS LIST ================= */
$stmt = $conn->prepare("
    SELECT DISTINCT classid
    FROM STUDENTS
    WHERE prog=? AND year=?
    ORDER BY classid
");
$stmt->bind_param("si",$prog,$year);
$stmt->execute();
$classRes = $stmt->get_result();
$stmt->close();

echo "<table>
<tr>
<th>Class ID</th>
<th>Total Students</th>
<th>Attended</th>
<th>Attendance %</th>
<th>Class Avg %</th>
</tr>";

while($c = $classRes->fetch_assoc()) {

    $classid = $c['classid'];

    /* TOTAL STUDENTS */
    $q=$conn->prepare("
        SELECT COUNT(*) total
        FROM STUDENTS
        WHERE classid=? AND debarred=0
    ");
    $q->bind_param("s",$classid);
    $q->execute();
    $total = (int)$q->get_result()->fetch_assoc()['total'];
    $q->close();

    /* ================= CLASS-SPECIFIC MAX TOTAL ================= */
    $q=$conn->prepare("
        SELECT SUM(m.marks) AS max_total
        FROM MIDS m
        WHERE m.mid=? AND m.sem=? AND m.prog=? AND m.year=?
          AND EXISTS (
              SELECT 1 FROM STUDENTS s
              WHERE s.classid=?
                AND FIND_IN_SET(s.branch,m.branch)
                AND FIND_IN_SET(s.college,m.college)
          )
    ");
    $q->bind_param("issis",$mid,$sem,$prog,$year,$classid);
    $q->execute();
    $max_total = (int)$q->get_result()->fetch_assoc()['max_total'];
    $q->close();

    if($max_total<=0){
        echo "<tr><td>$classid</td><td>$total</td><td>0</td><td>0%</td><td class='bad'>0%</td></tr>";
        continue;
    }

    /* ATTENDED */
    $q=$conn->prepare("
        SELECT COUNT(DISTINCT mm.roll) attended
        FROM MID_MARKS mm
        WHERE mm.classid=?
          AND mm.mids_id IN (
              SELECT id FROM MIDS
              WHERE mid=? AND sem=? AND prog=? AND year=?
          )
    ");
    $q->bind_param("sissi",$classid,$mid,$sem,$prog,$year);
    $q->execute();
    $attended = (int)$q->get_result()->fetch_assoc()['attended'];
    $q->close();

    $att_per = $total ? round(($attended/$total)*100,2) : 0;

    /* CLASS AVERAGE */
    $q=$conn->prepare("
        SELECT
        SUM(CASE WHEN marks_obtained='A' THEN 0 ELSE marks_obtained END) total
        FROM MID_MARKS
        WHERE classid=?
          AND mids_id IN (
            SELECT id FROM MIDS
            WHERE mid=? AND sem=? AND prog=? AND year=?
          )
        GROUP BY roll
    ");
    $q->bind_param("sissi",$classid,$mid,$sem,$prog,$year);
    $q->execute();
    $res=$q->get_result();
    $q->close();

    $sum=0;$cnt=0;
    while($r=$res->fetch_assoc()){
        $sum+=($r['total']/$max_total)*100;
        $cnt++;
    }
    $avg = $cnt?round($sum/$cnt,2):0;
    $color = $avg>=75?'good':($avg>=50?'avg':'bad');

    echo "<tr>
        <td><a class='class-link' href='ctpo_teamwise_mid.php?classid=$classid&mid=$mid&sem=$sem'>$classid</a></td>
        <td>$total</td>
        <td>$attended</td>
        <td>$att_per%</td>
        <td class='$color'>$avg%</td>
    </tr>";
}

echo "</table>";
?>

</body>
</html>
