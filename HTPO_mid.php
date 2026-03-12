<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();
require_once "db.php";

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    die("ACCESS DENIED");
}

$username = $_SESSION['username'] ?? '';
if (!$username) die("SESSION ERROR");

/* ================= FETCH HTPO CONTEXT ================= */
$stmt = $conn->prepare("
    SELECT prog, year, college
    FROM USERS
    WHERE username=? AND role='HTPO'
    LIMIT 1
");
$stmt->bind_param("s",$username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !$user['prog'] || !$user['year'] || !$user['college']) {
    die("HTPO PROG / YEAR / COLLEGE NOT CONFIGURED");
}

$prog    = strtoupper($user['prog']);
$year    = $user['year'];
$college = $user['college'];               // SET value: KIET,KIEK,...

if (!in_array($prog,['DIP','B.TECH'])) {
    die("PROGRAM NOT ALLOWED");
}

/* ================= SEM RULES ================= */
$semesters = ($prog === 'DIP')
    ? ['1-1','2-1','2-2','3-1']
    : ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];

function allowed_mids($prog,$sem){
    if ($prog==='DIP' && $sem==='1-1') return [1,2,3];
    return [1,2];
}

/* ================= HANDLE COLLEGE SET ================= */
$college_list = array_map('trim', explode(',', $college));
$placeholders = implode(',', array_fill(0, count($college_list), '?'));

/* ================= FETCH BRANCHES (FIXED) ================= */
$sql = "
    SELECT DISTINCT branch
    FROM STUDENTS
    WHERE prog=? AND year=?
      AND college IN ($placeholders)
      AND branch<>'' AND branch IS NOT NULL
    ORDER BY branch
";

$stmt = $conn->prepare($sql);

$types  = "si" . str_repeat("s", count($college_list));
$params = array_merge([$prog, $year], $college_list);

$stmt->bind_param($types, ...$params);
$stmt->execute();

$res = $stmt->get_result();
$branches = [];
while ($row = $res->fetch_assoc()) {
    $branches[] = $row['branch'];
}
$stmt->close();

/* ================= CREATE MID ================= */
$msg="";
if(isset($_POST['create_mid'])){

    $selected_branches = $_POST['branch'] ?? [];
    $sem        = $_POST['sem'] ?? '';
    $subject    = trim($_POST['subject'] ?? '');
    $mid        = (int)($_POST['mid'] ?? 0);
    $exam_date  = $_POST['exam_date'] ?? '';
    $marks      = (int)($_POST['marks'] ?? 0);

    if(
        empty($selected_branches) || !$sem || !$subject || !$mid ||
        !$exam_date || !$marks ||
        !in_array($sem,$semesters) ||
        !in_array($mid,allowed_mids($prog,$sem))
    ){
        $msg="INVALID INPUT";
    } else {

        $created = 0;
        $skipped = 0;

        foreach ($selected_branches as $branch) {

            $chk=$conn->prepare("
                SELECT id FROM MIDS
                WHERE prog=? AND year=? AND college=?
                  AND sem=? AND branch=? AND subject=? AND mid=?
                LIMIT 1
            ");
            $chk->bind_param(
                "sissssi",
                $prog,$year,$college,$sem,$branch,$subject,$mid
            );
            $chk->execute();
            $chk->store_result();

            if($chk->num_rows>0){
                $skipped++;
                $chk->close();
                continue;
            }
            $chk->close();

            $ins=$conn->prepare("
                INSERT INTO MIDS
                (prog,year,college,sem,branch,subject,mid,exam_date,marks)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
           $ins=$conn->prepare("
    INSERT INTO MIDS
    (prog,year,college,sem,branch,subject,mid,exam_date,marks)
    VALUES (?,?,?,?,?,?,?,?,?)
");

$ins->bind_param(
    "sissssisi",
    $prog,
    $year,
    $college,
    $sem,
    $branch,
    $subject,
    $mid,
    $exam_date,
    $marks
);

if ($ins->execute()) $created++;
$ins->close();

        }

        $msg = "CREATED: $created | SKIPPED: $skipped";
    }
}

/* ================= FETCH CREATED MIDS ================= */
$stmt=$conn->prepare("
    SELECT sem,branch,subject,mid,exam_date,marks
    FROM MIDS
    WHERE prog=? AND year=? AND college=?
    ORDER BY exam_date DESC, sem, branch, mid
");
$stmt->bind_param("sis",$prog,$year,$college);
$stmt->execute();
$mids=$stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>HTPO | Create MID</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container" style="max-width:950px">

<h4>Create MID Exam</h4>

<?php if($msg): ?>
<div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post" class="mb-4">

<div class="row mb-3">
    <div class="col-md-4">
        <label>Program</label>
        <input class="form-control" value="<?= $prog ?>" readonly>
    </div>
    <div class="col-md-4">
        <label>Year</label>
        <input class="form-control" value="<?= $year ?>" readonly>
    </div>
    <div class="col-md-4">
        <label>College</label>
        <input class="form-control" value="<?= $college ?>" readonly>
    </div>
</div>

<div class="mb-3">
    <label>Branch (Multiple)</label>
    <select name="branch[]" class="form-select" multiple required size="6">
        <?php foreach($branches as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>">
                <?= htmlspecialchars($b) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small class="text-muted">Hold Ctrl / Cmd to select multiple</small>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label>Semester</label>
        <select name="sem" class="form-select" required>
            <option value="">Select</option>
            <?php foreach($semesters as $s): ?>
                <option><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label>MID</label>
        <select name="mid" class="form-select" required>
            <option value="">Select</option>
            <?php foreach(($prog==='DIP'?[1,2,3]:[1,2]) as $m): ?>
                <option value="<?= $m ?>">MID <?= $m ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="mb-3">
    <label>Subject</label>
    <input type="text" name="subject" class="form-control" required>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label>Exam Date</label>
        <input type="date" name="exam_date" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label>Maximum Marks</label>
        <input type="number" name="marks" class="form-control" required>
    </div>
</div>

<button class="btn btn-primary w-100" name="create_mid">
    Create MID for Selected Branches
</button>

</form>

<hr>

<h5>Already Created MIDs</h5>

<table class="table table-bordered table-sm">
<thead class="table-light">
<tr>
    <th>SEM</th>
    <th>Branch</th>
    <th>Subject</th>
    <th>MID</th>
    <th>Date</th>
    <th>Marks</th>
</tr>
</thead>
<tbody>
<?php if($mids->num_rows==0): ?>
<tr><td colspan="6" class="text-center">No MIDs Created</td></tr>
<?php else: while($r=$mids->fetch_assoc()): ?>
<tr>
    <td><?= $r['sem'] ?></td>
    <td><?= htmlspecialchars($r['branch']) ?></td>
    <td><?= htmlspecialchars($r['subject']) ?></td>
    <td>MID <?= $r['mid'] ?></td>
    <td><?= $r['exam_date'] ?></td>
    <td><?= $r['marks'] ?></td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table>

</div>
</body>
</html>
