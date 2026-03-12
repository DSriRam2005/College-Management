<?php
session_start();
include 'db.php';

// ✅ Allow only CPTO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: login.php");
    exit();
}

$cp_classid = $_SESSION['classid'];
$year = $_SESSION['year'] ?? null;
$message = "";

// ✅ Validate classid
if (empty($cp_classid)) {
    echo "<p style='text-align:center;margin-top:50px;'>⚠️ No class assigned to this CPTO.</p>";
    exit();
}

// ✅ Handle submission for all students
if (isset($_POST['save_all'])) {
    $htnos = $_POST['htno'] ?? [];
    $st_phones = $_POST['st_phone'] ?? [];
    $f_phones = $_POST['f_phone'] ?? [];
    $errors = 0;

    foreach ($htnos as $i => $htno) {
        $st_phone = trim($st_phones[$i]);
        $f_phone = trim($f_phones[$i]);

        // ✅ Allow blanks, but validate if filled
        if (
            ($st_phone !== "" && !preg_match('/^\d{10}$/', $st_phone)) ||
            ($f_phone !== "" && !preg_match('/^\d{10}$/', $f_phone)) ||
            ($st_phone !== "" && $f_phone !== "" && $st_phone === $f_phone)
        ) {
            $errors++;
            continue;
        }

        // ✅ Optional: store blanks as NULL
        $st_phone = $st_phone === "" ? null : $st_phone;
        $f_phone = $f_phone === "" ? null : $f_phone;

        $stmt = $conn->prepare("UPDATE STUDENTS SET st_phone=?, f_phone=? WHERE htno=?");
        $stmt->bind_param("sss", $st_phone, $f_phone, $htno);
        $stmt->execute();
        $stmt->close();
    }

    if ($errors === 0) {
        $message = "<p class='success'>✅ All student details updated successfully.</p>";
    } else {
        $message = "<p class='error'>⚠️ Some rows had invalid or duplicate phone numbers and were skipped.</p>";
    }
}

// ✅ Fetch all students for this class
$stmt = $conn->prepare("SELECT htno, name, classid, st_phone, f_phone FROM STUDENTS WHERE classid=? ORDER BY htno");
$stmt->bind_param("s", $cp_classid);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();

// ✅ Check if any incomplete details exist
$incomplete = false;
foreach ($students as $row) {
    if (empty($row['st_phone']) || empty($row['f_phone'])) {
        $incomplete = true;
        break;
    }
}
// Rewind result set for display
$stmt = $conn->prepare("SELECT htno, name, classid, st_phone, f_phone FROM STUDENTS WHERE classid=? ORDER BY htno");
$stmt->bind_param("s", $cp_classid);
$stmt->execute();
$students = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Student Details - <?= htmlspecialchars($cp_classid) ?></title>
<style>
body { font-family:'Segoe UI',sans-serif;background:#f5f6fa; }
.container { max-width:850px;margin:40px auto;background:#fff;padding:25px;border-radius:10px;box-shadow:0 3px 10px rgba(0,0,0,0.1); }
h2 { text-align:center;color:#007BFF; }
table { width:100%;border-collapse:collapse;margin-top:20px; }
th, td { padding:10px;border-bottom:1px solid #ddd;text-align:center; }
input[type=text]{width:95%;padding:6px;border:1px solid #ccc;border-radius:6px;}
input[type=submit]{background:#28a745;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-size:16px;margin-top:15px;}
input[type=submit]:hover{background:#218838;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:6px;margin-bottom:10px;}
.error{background:#f8d7da;color:#721c24;padding:8px;border-radius:6px;margin-bottom:10px;}
.alert{background:#fff3cd;color:#856404;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center;}
</style>
</head>
<body>
<div class="container">
<h2>Update Student Details (<?= htmlspecialchars($cp_classid) ?>)</h2>

<?php
if ($incomplete) {
    echo "<div class='alert'>⚠️ Some student details are incomplete. You may leave phone numbers blank if unavailable.</div>";
}
echo $message;
?>

<?php if ($students->num_rows > 0): ?>
<form method="POST" onsubmit="return validatePhones();">
<table>
<tr>
    <th>S.No</th>
    <th>HT No</th>
    <th>Name</th>
    <th>Student Phone</th>
    <th>Parent Phone</th>
</tr>
<?php 
$sn = 1;
while ($row = $students->fetch_assoc()): ?>
<tr>
    <td><?= $sn++ ?></td>
    <td><?= htmlspecialchars($row['htno']) ?><input type="hidden" name="htno[]" value="<?= htmlspecialchars($row['htno']) ?>"></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><input type="text" name="st_phone[]" value="<?= htmlspecialchars($row['st_phone']) ?>" maxlength="10"></td>
    <td><input type="text" name="f_phone[]" value="<?= htmlspecialchars($row['f_phone']) ?>" maxlength="10"></td>
</tr>
<?php endwhile; ?>
</table>
<input type="submit" name="save_all" value="Save All">
</form>

<script>
function validatePhones() {
    let stPhones = document.getElementsByName('st_phone[]');
    let fPhones = document.getElementsByName('f_phone[]');
    let htNos = document.getElementsByName('htno[]');
    for (let i = 0; i < stPhones.length; i++) {
        let st = stPhones[i].value.trim();
        let f = fPhones[i].value.trim();

        // ✅ Allow blanks, but validate if filled
        if (st !== "" && !/^\d{10}$/.test(st)) {
            alert("Student phone must be 10 digits for HT No: " + htNos[i].value);
            return false;
        }
        if (f !== "" && !/^\d{10}$/.test(f)) {
            alert("Parent phone must be 10 digits for HT No: " + htNos[i].value);
            return false;
        }
        if (st !== "" && f !== "" && st === f) {
            alert("Student and Parent phone cannot be same for HT No: " + htNos[i].value);
            return false;
        }
    }
    return true;
}
</script>

<?php else: ?>
<p style="text-align:center;">No students found for class <b><?= htmlspecialchars($cp_classid) ?></b>.</p>
<?php endif; ?>
</div>
</body>
</html>
