<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id']);
$user = $conn->query("SELECT * FROM USERS WHERE id=$id")->fetch_assoc();

if (isset($_POST['update'])) {
    $username = $_POST['username'];
    $role = $_POST['role'];
    $year = $_POST['year'];
    $classid = $_POST['classid'];
    $teamid = $_POST['teamid'];

    // If password provided, update with hash
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $sql = "UPDATE USERS SET username=?, password=?, role=?, year=?, classid=?, teamid=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $username, $password, $role, $year, $classid, $teamid, $id);
    } else {
        $sql = "UPDATE USERS SET username=?, role=?, year=?, classid=?, teamid=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdii", $username, $role, $year, $classid, $teamid, $id);
    }
    $stmt->execute();
    header("Location: manage_users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <h3>Edit User</h3>
    <form method="POST">
        <div class="mb-3"><input type="text" name="username" class="form-control" value="<?= $user['username'] ?>" required></div>
        <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Leave blank to keep same"></div>
        <div class="mb-3">
            <select name="role" class="form-control" required>
                <option <?= ($user['role']=="ADMIN"?"selected":"") ?>>ADMIN</option>
                <option <?= ($user['role']=="PR"?"selected":"") ?>>PR</option>
                <option <?= ($user['role']=="HTPO"?"selected":"") ?>>HTPO</option>
                <option <?= ($user['role']=="CPTO"?"selected":"") ?>>CPTO</option>
                <option <?= ($user['role']=="TEAM"?"selected":"") ?>>TEAM</option>
            </select>
        </div>
        <div class="mb-3"><input type="text" name="year" class="form-control" value="<?= $user['year'] ?>"></div>
        <div class="mb-3"><input type="text" name="classid" class="form-control" value="<?= $user['classid'] ?>"></div>
        <div class="mb-3"><input type="number" name="teamid" class="form-control" value="<?= $user['teamid'] ?>"></div>
        <button type="submit" name="update" class="btn btn-success">Update</button>
        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>
