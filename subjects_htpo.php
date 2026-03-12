<?php
// ENABLE ERRORS
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

// ALLOW ONLY HTPO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    die("ACCESS DENIED");
}

$username = $_SESSION['username']; // HTPO login id

// ---------------------------------------------
// FETCH HTPO DETAILS
// ---------------------------------------------
$stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$htpo_prog    = $userData['prog'];
$htpo_year    = $userData['year'];
$htpo_college = $userData['college']; // SET('KIET','KIEK','KIEW')

// ---------------------------------------------
// FETCH CLASSIDS USING MULTIPLE COLLEGES
// ---------------------------------------------
$colleges = explode(',', $htpo_college);
$classOptions = [];

foreach ($colleges as $college) {
    $college = trim($college);
    if ($college === '') continue;

    $stmt = $conn->prepare("
        SELECT DISTINCT classid
        FROM STUDENTS
        WHERE FIND_IN_SET(?, college) > 0
          AND prog = ?
          AND year = ?
          AND classid <> ''
        ORDER BY classid
    ");
    $stmt->bind_param("ssi", $college, $htpo_prog, $htpo_year);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $classOptions[] = $row['classid'];
    }
    $stmt->close();
}

$classOptions = array_values(array_unique($classOptions));
sort($classOptions);

// ---------------------------------------------
// ADD SUBJECT + FILE UPLOAD LOGIC
// ---------------------------------------------
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {

    $faculty_empid = mysqli_real_escape_string($conn, $_POST['faculty_empid']);
    $faculty       = mysqli_real_escape_string($conn, $_POST['faculty_name']);
    $subject       = mysqli_real_escape_string($conn, $_POST['subject_name']);
    $faculty_exp   = mysqli_real_escape_string($conn, $_POST['faculty_experience']);
    $faculty_phone = mysqli_real_escape_string($conn, $_POST['faculty_phone']);

    // ===== FILE UPLOAD =====
    $uploadDir = "uploads/faculty/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = "";

    if (!empty($_FILES['faculty_photo']['name'])) {
        $fileTmp  = $_FILES['faculty_photo']['tmp_name'];
        $original = basename($_FILES['faculty_photo']['name']);
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            $message = "<p style='color:red;'>Only JPG, PNG, WEBP allowed.</p>";
        } else {
            $fileName = time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($fileTmp, $uploadDir . $fileName);
        }
    }

    if ($fileName == "") {
        $message = "<p style='color:red;'>Faculty photo upload failed.</p>";
    }

    if (!isset($_POST['classid']) || count($_POST['classid']) == 0) {
        $message = "<p style='color:red;'>Please select at least one Class ID.</p>";
    } else {

        foreach ($_POST['classid'] as $cid) {

            $cid = mysqli_real_escape_string($conn, $cid);

            $insert = $conn->prepare("
                INSERT INTO subjects 
                (prog, year, college, classid, faculty_empid, faculty_name, subject_name, faculty_experience, faculty_phone, faculty_photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "sissssssss",
                $htpo_prog,
                $htpo_year,
                $htpo_college,   // ✅ SAVING HTPO COLLEGE
                $cid,
                $faculty_empid,
                $faculty,
                $subject,
                $faculty_exp,
                $faculty_phone,
                $fileName
            );

            $insert->execute();
            $insert->close();
        }

        $message = "<p style='color:green;'>Subject added successfully.</p>";
    }
}

// ---------------------------------------------
// DELETE SUBJECT
// ---------------------------------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id=$id");
    header("Location: subjects_htpo.php");
    exit();
}

// --------------------------------------------------
// ✅ FETCH HTPO PROGRAM, YEAR, COLLEGE
// --------------------------------------------------
$stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prog    = $userData['prog'];
$year    = $userData['year'];
$college = trim($userData['college']); // MUST be single value like KIET

// --------------------------------------------------
// ✅ FETCH SUBJECTS BASED ON USER.PROG, YEAR, COLLEGE
// --------------------------------------------------
$filterClass = isset($_GET['filter_classid']) ? $_GET['filter_classid'] : "";

if ($filterClass != "") {
    $stmt = $conn->prepare("
        SELECT * FROM subjects
        WHERE prog = ?
          AND year = ?
          AND college = ?
          AND classid = ?
        ORDER BY classid, subject_name
    ");
    $stmt->bind_param("siss", $prog, $year, $college, $filterClass);
} else {
    $stmt = $conn->prepare("
        SELECT * FROM subjects
        WHERE prog = ?
          AND year = ?
          AND college = ?
        ORDER BY classid, subject_name
    ");
    $stmt->bind_param("sis", $prog, $year, $college);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>HTPO - Manage Subjects</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        th { background: #eee; }
        input, select { padding: 8px; width: 100%; margin-bottom: 10px; }
        .btn { padding: 10px 15px; background: green; color: white; border: none; cursor: pointer; }
        .btn-del { background: red; color: white; padding: 6px 10px; text-decoration: none; }
        img { border-radius: 5px; }
    </style>
</head>
<body>

<h2>HTPO – Manage Subjects</h2>

<?= $message ?>

<form method="POST" enctype="multipart/form-data">
    <h3>Add New Subject</h3>

    <label>Program</label>
    <input type="text" value="<?= $htpo_prog ?>" readonly>

    <label>Year</label>
    <input type="text" value="<?= $htpo_year ?>" readonly>

    <label>College(s)</label>
    <input type="text" value="<?= $htpo_college ?>" readonly>

    <label>Class IDs (Select Multiple)</label>
    <select name="classid[]" multiple required style="height:150px;">
        <?php foreach ($classOptions as $cid): ?>
            <option value="<?= $cid ?>"><?= $cid ?></option>
        <?php endforeach; ?>
    </select>

    <label>Faculty EMP ID</label>
    <input type="text" name="faculty_empid" required>

    <label>Faculty Name</label>
    <input type="text" name="faculty_name" required>

    <label>Faculty Experience</label>
    <input type="text" name="faculty_experience" required>

    <label>Faculty Phone</label>
    <input type="text" name="faculty_phone" required>

    <label>Faculty Photo</label>
    <input type="file" name="faculty_photo" accept="image/*" required>

    <label>Subject Name</label>
    <input type="text" name="subject_name" required>

    <button type="submit" name="add_subject" class="btn">Add Subject</button>
</form>

<h2>HTPO - Subject List</h2>

<p>
    <b>Program:</b> <?= $prog ?> |
    <b>Year:</b> <?= $year ?> |
    <b>College:</b> <?= $college ?>
</p>
<form method="GET" style="margin:20px 0;">
    <label><b>Filter by Class ID:</b></label>
    <select name="filter_classid" onchange="this.form.submit()">
        <option value="">-- All Classes --</option>
        <?php foreach ($classOptions as $cid): ?>
            <option value="<?= $cid ?>" 
                <?= isset($_GET['filter_classid']) && $_GET['filter_classid'] == $cid ? 'selected' : '' ?>>
                <?= $cid ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

    
<table>
    <tr>
        <th>ID</th>
        <th>Class ID</th>
        <th>Subject</th>
        <th>Faculty Name</th>
        <th>EMP ID</th>
        <th>Experience</th>
        <th>Phone</th>
        <th>Photo</th>
        <th>Action</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><span class="badge"><?= $row['classid'] ?></span></td>
            <td><?= $row['subject_name'] ?></td>
            <td><?= $row['faculty_name'] ?></td>
            <td><?= $row['faculty_empid'] ?></td>
            <td><?= $row['faculty_experience'] ?></td>
            <td><?= $row['faculty_phone'] ?></td>
            <td>
                <?php if (!empty($row['faculty_photo'])): ?>
                    <img src="uploads/faculty/<?= $row['faculty_photo'] ?>" width="60">
                <?php else: ?>
                    No Image
                <?php endif; ?>
            </td>
            <td>
                <a href="subjects_htpo.php?delete=<?= $row['id'] ?>" 
                   class="btn-del"
                   onclick="return confirm('Delete this subject?');">
                   Delete
                </a>
            </td>

        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">❌ No subjects found for your Program, Year & College</td>
        </tr>
    <?php endif; ?>
</table>
</body>
</html>
