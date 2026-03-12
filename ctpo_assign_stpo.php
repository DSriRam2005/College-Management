<?php
/*********************************************************
 * SHOW ERRORS (REMOVE IN PRODUCTION)
 *********************************************************/
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

/*********************************************************
 * ACCESS CONTROL
 *********************************************************/
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    die("ACCESS DENIED: CPTO ONLY");
}

/*********************************************************
 * GET CLASSID SAFELY
 *********************************************************/
if (empty($_SESSION['classid'])) {

    if (empty($_SESSION['id'])) {
        die("ERROR: USER ID NOT IN SESSION");
    }

    $uid = (int)$_SESSION['id'];

    $cq = $conn->prepare("SELECT classid FROM USERS WHERE id=? LIMIT 1");
    if (!$cq) {
        die("CLASSID PREPARE ERROR: ".$conn->error);
    }

    $cq->bind_param("i", $uid);
    $cq->execute();
    $cq->bind_result($cid);
    $cq->fetch();
    $cq->close();

    $_SESSION['classid'] = $cid ?? '';
}

$classid = $_SESSION['classid'];
if ($classid === '') {
    die("ERROR: CLASSID NOT ASSIGNED TO CPTO");
}

/*********************************************************
 * AJAX: FETCH STAFF NAME
 *********************************************************/
if (isset($_GET['ajax']) && $_GET['ajax'] === 'staff') {

    $empid = $_GET['empid'] ?? '';

    $q = $conn->prepare("SELECT NAME FROM kiet_staff WHERE EMPID=?");
    if (!$q) {
        echo json_encode(["status"=>"error","msg"=>$conn->error]);
        exit;
    }

    $q->bind_param("i", $empid);
    $q->execute();
    $q->bind_result($name);

    if ($q->fetch()) {
        echo json_encode(["status"=>"ok","name"=>$name]);
    } else {
        echo json_encode(["status"=>"error","name"=>""]);
    }

    $q->close();
    exit;
}

/*********************************************************
 * SAVE STPO
 *********************************************************/
if (isset($_POST['save'])) {

    if (empty($_POST['teamid']) || empty($_POST['empid'])) {
        die("ERROR: TEAMID OR EMPID MISSING");
    }

    $teamid = $_POST['teamid'];
    $empid  = $_POST['empid'];

    $up = $conn->prepare("
        UPDATE STUDENTS
        SET stpo=?
        WHERE teamid=? AND classid=?
    ");

    if (!$up) {
        die("UPDATE PREPARE ERROR: ".$conn->error);
    }

    $up->bind_param("sss", $empid, $teamid, $classid);
    $up->execute();

    if ($up->error) {
        die("UPDATE EXECUTE ERROR: ".$up->error);
    }

    $up->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/*********************************************************
 * FETCH TEAMS + ASSIGNED STPO + NAME
 *********************************************************/
$teams = $conn->prepare("
    SELECT
        s.teamid,
        MAX(s.stpo) AS stpo,
        k.NAME
    FROM STUDENTS s
    LEFT JOIN kiet_staff k ON k.EMPID = s.stpo
    WHERE s.classid=?
      AND s.debarred = 0
      AND s.teamid IS NOT NULL
      AND s.teamid!=''
    GROUP BY s.teamid
    ORDER BY
      SUBSTRING_INDEX(s.teamid,'_',1),
      CAST(SUBSTRING_INDEX(s.teamid,'_',-1) AS UNSIGNED)
");

if (!$teams) {
    die("SELECT PREPARE ERROR: ".$conn->error);
}

$teams->bind_param("s", $classid);
$teams->execute();
$teams->bind_result($teamid, $assigned_stpo, $assigned_name);
?>
<!DOCTYPE html>
<html>
<head>
<title>Assign STPO to Teams</title>
<meta charset="utf-8">

<style>
body{font-family:Arial;background:#f1f5f9;padding:20px}
h2{margin-bottom:15px}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{border:1px solid #ccc;padding:10px}
th{background:#e5e7eb}
input{padding:6px;width:130px}
button{padding:6px 14px;background:#2563eb;color:#fff;border:none;cursor:pointer}
.assigned{font-weight:bold;color:#1e40af}
.name{font-weight:bold;color:#065f46}
.error{color:red}
</style>

<script>
function fetchStaff(empid,row){
    const el=document.getElementById("name_"+row);
    if(empid===""){el.innerHTML="";return;}

    fetch("?ajax=staff&empid="+empid)
    .then(r=>r.json())
    .then(d=>{
        if(d.status==="ok"){
            el.innerHTML=d.name;
            el.className="name";
        }else{
            el.innerHTML="Not Found";
            el.className="error";
        }
    });
}
</script>
</head>

<body>

<h2>Assign STPO – Class <?= htmlspecialchars($classid) ?></h2>

<table>
<tr>
    <th>Team ID</th>
    <th>Assigned EMPID</th>
    <th>Assigned Name</th>
    <th>New EMPID</th>
    <th>Live Name</th>
    <th>Action</th>
</tr>

<?php $i=0; while($teams->fetch()): $i++; ?>
<tr>
<form method="post">
    <td>
        <?= htmlspecialchars($teamid ?? '') ?>
        <input type="hidden" name="teamid" value="<?= htmlspecialchars($teamid ?? '') ?>">
    </td>

    <td class="assigned">
        <?= htmlspecialchars($assigned_stpo ?? '—') ?>
    </td>

    <td class="assigned">
        <?= htmlspecialchars($assigned_name ?? '—') ?>
    </td>

    <td>
        <input type="text"
               name="empid"
               value="<?= htmlspecialchars($assigned_stpo ?? '') ?>"
               onblur="fetchStaff(this.value,<?= $i ?>)"
               required>
    </td>

    <td id="name_<?= $i ?>"></td>

    <td>
        <button type="submit" name="save">Save</button>
    </td>
</form>
</tr>
<?php endwhile; $teams->close(); ?>

</table>

</body>
</html>
