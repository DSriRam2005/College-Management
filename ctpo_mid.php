<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

/* ============================================================
   AJAX UPDATE HANDLER (STORES FULL DATA)
============================================================ */
if (isset($_POST['update_marks'])) {

    $data = json_decode($_POST['update_marks'], true);
    $classid = $_SESSION['classid'];

    /* Fetch class meta ONCE */
    $metaQ = $conn->prepare("
        SELECT prog, year, branch, college
        FROM STUDENTS
        WHERE classid=?
        LIMIT 1
    ");
    $metaQ->bind_param("s", $classid);
    $metaQ->execute();
    $meta = $metaQ->get_result()->fetch_assoc();
    $metaQ->close();

    $prog    = strtoupper($meta['prog']);
    $year    = (string)$meta['year'];
    $branch  = $meta['branch'];
    $college = $meta['college'];

    foreach ($data as $row) {

        $roll  = trim($row['roll']);
        $sid   = (int)$row['sid'];     // MIDS.id
        $marks = strtoupper(trim($row['marks']));

        if ($marks === "") continue;
        if (!is_numeric($marks) && $marks !== "A") continue;

        /* Student name */
        $nQ = $conn->prepare("SELECT name FROM STUDENTS WHERE htno=? LIMIT 1");
        $nQ->bind_param("s", $roll);
        $nQ->execute();
        $name = $nQ->get_result()->fetch_assoc()['name'];
        $nQ->close();

        /* Subject name */
        $sQ = $conn->prepare("SELECT subject FROM MIDS WHERE id=? LIMIT 1");
        $sQ->bind_param("i", $sid);
        $sQ->execute();
        $subject = $sQ->get_result()->fetch_assoc()['subject'];
        $sQ->close();

        /* Exists? */
        $chk = $conn->prepare("
            SELECT id FROM MID_MARKS
            WHERE roll=? AND mids_id=?
            LIMIT 1
        ");
        $chk->bind_param("si", $roll, $sid);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;
        $chk->close();

        if ($exists) {
            $upd = $conn->prepare("
                UPDATE MID_MARKS
                SET marks_obtained=?, name=?, subject=?,
                    prog=?, year=?, classid=?, college=?
                WHERE roll=? AND mids_id=?
            ");
            $upd->bind_param(
                "ssssssssi",
                $marks, $name, $subject,
                $prog, $year, $classid, $college,
                $roll, $sid
            );
            $upd->execute();
            $upd->close();
        } else {
            $ins = $conn->prepare("
                INSERT INTO MID_MARKS
                (roll,name,subject,mids_id,marks_obtained,prog,year,classid,college)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $ins->bind_param(
                "sssisssss",
                $roll, $name, $subject,
                $sid, $marks,
                $prog, $year, $classid, $college
            );
            $ins->execute();
            $ins->close();
        }
    }

    echo "SUCCESS";
    exit;
}

/* ============================================================
   ACCESS CONTROL
============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: login.php");
    exit;
}

$classid = $_SESSION['classid'];

/* Class info */
$st = $conn->prepare("
    SELECT prog, year, branch, college
    FROM STUDENTS
    WHERE classid=?
    LIMIT 1
");
$st->bind_param("s", $classid);
$st->execute();
$class = $st->get_result()->fetch_assoc();

$class_prog    = strtoupper($class['prog']);
$class_year    = (int)$class['year'];
$class_branch  = $class['branch'];
$class_college = $class['college'];
?>

<!DOCTYPE html>
<html>
<head>
<title>CPTO – MID Marks Report</title>
<style>
body { font-family: Arial; background:#eef2f7; padding:20px; }
h2 { margin-bottom:20px; }
.filter-card { background:#fff; padding:20px; border-radius:10px; max-width:900px; margin:auto; }
.filter-row { display:flex; gap:20px; }
.filter-group { flex:1; }
select { width:100%; padding:10px; }
.view-btn { margin-top:20px; padding:12px; width:220px; background:#0066ff; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer; }

table { width:100%; margin-top:20px; border-collapse:collapse; background:#fff; }
th { background:#4c6ef5; color:#fff; padding:10px; }
td { padding:8px; border:1px solid #ddd; text-align:center; }
.rank { font-weight:bold; }

#editBtn,#saveBtn,#cancelBtn {
    padding:8px 14px; border:none; color:#fff;
    font-weight:bold; border-radius:5px; cursor:pointer;
}
#editBtn { background:#ff9800; }
#saveBtn { background:#4caf50; display:none; }
#cancelBtn { background:#f44336; display:none; }
</style>
</head>
<body>

<h2>MID Marks – Class <?= htmlspecialchars($classid) ?></h2>

<div class="filter-card">
<form method="GET">
<div class="filter-row">
<div class="filter-group">
<label>MID</label>
<select name="mid" required>
<option value="">Select</option>
<option value="1">MID-1</option>
<option value="2">MID-2</option>
<?php if ($class_prog!=="B.TECH") echo "<option value='3'>MID-3</option>"; ?>
</select>
</div>

<div class="filter-group">
<label>SEM</label>
<select name="sem" required>
<option value="">Select</option>
<?php
$sems = ($class_year==25)
    ? ['1-1','1-2']
    : ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];
foreach ($sems as $s) echo "<option value='$s'>$s</option>";
?>
</select>
</div>
</div>

<?php if (isset($_GET['mid'], $_GET['sem'])): ?>
<div class="filter-group" style="margin-top:20px;">
<label>Subject</label>
<select name="subject_filter">
<option value="ALL">ALL SUBJECTS</option>
<?php
$midX=(int)$_GET['mid']; $semX=$_GET['sem'];
$subQ=$conn->prepare("
    SELECT subject FROM MIDS
    WHERE mid=? AND sem=? AND prog=? AND year=?
      AND FIND_IN_SET(?,branch)
      AND FIND_IN_SET(?,college)
");
$subQ->bind_param("ississ",$midX,$semX,$class_prog,$class_year,$class_branch,$class_college);
$subQ->execute();
$r=$subQ->get_result();
while($s=$r->fetch_assoc())
    echo "<option value='{$s['subject']}'>{$s['subject']}</option>";
?>
</select>
</div>
<?php endif; ?>

<button class="view-btn">View</button>
</form>
</div>

<?php
if (isset($_GET['mid'],$_GET['sem'])) {

$mid=(int)$_GET['mid']; $sem=$_GET['sem'];
$chosen=$_GET['subject_filter'] ?? "ALL";

/* Subjects */
$stmt=$conn->prepare("
    SELECT id,subject,marks FROM MIDS
    WHERE mid=? AND sem=? AND prog=? AND year=?
      AND FIND_IN_SET(?,branch)
      AND FIND_IN_SET(?,college)
");
$stmt->bind_param("ississ",$mid,$sem,$class_prog,$class_year,$class_branch,$class_college);
$stmt->execute();
$subs=$stmt->get_result();

$subjects=[]; $max_total=0;
while($s=$subs->fetch_assoc()){
    $subjects[$s['id']]=$s['subject'];
    $max_total+=$s['marks'];
}
if(!$subjects){ echo "<p>No subjects found.</p>"; exit; }

/* Students */
$stuQ=$conn->prepare("
    SELECT htno,name,teamid
    FROM STUDENTS
    WHERE classid=? AND debarred=0
    ORDER BY CAST(htno AS UNSIGNED)
");
$stuQ->bind_param("s",$classid);
$stuQ->execute();
$stuRes=$stuQ->get_result();

$data=[];
while($st=$stuRes->fetch_assoc()){
    $total=0; $marksArr=[];
    foreach($subjects as $sid=>$sn){
        $mQ=$conn->prepare("SELECT marks_obtained FROM MID_MARKS WHERE roll=? AND mids_id=?");
        $mQ->bind_param("si",$st['htno'],$sid);
        $mQ->execute();
        $r=$mQ->get_result();
        $v=$r->num_rows?$r->fetch_assoc()['marks_obtained']:'-';
        $num=(strtoupper($v)=='A')?0:(is_numeric($v)?$v:0);
        $total+=$num;
        $marksArr[$sid]=$v;
    }
    $perc=$max_total?round(($total/$max_total)*100,2):0;
    $data[]=compact('st','total','perc','marksArr');
}

/* Rank */
usort($data,fn($a,$b)=>$b['perc']<=>$a['perc']);
?>

<div style="text-align:right;margin-top:15px;">
<button id="editBtn">Edit</button>
<button id="saveBtn">Save</button>
<button id="cancelBtn">Cancel</button>
</div>

<table>
<tr>
<th>Rank</th><th>Team</th><th>Roll</th><th>Name</th>
<?php foreach($subjects as $s) echo "<th>$s</th>"; ?>
<th>Total</th><th>%</th>
</tr>

<?php $r=1; foreach($data as $row):
$bg=$row['perc']>=75?'#b6fcb6':($row['perc']>=50?'#ffe6a1':'#ffb3b3');
?>
<tr>
<td><?= $r++ ?></td>
<td><?= $row['st']['teamid'] ?></td>
<td><?= $row['st']['htno'] ?></td>
<td><?= $row['st']['name'] ?></td>

<?php foreach($subjects as $sid=>$sn):
$v=$row['marksArr'][$sid]; ?>
<td class="cell" data-roll="<?= $row['st']['htno'] ?>" data-sid="<?= $sid ?>"
<?= strtoupper($v)=='A'?"style='background:#ff4d4d;color:#fff;font-weight:bold;'":"" ?>>
<?= $v ?></td>
<?php endforeach; ?>

<td><b><?= $row['total'] ?></b></td>
<td style="background:<?= $bg ?>;font-weight:bold;"><?= $row['perc'] ?>%</td>
</tr>
<?php endforeach; ?>
</table>

<?php } ?>

<script>
editBtn.onclick=()=>{
editBtn.style.display="none";
saveBtn.style.display="inline";
cancelBtn.style.display="inline";
document.querySelectorAll(".cell").forEach(c=>{
let v=c.innerText.trim();
c.innerHTML=`<input value="${v}" style="width:55px">`;
});
};
cancelBtn.onclick=()=>location.reload();
saveBtn.onclick=()=>{
let d=[];
document.querySelectorAll(".cell").forEach(c=>{
d.push({roll:c.dataset.roll,sid:c.dataset.sid,marks:c.querySelector("input").value.trim()});
});
let fd=new FormData();
fd.append("update_marks",JSON.stringify(d));
fetch("",{method:"POST",body:fd}).then(()=>location.reload());
};
</script>

</body>
</html>
