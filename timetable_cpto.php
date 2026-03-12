<?php
// ENABLE ERRORS
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

// ALLOW ONLY CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    die("ACCESS DENIED");
}

$username = $_SESSION['username'];

// -------------------------------------------------------------
// FETCH CPTO DETAILS (PROG, YEAR, COLLEGE, CLASSIDS)
// -------------------------------------------------------------
$userQ = $conn->query("
    SELECT prog, year, college, classid 
    FROM USERS 
    WHERE username='$username'
    LIMIT 1
");

$userData = $userQ->fetch_assoc();

$cpto_prog     = $userData['prog'];
$cpto_year     = $userData['year'];
$cpto_college  = $userData['college'];
$cpto_classids = $userData['classid'];  // Could be: CSE-A or CSE-A,CSE-B

$classList = array_map('trim', explode(",", $cpto_classids));


// -------------------------------------------------------------
// AJAX LOAD SUBJECTS
// -------------------------------------------------------------
if (isset($_GET['load_sub']) && isset($_GET['classid'])) {
    $cid = mysqli_real_escape_string($conn, $_GET['classid']);
    $q = $conn->query("
        SELECT DISTINCT subject_name
        FROM subjects
        WHERE classid='$cid'
    ");
    while ($r = $q->fetch_assoc()) {
        echo "<option value='".$r['subject_name']."'>".$r['subject_name']."</option>";
    }
    exit();
}


// -------------------------------------------------------------
// AJAX LOAD FACULTY
// -------------------------------------------------------------
if (isset($_GET['load_faculty']) && isset($_GET['classid']) && isset($_GET['subject'])) {
    $cid     = mysqli_real_escape_string($conn, $_GET['classid']);
    $subject = mysqli_real_escape_string($conn, $_GET['subject']);

    $q = $conn->query("
        SELECT DISTINCT faculty_name
        FROM subjects
        WHERE classid='$cid'
        AND subject_name='$subject'
    ");
    while ($r = $q->fetch_assoc()) {
        echo "<option value='".$r['faculty_name']."'>".$r['faculty_name']."</option>";
    }
    exit();
}


// -------------------------------------------------------------
// SAVE TIMETABLE ENTRY (WITH CLASS+FACULTY SLOT CHECK)
// -------------------------------------------------------------
$message = "";

if (isset($_POST['save'])) {

    $classid = mysqli_real_escape_string($conn, $_POST['classid']);
    $day     = mysqli_real_escape_string($conn, $_POST['day']);
    $period  = intval($_POST['period_no']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $faculty = mysqli_real_escape_string($conn, $_POST['faculty_name']);

    // ---- CLASS SLOT CHECK ----
    $chkClass = $conn->query("
        SELECT id FROM timetable
        WHERE classid='$classid'
        AND day='$day'
        AND period_no='$period'
    ");

    if ($chkClass->num_rows > 0) {
        $message = "<div class='msg msg-error'>
                        This class already has a subject for Day: $day, Period: $period.
                    </div>";
    } else {

        // ---- FACULTY SLOT CHECK ----
        $chkFaculty = $conn->query("
            SELECT id FROM timetable
            WHERE faculty_name='$faculty'
            AND day='$day'
            AND period_no='$period'
        ");

        if ($chkFaculty->num_rows > 0) {
            $message = "<div class='msg msg-error'>
                            Faculty is already assigned to another class at Day: $day, Period: $period.
                        </div>";
        } else {

            // ---- FINAL INSERT ----
            $ins = $conn->query("
                INSERT INTO timetable (classid, prog, year, day, period_no, subject_name, faculty_name)
                VALUES ('$classid', '$cpto_prog', '$cpto_year', '$day', '$period', '$subject', '$faculty')
            ");

            if ($ins) {
                $message = "<div class='msg msg-success'>Timetable saved successfully.</div>";
            } else {
                $message = "<div class='msg msg-error'>Error: ".$conn->error."</div>";
            }
        }
    }
}


// -------------------------------------------------------------
// LOAD TIMETABLE LIST
// -------------------------------------------------------------
$classIdsForQuery = "'" . implode("','", $classList) . "'";

$ttQ = $conn->query("
    SELECT *
    FROM timetable
    WHERE classid IN ($classIdsForQuery)
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
             period_no
");
?>
<!DOCTYPE html>
<html>
<head>
<title>CPTO Timetable Entry</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { margin: 0; font-family: Arial; background: #f4f6f9; }
    .page-wrapper { max-width: 1000px; margin: 20px auto; padding: 0 10px; }
    .page-header { background:#2563eb;color:#fff;padding:12px;border-radius:8px;margin-bottom:15px; }
    .page-header h2 { margin:0;font-size:20px; }
    .msg { padding:10px;border-radius:6px;margin:10px 0;font-size:14px; }
    .msg-success { background:#dcfce7;color:#166534;border:1px solid #22c55e; }
    .msg-error { background:#fee2e2;color:#b91c1c;border:1px solid #f97373; }
    .card { background:#fff;border-radius:8px;padding:14px;margin-top:8px;
            box-shadow:0 1px 4px rgba(0,0,0,0.08); }
    label { display:block;margin-top:10px;font-size:13px;font-weight:600;color:#374151; }
    select,input[type=text] { width:100%;padding:7px;border:1px solid #ccc;border-radius:4px; }
    input[readonly] { background:#f9fafb; }
    .btn { width:100%;padding:10px;background:#2563eb;color:white;border:none;
           border-radius:4px;font-size:15px;margin-top:15px; }
    .tick-group { display:flex;flex-wrap:wrap;gap:8px;margin-top:5px; }
    .tick-option { display:flex;align-items:center;gap:6px;padding:6px 10px;background:#f3f4f6;
                   border-radius:6px;border:1px solid #d1d5db;cursor:pointer; }
    .table-wrapper { margin-top:10px;background:#fff;border-radius:8px;
                     box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:10px;overflow-x:auto; }
    table { width:100%;border-collapse:collapse;min-width:600px; }
    th { background:#2563eb;color:#fff;padding:8px;text-align:left; }
    td { padding:8px;border-bottom:1px solid #eee; }
</style>

<script>
function loadSubjects(cid, targetId) {
    fetch("timetable_cpto.php?load_sub=1&classid=" + cid)
    .then(res => res.text())
    .then(data => { document.getElementById(targetId).innerHTML = data; });
}

function loadFaculty(cid, subject, targetId) {
    fetch("timetable_cpto.php?load_faculty=1&classid=" + cid + "&subject=" + subject)
    .then(res => res.text())
    .then(data => { document.getElementById(targetId).innerHTML = data; });
}
</script>

</head>
<body>

<div class="page-wrapper">

    <div class="page-header">
        <h2>CPTO – Timetable Entry (Year: <?= htmlspecialchars($cpto_year) ?>)</h2>
    </div>

    <?= $message ?>

    <?php foreach ($classList as $index => $cid): ?>

    <h3>Class: <?= htmlspecialchars($cid) ?></h3>

    <form method="POST" class="card">
        <input type="hidden" name="classid" value="<?= htmlspecialchars($cid) ?>">

        <label>Program</label>
        <input type="text" value="<?= htmlspecialchars($cpto_prog) ?>" readonly>

        <label>Year</label>
        <input type="text" value="<?= htmlspecialchars($cpto_year) ?>" readonly>

        <label>Class ID</label>
        <input type="text" value="<?= htmlspecialchars($cid) ?>" readonly>

        <label>Day</label>
        <div class="tick-group">
            <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"] as $d): ?>
            <label class="tick-option">
                <input type="radio" name="day" value="<?= $d ?>" required>
                <span><?= $d ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <label>Period No</label>
        <div class="tick-group">
            <?php for($i=1;$i<=4;$i++): ?>
            <label class="tick-option">
                <input type="radio" name="period_no" value="<?= $i ?>" required>
                <span><?= $i ?></span>
            </label>
            <?php endfor; ?>
        </div>

        <label>Subject</label>
        <select name="subject_name"
                id="sub_<?= $index ?>"
                onchange="loadFaculty('<?= $cid ?>', this.value, 'fac_<?= $index ?>')"
                required>
            <option value="">Select</option>
            <script>loadSubjects("<?= $cid ?>","sub_<?= $index ?>")</script>
        </select>

        <label>Faculty</label>
        <select name="faculty_name" id="fac_<?= $index ?>" required>
            <option value="">Select Subject First</option>
        </select>

        <button name="save" class="btn">Save Entry</button>
    </form>

    <hr>

    <?php endforeach; ?>


    <h3>Complete Timetable</h3>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>ID</th>
                <th>Class</th>
                <th>Day</th>
                <th>Period</th>
                <th>Subject</th>
                <th>Faculty</th>
            </tr>

            <?php while($row = $ttQ->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['classid'] ?></td>
                <td><?= $row['day'] ?></td>
                <td><?= $row['period_no'] ?></td>
                <td><?= $row['subject_name'] ?></td>
                <td><?= $row['faculty_name'] ?></td>
            </tr>
            <?php endwhile; ?>

        </table>
    </div>

</div>

</body>
</html>
