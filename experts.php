<?php
session_start();
require_once "db.php";

/* ROLE CHECK */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    die("ACCESS DENIED");
}

$msg = "";

/* DELETE EXPERT */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get photo path
    $photoQ = $conn->query("SELECT expert_photo FROM EXPERTS WHERE expert_id=$id");
    $p = $photoQ->fetch_assoc();
    if ($p && $p['expert_photo'] && file_exists($p['expert_photo'])) {
        unlink($p['expert_photo']);
    }

    $conn->query("DELETE FROM EXPERTS WHERE expert_id=$id");
    $msg = "Expert deleted successfully.";
}

/* ADD or UPDATE EXPERT */
if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name  = trim($_POST['expert_name']);
    $qual  = trim($_POST['expert_qualification']);
    $exp   = trim($_POST['expert_experience']);
    $from  = trim($_POST['expert_from']);
    $phone = trim($_POST['expert_phone']);
    $eid   = intval($_POST['expert_id'] ?? 0);

    /* PHOTO */
    $photo_path = $_POST['old_photo'] ?? NULL;

    if (!empty($_FILES['expert_photo']['name'])) {
        $folder = "uploads/experts/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $filename = "expert_" . time() . "_" . basename($_FILES['expert_photo']['name']);
        $target = $folder . $filename;

        if (move_uploaded_file($_FILES['expert_photo']['tmp_name'], $target)) {
            $photo_path = $target;
        }
    }

    if ($eid == 0) {  
        // INSERT
        $stmt = $conn->prepare("INSERT INTO EXPERTS 
            (expert_name,expert_qualification,expert_experience,expert_from,expert_phone,expert_photo)
            VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $name,$qual,$exp,$from,$phone,$photo_path);
        $stmt->execute();
        $msg = "Expert added successfully.";
    } else {
        // UPDATE
        $stmt = $conn->prepare("UPDATE EXPERTS SET
            expert_name=?, expert_qualification=?, expert_experience=?, expert_from=?, 
            expert_phone=?, expert_photo=? WHERE expert_id=?");
        $stmt->bind_param("ssssssi", $name,$qual,$exp,$from,$phone,$photo_path,$eid);
        $stmt->execute();
        $msg = "Expert updated successfully.";
    }
}

/* LOAD FOR EDIT */
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_data = ["expert_name"=>"","expert_qualification"=>"","expert_experience"=>"","expert_from"=>"","expert_phone"=>"","expert_photo"=>""];

if ($edit_id > 0) {
    $res = $conn->query("SELECT * FROM EXPERTS WHERE expert_id=$edit_id");
    $edit_data = $res->fetch_assoc();
}

/* SEARCH */
$search = trim($_GET['search'] ?? "");
$where = "";

if ($search !== "") {
    $search = $conn->real_escape_string($search);
    $where = "WHERE expert_name LIKE '%$search%' 
              OR expert_phone LIKE '%$search%'
              OR expert_qualification LIKE '%$search%'
              OR expert_from LIKE '%$search%'";
}

$list = $conn->query("SELECT * FROM EXPERTS $where ORDER BY expert_id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expert Management</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { width: 450px; padding: 20px; border: 1px solid #ccc; margin-bottom: 30px; }
        input, button, textarea { width: 100%; padding: 8px; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        th { background: #f2f2f2; }
        img { width: 50px; height: 50px; object-fit: cover; }
        .msg { padding: 10px; background: #e0ffe0; border: 1px solid #0a0; margin-bottom: 20px; }
        .actions a { margin-right: 10px; text-decoration:none; padding:5px 10px; border-radius:4px; }
        .edit { background:#007bff;color:#fff; }
        .delete { background:#dc3545;color:#fff; }
    </style>
</head>
<body>

<h2>Expert Management</h2>

<?php if ($msg): ?>
    <div class="msg"><?= $msg ?></div>
<?php endif; ?>

<!-- ADD / EDIT FORM -->
<div class="box">
    <h3><?= $edit_id ? "Edit Expert" : "Add Expert" ?></h3>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="expert_id" value="<?= $edit_id ?>">
        <input type="hidden" name="old_photo" value="<?= $edit_data['expert_photo'] ?>">

        <label>Name</label>
        <input type="text" name="expert_name" required value="<?= $edit_data['expert_name'] ?>">

        <label>Qualification</label>
        <input type="text" name="expert_qualification" value="<?= $edit_data['expert_qualification'] ?>">

        <label>Experience</label>
        <input type="text" name="expert_experience" value="<?= $edit_data['expert_experience'] ?>">

        <label>From</label>
        <input type="text" name="expert_from" value="<?= $edit_data['expert_from'] ?>">

        <label>Phone</label>
        <input type="text" name="expert_phone" value="<?= $edit_data['expert_phone'] ?>">

        <label>Photo</label>
        <input type="file" name="expert_photo" accept="image/*">
        <?php if ($edit_id && $edit_data['expert_photo']): ?>
            <img src="<?= $edit_data['expert_photo'] ?>" style="margin-top:10px;">
        <?php endif; ?>

        <button type="submit"><?= $edit_id ? "Update Expert" : "Add Expert" ?></button>
    </form>
</div>

<!-- SEARCH BAR -->
<form method="get">
    <input type="text" name="search" placeholder="Search experts..." value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
</form>

<!-- EXPERT LIST -->
<table>
    <tr>
        <th>ID</th>
        <th>Photo</th>
        <th>Name</th>
        <th>Qualification</th>
        <th>Experience</th>
        <th>From</th>
        <th>Phone</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $list->fetch_assoc()): ?>
    <tr>
        <td><?= $row['expert_id'] ?></td>
        <td>
            <?php if($row['expert_photo']): ?>
                <img src="<?= $row['expert_photo'] ?>">
            <?php else: ?> — <?php endif; ?>
        </td>
        <td><?= $row['expert_name'] ?></td>
        <td><?= $row['expert_qualification'] ?></td>
        <td><?= $row['expert_experience'] ?></td>
        <td><?= $row['expert_from'] ?></td>
        <td><?= $row['expert_phone'] ?></td>
        <td class="actions">
            <a class="edit" href="experts.php?edit=<?= $row['expert_id'] ?>">Edit</a>
            <a class="delete" href="experts.php?delete=<?= $row['expert_id'] ?>"
               onclick="return confirm('Delete this expert?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
