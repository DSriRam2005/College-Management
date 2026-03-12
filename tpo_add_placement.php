<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'TPO') {
    die("ACCESS DENIED");
}

include "db.php";

$student = null;

/* ---------- FETCH STUDENT ---------- */
if (isset($_POST['fetch_student'])) {
    $htno = $_POST['htno'];

    $q = $conn->query("SELECT * FROM STUDENTS WHERE htno='$htno' LIMIT 1");

    if ($q->num_rows > 0) {
        $student = $q->fetch_assoc();
    } else {
        $student = "NOT_FOUND";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Placement</title>

<style>
body {
    font-family: "Segoe UI", Arial;
    margin: 0;
    background: #eef3ff;
}

/* HEADER */
.page-header {
    background: linear-gradient(90deg, #0048ff, #0099ff);
    color: white;
    padding: 14px;
    text-align: center;
    font-size: 22px;
    letter-spacing: .5px;
}

/* MAIN CARD */
.card {
    width: 90%;
    max-width: 550px;
    background: white;
    margin: 20px auto;
    padding: 20px 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

/* LABEL */
.card label {
    font-weight: 600;
    color: #003366;
}

/* INPUTS */
input[type=text], input[type=file], select {
    width: 100%;
    padding: 10px 12px;
    margin-top: 5px;
    margin-bottom: 14px;
    border: 2px solid #007bff;
    border-radius: 8px;
    outline: none;
    transition: .2s;
    background: #f8fbff;
}

input[type=text]:focus, select:focus {
    border-color: #00c3ff;
    box-shadow: 0 0 6px #00bfff;
}

/* BUTTONS */
button {
    width: 100%;
    background: #0066ff;
    border: none;
    padding: 12px;
    color: white;
    font-size: 15px;
    border-radius: 10px;
    cursor: pointer;
    transition: .2s;
}

button:hover {
    background: #004dcc;
}

/* STUDENT PHOTO */
.photo-preview {
    border-radius: 10px;
    border: 2px solid #0099ff;
}

/* SECTION TITLE */
.section-title {
    font-size: 20px;
    color: #0040a8;
    margin-bottom: 10px;
    margin-top: 20px;
    font-weight: bold;
}
</style>

</head>
<body>

<div class="page-header">TPO – Add Placement</div>

<!-- SEARCH STUDENT -->
<div class="card">
    <label>Enter HTNO</label>
    <form method="POST">
        <input type="text" name="htno" placeholder="Enter Hall Ticket No" required>
        <button type="submit" name="fetch_student">Fetch Student</button>
    </form>
</div>

<?php
/* ---------- STUDENT NOT FOUND ---------- */
if ($student == "NOT_FOUND") {
    echo "<div class='card' style='color:red; font-weight:bold;'>Student Not Found in STUDENTS Table</div>";
}

/* ---------- FOUND STUDENT ---------- */
if (is_array($student)) {
?>
<div class="card">

    <div class="section-title">Student Details</div>

    <form method="POST" enctype="multipart/form-data" action="save_placement.php">

        <label>HTNO</label>
        <input type="text" name="htno" value="<?php echo $student['htno']; ?>" readonly>

        <label>Name</label>
        <input type="text" name="name" value="<?php echo $student['name']; ?>" readonly>

        <label>Student Photo</label><br>
        <?php if ($student['photo'] != "") { ?>
            <img src="<?php echo $student['photo']; ?>" height="80" class="photo-preview"><br><br>
            <input type="hidden" name="photo" value="<?php echo $student['photo']; ?>">
        <?php } else { ?>
            <input type="file" name="photo" accept="image/*" required><br>

        <?php } ?>

        <label>Village</label>
        <input type="text" name="village"
               value="<?php echo $student['village']; ?>"
               <?php echo $student['village'] ? "readonly" : ""; ?>>

        <label>Mandal</label>
        <input type="text" name="mandal"
               value="<?php echo $student['mandal']; ?>"
               <?php echo $student['mandal'] ? "readonly" : ""; ?>>

        <label>District</label>
        <input type="text" name="dist"
               value="<?php echo $student['dist']; ?>"
               <?php echo $student['dist'] ? "readonly" : ""; ?>>

        <label>State</label>
        <input type="text" name="state"
               value="<?php echo $student['state']; ?>"
               <?php echo $student['state'] ? "readonly" : ""; ?>>

        <div class="section-title">Placement Details</div>

        <label>Select Company</label>
        <select name="company" id="company_dropdown" required onchange="fillCompanyDetails()">
            <option value="">Select Company</option>
            <?php
            $companies = $conn->query("SELECT * FROM PLACEMENT_COMPANIES");
            while ($c = $companies->fetch_assoc()) {
            ?>
            <option value="<?php echo $c['id']; ?>"
                    data-location="<?php echo $c['company_location']; ?>"
                    data-logo="<?php echo $c['company_logo']; ?>">
                <?php echo $c['company_name']; ?>
            </option>
            <?php } ?>
        </select>

        <label>Company Location</label>
        <input type="text" name="company_location" id="company_location" readonly>

        <label>Company Logo</label><br>
        <img id="company_logo_preview" src="" height="60" class="photo-preview"><br><br>

        <label>Package (LPA)</label>
        <input type="text" name="package">

        <button type="submit" name="submit_placement">Save Placement</button>

    </form>

</div>
<?php } ?>

<script>
function fillCompanyDetails() {
    var select = document.getElementById("company_dropdown");
    var opt = select.options[select.selectedIndex];

    document.getElementById("company_location").value = opt.getAttribute("data-location");
    document.getElementById("company_logo_preview").src = opt.getAttribute("data-logo");
}
</script>

</body>
</html>
