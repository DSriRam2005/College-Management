<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

/* ---------------------------------------------
   ACCESS CONTROL
--------------------------------------------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: login.php");
    exit;
}

$htpo_username = $_SESSION['username'];

/* ---------------------------------------------
   FETCH HTPO DETAILS
--------------------------------------------- */
$stmt = $conn->prepare("
    SELECT college, prog, year 
    FROM USERS 
    WHERE username=? 
    LIMIT 1
");
$stmt->bind_param("s", $htpo_username);
$stmt->execute();
$htpo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$colleges = array_map('trim', explode(",", $htpo['college']));
$prog = strtoupper($htpo['prog']);
$year = (int)$htpo['year'];

/* DEFAULT MID & SEM */
$mid = (int)($_GET['mid'] ?? 1);
$sem = $_GET['sem'] ?? "1-1";
?>

<!DOCTYPE html>
<html>
<head>
<title>HTPO – MID Summary Report</title>
<style>
body { font-family: Arial; background:#eef2f7; padding:20px; }
h2 { margin-bottom:20px; }
.box { background:#fff; padding:18px; max-width:700px; border-radius:10px; }
select,button { width:100%; padding:10px; margin-top:10px; }
button { background:#0066ff; color:#fff; font-weight:bold; cursor:pointer; }
table { width:100%; margin-top:20px; border-collapse:collapse; background:#fff; }
th { background:#4c6ef5; color:#fff; padding:10px; }
td { padding:10px; border:1px solid #ddd; text-align:center; }
.good { background:#b6fcb6; font-weight:bold; }
.avg  { background:#ffe6a1; font-weight:bold; }
.bad  { background:#ffb3b3; font-weight:bold; }
a.class-link { color:#0066ff; font-weight:bold; text-decoration:none; }
</style>
</head>
<body>

<h2>
HTPO MID Summary – <?= htmlspecialchars($htpo['college']) ?> |
<?= $prog ?> | YEAR <?= $year ?>
</h2>

<!-- FILTER -->
<div class="box">
<form method="GET">

<label><b>Select MID</b></label>
<select name="mid">
<?php for ($i=1;$i<=3;$i++) echo "<option value='$i' ".($mid==$i?'selected':'').">MID-$i</option>"; ?>
</select>

<label><b>Select SEM</b></label>
<select name="sem">
<?php
$sems = ['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'];
foreach ($sems as $s)
    echo "<option value='$s' ".($sem==$s?'selected':'').">$s</option>";
?>
</select>

<button>View Report</button>
</form>
</div>

<?php
/* ---------------------------------------------
   CALCULATE MAX TOTAL MARKS (CORRECTED)
--------------------------------------------- */
$stmt = $conn->prepare("
    SELECT SUM(marks) AS max_total
    FROM MIDS
    WHERE mid=?
      AND sem=?
      AND prog=?
      AND year=?
      AND (
            " . implode(" OR ", array_fill(0, count($colleges), "FIND_IN_SET(?, college)")) . "
          )
");
$types = "issi" . str_repeat("s", count($colleges));
$params = array_merge([$mid, $sem, $prog, $year], $colleges);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$max_total = (int)$stmt->get_result()->fetch_assoc()['max_total'];
$stmt->close();

if ($max_total <= 0) {
    echo "<p><b>No subjects found for MID-$mid / SEM $sem.</b></p>";
    exit;
}

/* ---------------------------------------------
   FETCH CLASS LIST
--------------------------------------------- */
$class_list = [];

foreach ($colleges as $college) {
    $stmt = $conn->prepare("
        SELECT DISTINCT classid
        FROM STUDENTS
        WHERE FIND_IN_SET(?, college)
          AND prog=?
          AND year=?
        ORDER BY classid
    ");
    $stmt->bind_param("ssi", $college, $prog, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $class_list[] = $r['classid'];
    $stmt->close();
}

$class_list = array_unique($class_list);
sort($class_list);

/* ---------------------------------------------
   SUMMARY TABLE
--------------------------------------------- */
echo "<table>
<tr>
<th>Class ID</th>
<th>Total Students</th>
<th>Attended</th>
<th>Attendance %</th>
<th>Class Avg %</th>
</tr>";

foreach ($class_list as $classid) {

    // Total students
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM STUDENTS
        WHERE classid=? AND debarred=0
    ");
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Attendance
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT roll) AS attended
        FROM MID_MARKS
        WHERE mids_id IN (
            SELECT id FROM MIDS
            WHERE mid=? AND sem=? AND prog=? AND year=?
        )
        AND roll IN (
            SELECT htno FROM STUDENTS
            WHERE classid=? AND debarred=0
        )
    ");
    $stmt->bind_param("issis", $mid, $sem, $prog, $year, $classid);
    $stmt->execute();
    $attended = (int)$stmt->get_result()->fetch_assoc()['attended'];
    $stmt->close();

    $attendance_percent = $total ? round(($attended/$total)*100,2) : 0;

    // Class average
    $stmt = $conn->prepare("
        SELECT SUM(marks_obtained) AS total
        FROM MID_MARKS
        WHERE mids_id IN (
            SELECT id FROM MIDS
            WHERE mid=? AND sem=? AND prog=? AND year=?
        )
        AND roll IN (
            SELECT htno FROM STUDENTS
            WHERE classid=? AND debarred=0
        )
        GROUP BY roll
    ");
    $stmt->bind_param("issis", $mid, $sem, $prog, $year, $classid);
    $stmt->execute();
    $res = $stmt->get_result();

    $sum = 0; $cnt = 0;
    while ($r = $res->fetch_assoc()) {
        $sum += ($r['total'] / $max_total) * 100;
        $cnt++;
    }
    $stmt->close();

    $avg = $cnt ? round($sum/$cnt,2) : 0;
    $color = ($avg>=75?'good':($avg>=50?'avg':'bad'));

    $link = "<a class='class-link' href='ctpo_teamwise_mid.php?classid=$classid&mid=$mid&sem=$sem'>$classid</a>";

    echo "<tr>
        <td>$link</td>
        <td>$total</td>
        <td>$attended</td>
        <td>$attendance_percent%</td>
        <td class='$color'>$avg%</td>
    </tr>";
}

echo "</table>";
?>

</body>
</html>
