<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'TPO') {
    die("ACCESS DENIED");
}

include "db.php";

// --------------------- UPDATE STUDENT DETAILS IF MISSING ---------------------

$htno = $_POST['htno'];

// --------------------- PHOTO (IMAGES ONLY) ---------------------

$photo = $_POST['photo'] ?? "";

if (!empty($_FILES['photo']['name'])) {

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $file_type = $_FILES['photo']['type'];

    if (!in_array($file_type, $allowed_types)) {
        die("❌ Only JPG, PNG, and WEBP images are allowed.");
    }

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photo = "uploads/photo/" . time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file($_FILES['photo']['tmp_name'], $photo);

    // Block fake renamed files
    if (!getimagesize($photo)) {
        unlink($photo);
        die("❌ Invalid image file.");
    }

    $conn->query("UPDATE STUDENTS SET photo='$photo' WHERE htno='$htno'");
}

// VILLAGE
if ($_POST['village']) {
    $conn->query("UPDATE STUDENTS
                  SET village='{$_POST['village']}'
                  WHERE htno='$htno'");
}

// MANDAL
if ($_POST['mandal']) {
    $conn->query("UPDATE STUDENTS
                  SET mandal='{$_POST['mandal']}'
                  WHERE htno='$htno'");
}

// DISTRICT
if ($_POST['dist']) {
    $conn->query("UPDATE STUDENTS
                  SET dist='{$_POST['dist']}'
                  WHERE htno='$htno'");
}

// STATE
if ($_POST['state']) {
    $conn->query("UPDATE STUDENTS
                  SET state='{$_POST['state']}'
                  WHERE htno='$htno'");
}


// --------------------- SAVE PLACEMENT DETAILS ---------------------

$company = $_POST['company'];
$location = $_POST['company_location'];
$package = $_POST['package'];
$name = $_POST['name'];

$q = $conn->query("SELECT company_logo, company_name FROM PLACEMENT_COMPANIES WHERE id='$company'");
$r = $q->fetch_assoc();

$logo = $r['company_logo'];
$cname = $r['company_name'];

$sql = "INSERT INTO PLACEMENT_DETAILS
        (htno, name, placed_company, placed_company_location, company_logo, package)
        VALUES ('$htno', '$name', '$cname', '$location', '$logo', '$package')";

$conn->query($sql);

echo "<h3>Placement Added Successfully</h3>";
echo "<a href='tpo_add_placement.php'>Go Back</a>";
?>
