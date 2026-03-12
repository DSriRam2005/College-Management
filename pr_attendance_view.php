<?php
include 'db.php';

/* ================= FILTERS ================= */
$selected_year    = $_GET['year'] ?? '25';
$selected_prog    = $_GET['prog'] ?? 'B.TECH';
$from_date        = $_GET['from_date'] ?? date('Y-m-01');
$to_date          = $_GET['to_date'] ?? date('Y-m-d');
$selected_college = $_GET['college'] ?? '';
$selected_classid = $_GET['classid'] ?? '';
$selected_status  = $_GET['status'] ?? '';
$selected_stpo    = $_GET['stpo'] ?? '';
$selected_ctpo    = $_GET['ctpo'] ?? '';

/* ================= DATE LIST ================= */
$dates = [];
$start = new DateTime($from_date);
$end   = new DateTime($to_date);
while ($start <= $end) {
    $dates[] = $start->format('Y-m-d');
    $start->modify('+1 day');
}

/* ================= YEARS & PROGRAMS ================= */
$years = $conn->query("SELECT DISTINCT year FROM STUDENTS WHERE debarred=0 ORDER BY year DESC");
$progs = $conn->query("SELECT DISTINCT prog FROM STUDENTS WHERE debarred=0 ORDER BY prog");

/* ================= STPO LIST ================= */
$stpo_rs = $conn->query("
    SELECT DISTINCT stpo
    FROM STUDENTS
    WHERE debarred=0 AND stpo IS NOT NULL AND stpo!=''
    ORDER BY stpo
");

/* ================= CTPO LIST ================= */
$ctpo_rs = $conn->query("
    SELECT DISTINCT EMP_ID
    FROM USERS
    WHERE role='CPTO' AND EMP_ID IS NOT NULL AND classid IS NOT NULL
    ORDER BY EMP_ID
");

/* ================= COLLEGES ================= */
$college_sql = "SELECT DISTINCT college FROM STUDENTS WHERE debarred=0";
$cp=[]; $ct="";

if ($selected_year !== 'ALL') { $college_sql.=" AND year=?"; $cp[]=$selected_year; $ct.="i"; }
if ($selected_prog !== 'ALL') { $college_sql.=" AND prog=?"; $cp[]=$selected_prog; $ct.="s"; }

$college_sql .= " ORDER BY college";
$stmt = $conn->prepare($college_sql);
if ($cp) $stmt->bind_param($ct, ...$cp);
$stmt->execute();
$colleges = $stmt->get_result();

/* ================= CLASS IDS ================= */
$class_sql = "SELECT DISTINCT classid FROM STUDENTS WHERE debarred=0";
$cp=[]; $ct="";

if ($selected_year !== 'ALL') { $class_sql.=" AND year=?"; $cp[]=$selected_year; $ct.="i"; }
if ($selected_prog !== 'ALL') { $class_sql.=" AND prog=?"; $cp[]=$selected_prog; $ct.="s"; }

$class_sql .= " ORDER BY classid";
$stmt = $conn->prepare($class_sql);
if ($cp) $stmt->bind_param($ct, ...$cp);
$stmt->execute();
$classids = $stmt->get_result();

/* ================= MAIN STUDENT QUERY ================= */
$query = "
SELECT
    s.htno,
    COALESCE(
        NULLIF(CONCAT(TRIM(s.firstname),' ',TRIM(s.middlename),' ',TRIM(s.lastname)),'  '),
        s.name,'Unknown'
    ) AS student_name,
    s.classid,
    s.teamid,
    s.college,
    s.gen,
    s.stpo,

    (
        SELECT u.EMP_ID
        FROM USERS u
        WHERE u.role='CPTO'
          AND u.classid = s.classid
        LIMIT 1
    ) AS ctpo_emp_id

FROM STUDENTS s
WHERE s.debarred = 0
";

$params = [];
$types  = "";

/* FILTERS */
if ($selected_year !== 'ALL') { $query.=" AND s.year=?"; $params[]=$selected_year; $types.="i"; }
if ($selected_prog !== 'ALL') { $query.=" AND s.prog=?"; $params[]=$selected_prog; $types.="s"; }
if ($selected_college !== '') { $query.=" AND s.college=?"; $params[]=$selected_college; $types.="s"; }
if ($selected_classid !== '') { $query.=" AND s.classid=?"; $params[]=$selected_classid; $types.="s"; }
if ($selected_stpo !== '') { $query.=" AND s.stpo=?"; $params[]=$selected_stpo; $types.="s"; }

if ($selected_ctpo !== '') {
    $query .= "
    AND EXISTS (
        SELECT 1 FROM USERS u
        WHERE u.role='CPTO'
          AND u.EMP_ID=?
          AND u.classid=s.classid
    )";
    $params[] = $selected_ctpo;
    $types .= "s";
}

$query .= " ORDER BY s.year, s.prog, s.classid, s.teamid, s.htno";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ================= ATTENDANCE MAP ================= */
$att_map = [];
$att_stmt = $conn->prepare("
    SELECT htno, att_date, status
    FROM attendance
    WHERE att_date BETWEEN ? AND ?
");
$att_stmt->bind_param("ss", $from_date, $to_date);
$att_stmt->execute();
$res = $att_stmt->get_result();

while ($r = $res->fetch_assoc()) {
    $att_map[$r['htno']][$r['att_date']] = $r['status'];
}

/* ================= PROCESS ================= */
$total=$present=$absent=$not_marked=0;
$rows=[];

while ($r=$result->fetch_assoc()) {

    $ht = $r['htno'];
    $working = 0;
    $present_days = 0;
    $daily = [];

    foreach ($dates as $d) {
        if (isset($att_map[$ht][$d])) {
            $working++;
            if ($att_map[$ht][$d]=='Present') {
                $present_days++;
                $daily[$d]='P';
            } else {
                $daily[$d]='A';
            }
        } else {
            $daily[$d]='-';
        }
    }

    if ($selected_status=='Present' && $present_days==0) continue;
    if ($selected_status=='Absent' && $present_days==$working) continue;
    if ($selected_status=='Not Marked' && $working>0) continue;

    $r['daily']=$daily;
    $r['working_days']=$working;
    $r['present_days']=$present_days;
    $r['percent']=$working?round(($present_days/$working)*100,1):0;

    $rows[]=$r;
    $total++;
    $present+=$present_days;
    $absent+=($working-$present_days);
    $not_marked+=count($dates)-$working;
}

/* ================= CSV EXPORT ================= */
if (isset($_GET['export']) && $_GET['export']=='excel') {
    header("Content-Type:text/csv");
    header("Content-Disposition:attachment; filename=attendance_$from_date-$to_date.csv");
    $out=fopen("php://output","w");

    $header=['HTNO','Name','Class','College','Gender','CTPO','STPO'];
    foreach ($dates as $d) $header[]=date('d',strtotime($d));
    $header[]='Working';
    $header[]='Present';
    $header[]='%';

    fputcsv($out,$header);

    foreach ($rows as $r) {
        $line=[
            $r['htno'],$r['student_name'],$r['classid'],
            $r['college'],$r['gen'],$r['ctpo_emp_id'],$r['stpo']
        ];
        foreach ($dates as $d) $line[]=$r['daily'][$d];
        $line[]=$r['working_days'];
        $line[]=$r['present_days'];
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
<title>Attendance Report</title>
<style>
body{font-family:Arial;margin:25px}
table{border-collapse:collapse;width:100%;margin-top:15px}
th,td{border:1px solid #333;padding:5px;text-align:center;font-size:12px}
th{background:#eee}
.present{background:#c8e6c9}
.absent{background:#ffcdd2}
.not-marked{background:#e0e0e0}
select,input,button{padding:6px;margin-right:6px}
.summary{border:1px solid #444;background:#f9f9f9;padding:10px;margin:15px 0}
@media print{form,button{display:none}}
</style>
<script>
let ar=false,ref;
function toggleRefresh(){
    ar=!ar;
    if(ar){ref=setInterval(()=>location.reload(),30000);alert("Auto refresh ON");}
    else{clearInterval(ref);alert("Auto refresh OFF");}
}
</script>
</head>

<body>

<h2>Student Attendance Report</h2>

<form method="get">
Year:
<select name="year"><option value="ALL">ALL</option>
<?php while($y=$years->fetch_assoc()): ?>
<option value="<?= $y['year'] ?>" <?= $selected_year==$y['year']?'selected':'' ?>><?= $y['year'] ?></option>
<?php endwhile; ?>
</select>

Program:
<select name="prog"><option value="ALL">ALL</option>
<?php while($p=$progs->fetch_assoc()): ?>
<option value="<?= $p['prog'] ?>" <?= $selected_prog==$p['prog']?'selected':'' ?>><?= $p['prog'] ?></option>
<?php endwhile; ?>
</select>

From: <input type="date" name="from_date" value="<?= $from_date ?>">
To: <input type="date" name="to_date" value="<?= $to_date ?>">

College:
<select name="college"><option value="">ALL</option>
<?php while($c=$colleges->fetch_assoc()): ?>
<option value="<?= $c['college'] ?>" <?= $selected_college==$c['college']?'selected':'' ?>><?= $c['college'] ?></option>
<?php endwhile; ?>
</select>

Class:
<select name="classid"><option value="">ALL</option>
<?php while($c=$classids->fetch_assoc()): ?>
<option value="<?= $c['classid'] ?>" <?= $selected_classid==$c['classid']?'selected':'' ?>><?= $c['classid'] ?></option>
<?php endwhile; ?>
</select>

CTPO:
<select name="ctpo"><option value="">ALL</option>
<?php while($c=$ctpo_rs->fetch_assoc()): ?>
<option value="<?= $c['EMP_ID'] ?>" <?= $selected_ctpo==$c['EMP_ID']?'selected':'' ?>><?= $c['EMP_ID'] ?></option>
<?php endwhile; ?>
</select>

STPO:
<select name="stpo"><option value="">ALL</option>
<?php while($s=$stpo_rs->fetch_assoc()): ?>
<option value="<?= $s['stpo'] ?>" <?= $selected_stpo==$s['stpo']?'selected':'' ?>><?= $s['stpo'] ?></option>
<?php endwhile; ?>
</select>

Status:
<select name="status">
<option value="">ALL</option>
<option value="Present" <?= $selected_status=='Present'?'selected':'' ?>>Present</option>
<option value="Absent" <?= $selected_status=='Absent'?'selected':'' ?>>Absent</option>
<option value="Not Marked" <?= $selected_status=='Not Marked'?'selected':'' ?>>Not Marked</option>
</select>

<input type="submit" value="Filter">
<button type="button" onclick="window.print()">PDF</button>
<a href="?<?= $_SERVER['QUERY_STRING'] ?>&export=excel"><button type="button">Excel</button></a>
<button type="button" onclick="toggleRefresh()">Auto Refresh</button>
</form>

<div class="summary">
<b>Students:</b> <?= $total ?> |
<b>Total Present:</b> <?= $present ?> |
<b>Total Absent:</b> <?= $absent ?> |
<b>Total Not Marked:</b> <?= $not_marked ?>
</div>

<table>
<tr>
<th>#</th>
<th>HTNO</th>
<th>Name</th>
<th>Class</th>
<th>Team</th>
<th>College</th>
<th>Gender</th>
<th>CTPO</th>
<th>STPO</th>

<?php foreach ($dates as $d): ?>
<th><?= date('d',strtotime($d)) ?></th>
<?php endforeach; ?>

<th>Working</th>
<th>Present</th>
<th>%</th>
</tr>

<?php if($rows): $i=1; foreach($rows as $r): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $r['htno'] ?></td>
<td><?= htmlspecialchars($r['student_name']) ?></td>
<td><?= $r['classid'] ?></td>
<td><?= $r['teamid'] ?></td>
<td><?= $r['college'] ?></td>
<td><?= $r['gen'] ?></td>
<td><?= $r['ctpo_emp_id'] ?></td>
<td><?= $r['stpo'] ?></td>

<?php foreach ($dates as $d): 
$val=$r['daily'][$d];
$cls=$val=='P'?'present':($val=='A'?'absent':'not-marked');
?>
<td class="<?= $cls ?>"><?= $val ?></td>
<?php endforeach; ?>

<td><b><?= $r['working_days'] ?></b></td>
<td><b><?= $r['present_days'] ?></b></td>
<td><b><?= $r['percent'] ?>%</b></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="20">No records found</td></tr>
<?php endif; ?>
</table>

</body>
</html>