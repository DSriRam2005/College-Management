<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

/* ============================================================
   ACCESS CONTROL
============================================================ */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['CPTO','HTPO','PR'])) {
    header("Location: login.php");
    exit;
}

$classid = $_GET['classid'] ?? $_SESSION['classid'];

/* ============================================================
   FETCH CLASS DETAILS
============================================================ */
$st = $conn->prepare("
    SELECT prog, year, branch, college 
    FROM STUDENTS 
    WHERE classid=? 
    LIMIT 1
");
$st->bind_param("s", $classid);
$st->execute();
$info = $st->get_result()->fetch_assoc();

if (!$info) {
    die("Invalid Class ID");
}

$class_prog    = strtoupper($info['prog']);   // B.TECH
$class_year    = (int)$info['year'];           // 25
$class_branch  = $info['branch'];              // CSM
$class_college = $info['college'];             // KIEW / KIET

/* DEFAULT MID + SEM */
$mid = isset($_GET['mid']) ? (int)$_GET['mid'] : 1;
$sem = $_GET['sem'] ?? '1-1';
?>

<!DOCTYPE html>
<html>
<head>
<title>Team Wise MID Marks Report</title>
<style>
body { font-family: Arial; background:#eef2f7; padding:20px; }
.filter-box { background:#fff; padding:15px; border-radius:8px; max-width:600px; }
.team-header { background:#4c6ef5; color:#fff; padding:10px; margin-top:25px; font-size:18px; }
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px; }
th,td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#e7ebff; }
.absent { background:#ff4d4d; color:#fff; font-weight:bold; }
.avg-box { background:#fff3cd; padding:10px; margin-top:8px; border-radius:6px; }
</style>
</head>
<body>

<h2>TEAM-WISE MID Marks Report (Class: <?= htmlspecialchars($classid) ?>)</h2>

<form method="GET" class="filter-box">
    <label>MID</label>
    <select name="mid">
        <option value="1" <?= $mid==1?'selected':'' ?>>MID-1</option>
        <option value="2" <?= $mid==2?'selected':'' ?>>MID-2</option>
        <?php if ($class_prog !== 'B.TECH') { ?>
            <option value="3" <?= $mid==3?'selected':'' ?>>MID-3</option>
        <?php } ?>
    </select>

    <br><br>

    <label>SEM</label>
    <select name="sem">
        <?php
        $sems = ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];
        foreach ($sems as $s) {
            echo "<option value='$s' ".($sem==$s?'selected':'').">$s</option>";
        }
        ?>
    </select>

    <input type="hidden" name="classid" value="<?= htmlspecialchars($classid) ?>">
    <br><br>
    <button>View Report</button>
</form>

<?php
/* ============================================================
   REPORT GENERATION
============================================================ */
if ($mid && $sem) {

    /* ===== SUBJECTS (CSV-SAFE QUERY) ===== */
    $subQ = $conn->prepare("
        SELECT id, subject, marks
        FROM MIDS
        WHERE mid = ?
          AND sem = ?
          AND prog = ?
          AND year = ?
          AND FIND_IN_SET(?, branch)
          AND FIND_IN_SET(?, college)
        ORDER BY id
    ");

    $subQ->bind_param(
        "ississ",
        $mid,
        $sem,
        $class_prog,
        $class_year,
        $class_branch,
        $class_college
    );
    $subQ->execute();
    $subRes = $subQ->get_result();

    $subjects = [];
    $max_total = 0;

    while ($row = $subRes->fetch_assoc()) {
        $subjects[$row['id']] = $row['subject'];
        $max_total += (int)$row['marks'];
    }

    if (empty($subjects)) {
        echo "<b>No subjects configured for this MID.</b>";
        exit;
    }

    /* ===== TEAMS ===== */
    $teamQ = $conn->prepare("
        SELECT DISTINCT teamid
        FROM STUDENTS
        WHERE classid=? AND debarred=0 AND teamid!=''
        ORDER BY teamid
    ");
    $teamQ->bind_param("s", $classid);
    $teamQ->execute();
    $teams = $teamQ->get_result();

    while ($t = $teams->fetch_assoc()) {

        $team = $t['teamid'];
        echo "<div class='team-header'>TEAM $team</div>";

        $stuQ = $conn->prepare("
            SELECT htno, name
            FROM STUDENTS
            WHERE classid=? AND teamid=? AND debarred=0
            ORDER BY htno
        ");
        $stuQ->bind_param("ss", $classid, $team);
        $stuQ->execute();
        $students = $stuQ->get_result();

        echo "<table><tr><th>Roll</th><th>Name</th>";
        foreach ($subjects as $s) echo "<th>$s</th>";
        echo "<th>Total</th><th>%</th></tr>";

        $sum = 0;
        $cnt = 0;

        while ($stu = $students->fetch_assoc()) {

            $total = 0;
            echo "<tr><td>{$stu['htno']}</td><td>{$stu['name']}</td>";

            foreach ($subjects as $sid => $sname) {

                /* ===== MARKS (NO mid COLUMN — CORRECT) ===== */
                $m = $conn->prepare("
                    SELECT marks_obtained
                    FROM MID_MARKS
                    WHERE roll=? AND mids_id=?
                    LIMIT 1
                ");
                $m->bind_param("si", $stu['htno'], $sid);
                $m->execute();
                $res = $m->get_result();

                $val = $res->num_rows ? $res->fetch_assoc()['marks_obtained'] : '-';

                if (strtoupper($val) === 'A') {
                    echo "<td class='absent'>A</td>";
                } else {
                    echo "<td>$val</td>";
                    if (is_numeric($val)) $total += (int)$val;
                }
            }

            $perc = round(($total / $max_total) * 100, 2);
            echo "<td><b>$total</b></td><td>$perc%</td></tr>";

            $sum += $perc;
            $cnt++;
        }

        echo "</table>";

        if ($cnt > 0) {
            $avg = round($sum / $cnt, 2);
            echo "<div class='avg-box'>Team Average: <b>$avg%</b></div>";
        }
    }
}
?>

</body>
</html>
