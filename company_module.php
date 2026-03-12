<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'TPO') {
    die("ACCESS DENIED");
}

include "db.php";

$edit_mode = false;
$edit_data = null;

/* ------------------------- DELETE ------------------------- */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM PLACEMENT_COMPANIES WHERE id='$id'");
    header("Location: company_module.php");
    exit;
}

/* ------------------------- EDIT --------------------------- */
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = $_GET['edit'];
    $res = $conn->query("SELECT * FROM PLACEMENT_COMPANIES WHERE id='$id'");
    $edit_data = $res->fetch_assoc();
}

/* ------------------------- SAVE / UPDATE ------------------ */
if (isset($_POST['save_company'])) {

    $name = $_POST['company_name'];
    $location = $_POST['company_location'];

    $logo = $_POST['existing_logo'] ?? "";

    if (!empty($_FILES['company_logo']['name'])) {
        $logo = "uploads/" . time() . "_" . basename($_FILES['company_logo']['name']);
        move_uploaded_file($_FILES['company_logo']['tmp_name'], $logo);
    }

    if ($_POST['company_id'] == "") {
        $sql = "INSERT INTO PLACEMENT_COMPANIES (company_name, company_location, company_logo)
                VALUES ('$name', '$location', '$logo')";
    } else {
        $id = $_POST['company_id'];
        $sql = "UPDATE PLACEMENT_COMPANIES
                SET company_name='$name',
                    company_location='$location',
                    company_logo='$logo'
                WHERE id='$id'";
    }

    $conn->query($sql);
    header("Location: company_module.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Company Management</title>

<style>

/* GLOBAL */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #eef3ff;
    margin: 0;
    padding: 0;
}

/* PAGE TITLE */
.page-title {
    text-align: center;
    padding: 15px;
    background: linear-gradient(90deg, #0050ff, #0099ff);
    color: white;
    margin: 0;
    font-size: 24px;
    letter-spacing: .5px;
}

/* FORM CARD */
.form-card {
    width: 90%;
    max-width: 500px;
    background: white;
    margin: 20px auto;
    padding: 20px 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    border-radius: 15px;
}

.form-card h3 {
    text-align: center;
    margin-top: 0;
    color: #003388;
}

/* INPUT STYLING */
.form-card input[type="text"], .form-card input[type="file"]{
    width: 100%;
    padding: 10px;
    border: 2px solid #007bff;
    border-radius: 8px;
    margin-top: 5px;
    outline: none;
}

.form-card input[type="text"]:focus {
    border-color: #00bbff;
    box-shadow: 0 0 5px #00c3ff;
}

.form-card button {
    width: 100%;
    background: #007bff;
    border: none;
    padding: 12px;
    color: white;
    font-size: 15px;
    border-radius: 10px;
    cursor: pointer;
    transition: .2s;
    margin-top: 15px;
}

.form-card button:hover {
    background: #005fcc;
}

/* COMPANY LIST TABLE */
.table-box {
    width: 95%;
    margin: 20px auto;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

th {
    background: #0066ff;
    color: white;
    padding: 10px;
    font-size: 15px;
}

td {
    padding: 10px;
    text-align: center;
}

tr:nth-child(even) {
    background: #f0f5ff;
}

/* ACTION LINKS */
.action-btn a {
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    color: white;
    font-size: 13px;
}

.edit-btn { background: #ffa200; }
.delete-btn { background: #ff0033; }

/* RESPONSIVE */
@media(max-width: 600px){
    th, td { font-size: 13px; padding: 8px; }
}
</style>

</head>
<body>

<div class="page-title">TPO – Company Management</div>

<!-- ADD / EDIT FORM -->
<div class="form-card">
    <h3><?php echo $edit_mode ? "Edit Company" : "Add New Company"; ?></h3>

    <form method="POST" enctype="multipart/form-data">

        <input type="hidden" name="company_id" value="<?php echo $edit_mode ? $edit_data['id'] : ''; ?>">
        <input type="hidden" name="existing_logo" value="<?php echo $edit_mode ? $edit_data['company_logo'] : ''; ?>">

        <label>Company Name:</label>
        <input type="text" name="company_name"
               value="<?php echo $edit_mode ? $edit_data['company_name'] : ''; ?>" required>

        <br><br>

        <label>Company Location:</label>
        <input type="text" name="company_location"
               value="<?php echo $edit_mode ? $edit_data['company_location'] : ''; ?>">

        <br><br>

        <label>Company Logo:</label>
        <input type="file" name="company_logo">

        <?php if ($edit_mode && $edit_data['company_logo']) { ?>
            <br><br>
            <img src="<?php echo $edit_data['company_logo']; ?>" height="80" style="border-radius:8px;">
        <?php } ?>

        <button type="submit" name="save_company">
            <?php echo $edit_mode ? "Update Company" : "Add Company"; ?>
        </button>

    </form>
</div>

<!-- COMPANY LIST -->
<div class="table-box">
    <h3 style="text-align:center; color:#002766;">Company List</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Location</th>
            <th>Logo</th>
            <th>Action</th>
        </tr>

        <?php
        $res = $conn->query("SELECT * FROM PLACEMENT_COMPANIES ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) {
        ?>
        <tr>
            <td><?php echo $r['id']; ?></td>
            <td><?php echo $r['company_name']; ?></td>
            <td><?php echo $r['company_location']; ?></td>

            <td>
                <?php if ($r['company_logo']) { ?>
                    <img src="<?php echo $r['company_logo']; ?>" height="40" style="border-radius:5px;">
                <?php } ?>
            </td>

            <td class="action-btn">
                <a class="edit-btn" href="company_module.php?edit=<?php echo $r['id']; ?>">Edit</a>
                <a class="delete-btn" href="company_module.php?delete=<?php echo $r['id']; ?>"
                   onclick="return confirm('Delete this company?');">Delete</a>
            </td>
        </tr>
        <?php } ?>

    </table>
</div>

</body>
</html>
