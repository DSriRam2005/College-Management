<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$msg = "";

/* ===========================================
   ACCESS CONTROL
=========================================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: login.php");
    exit;
}

$classid = $_SESSION['classid'];

/* ===========================================
   FETCH CLASS META (ONCE)
=========================================== */
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

$class_prog    = strtoupper($meta['prog']);
$class_year    = (int)$meta['year'];
$class_branch  = $meta['branch'];
$class_college = $meta['college'];

/* ===========================================
   DOWNLOAD SAMPLE CSV
=========================================== */
if (isset($_GET['download'])) {

    $sem = $_GET['sem'];

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=sample_{$classid}_{$sem}.csv");

    $out = fopen("php://output", "w");

    /* SUBJECTS (CLASS-AWARE) */
    $subQ = $conn->prepare("
        SELECT subject
        FROM MIDS
        WHERE sem=? AND prog=? AND year=?
          AND FIND_IN_SET(?, branch)
          AND FIND_IN_SET(?, college)
        ORDER BY id
    ");
    $subQ->bind_param(
        "sisss",
        $sem,
        $class_prog,
        $class_year,
        $class_branch,
        $class_college
    );
    $subQ->execute();
    $subRes = $subQ->get_result();

    $subjects = [];
    while ($r = $subRes->fetch_assoc()) {
        $subjects[] = $r['subject'];
    }

    /* HEADER */
    fputcsv($out, array_merge(["ID", "ROLL", "NAME"], $subjects));

    /* STUDENTS */
    $stuQ = $conn->prepare("
        SELECT htno, name
        FROM STUDENTS
        WHERE classid=?
        ORDER BY CAST(htno AS UNSIGNED)
    ");
    $stuQ->bind_param("s", $classid);
    $stuQ->execute();
    $stuRes = $stuQ->get_result();

    $i = 1;
    while ($s = $stuRes->fetch_assoc()) {
        fputcsv(
            $out,
            array_merge([$i++, $s['htno'], $s['name']], array_fill(0, count($subjects), ""))
        );
    }

    fclose($out);
    exit;
}

/* ===========================================
   UPLOAD CSV AND SAVE MARKS
=========================================== */
if (isset($_POST['upload'])) {

    $mid = (int)$_POST['mid'];
    $sem = $_POST['sem'];

    if ($_FILES['file']['error'] !== 0) {
        $msg = "File upload error.";
    } else {

        $fp = fopen($_FILES['file']['tmp_name'], "r");
        $header = fgetcsv($fp);
        $subjects = array_slice($header, 3);

        /* SUBJECT → mids_id MAP (CLASS-AWARE) */
        $subject_map = [];

        foreach ($subjects as $subj) {
            $subj = trim($subj);

            $q = $conn->prepare("
                SELECT id
                FROM MIDS
                WHERE subject=? AND mid=? AND sem=? AND prog=? AND year=?
                  AND FIND_IN_SET(?, branch)
                  AND FIND_IN_SET(?, college)
                LIMIT 1
            ");
            $q->bind_param(
                "sississ",
                $subj,
                $mid,
                $sem,
                $class_prog,
                $class_year,
                $class_branch,
                $class_college
            );
            $q->execute();
            $r = $q->get_result();
            $subject_map[$subj] = $r->num_rows ? $r->fetch_assoc()['id'] : null;
        }

        /* PROCESS ROWS */
        while (($row = fgetcsv($fp)) !== false) {

            $roll = trim($row[1]);
            $name = trim($row[2]);

            if ($roll === "") continue;

            for ($i = 3; $i < count($row); $i++) {

                $subject = $header[$i];
                $marks = strtoupper(trim($row[$i]));
                $mids_id = $subject_map[$subject] ?? null;

                if (!$mids_id) continue;

                if ($marks === "AB" || $marks === "A") $marks = "A";
                if ($marks === "") continue;

                /* EXISTS? */
                $chk = $conn->prepare("
                    SELECT id FROM MID_MARKS
                    WHERE roll=? AND mids_id=?
                    LIMIT 1
                ");
                $chk->bind_param("si", $roll, $mids_id);
                $chk->execute();
                $exists = $chk->get_result()->num_rows > 0;
                $chk->close();

                if ($exists) {
                    $upd = $conn->prepare("
                        UPDATE MID_MARKS
                        SET marks_obtained=?,
                            name=?, subject=?,
                            prog=?, year=?, classid=?, college=?
                        WHERE roll=? AND mids_id=?
                    ");
                    $upd->bind_param(
                        "ssssssssi",
                        $marks,
                        $name,
                        $subject,
                        $class_prog,
                        $class_year,
                        $classid,
                        $class_college,
                        $roll,
                        $mids_id
                    );
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("
                        INSERT INTO MID_MARKS
                        (roll,name,subject,mids_id,marks_obtained,prog,year,classid,college)
                        VALUES (?,?,?,?,?,?,?,?,?)
                    ");
                    $ins->bind_param(
                        "sssisssss",
                        $roll,
                        $name,
                        $subject,
                        $mids_id,
                        $marks,
                        $class_prog,
                        $class_year,
                        $classid,
                        $class_college
                    );
                    $ins->execute();
                }
            }
        }

        fclose($fp);
        $msg = "Marks uploaded successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>MID Marks Upload & Download</title>
<style>
body { font-family: Arial; padding:20px; background:#f4f6f9; }
.box { width:520px; padding:20px; background:#fff; border-radius:8px; }
.btn { padding:8px 14px; background:#007bff; color:#fff; border:none; cursor:pointer; }
.btn:hover { background:#0056b3; }
.msg { color:green; margin-bottom:10px; font-weight:bold; }
</style>
</head>

<body>

<h2>MID Marks Upload & Sample Download</h2>
<?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>

<div class="box">

<h3>Download Sample CSV (<?= $classid ?>)</h3>
<form method="GET">
<select name="sem" required>
<?php foreach (['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'] as $s)
    echo "<option value='$s'>$s</option>"; ?>
</select><br><br>
<button name="download" class="btn">Download</button>
</form>

<hr>

<h3>Upload Filled CSV</h3>
<form method="POST" enctype="multipart/form-data">

<label>MID</label><br>
<select name="mid" required>
<option value="1">MID-1</option>
<option value="2">MID-2</option>
<option value="3">MID-3</option>
</select><br><br>

<label>SEM</label><br>
<select name="sem" required>
<?php foreach (['1-1','1-2','2-1','2-2','3-1','3-2','4-1','4-2'] as $s)
    echo "<option value='$s'>$s</option>"; ?>
</select><br><br>

<input type="file" name="file" accept=".csv" required><br><br>
<button name="upload" class="btn">Upload Marks</button>

</form>
</div>

</body>
</html>
