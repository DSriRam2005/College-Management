<?php
session_start();
include 'db.php';

// Enable FULL MySQL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Restrict access
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: index.php");
    exit;
}

$message = "";

// ---------------- FETCH DISTINCT VALUES ----------------
$colleges = [];
$programs = [];
$years = [];

$college_res = $conn->query("SELECT DISTINCT college FROM STUDENTS WHERE college IS NOT NULL AND college<>'' ORDER BY college");
while ($row = $college_res->fetch_assoc()) $colleges[] = $row['college'];

$prog_res = $conn->query("SELECT DISTINCT prog FROM STUDENTS WHERE prog IS NOT NULL AND prog<>'' ORDER BY prog");
while ($row = $prog_res->fetch_assoc()) $programs[] = $row['prog'];

$year_res = $conn->query("SELECT DISTINCT year FROM STUDENTS WHERE year IS NOT NULL AND year<>'' ORDER BY year");
while ($row = $year_res->fetch_assoc()) $years[] = $row['year'];

// ---------------- BULK CSV UPLOAD ----------------
if (isset($_POST['upload_csv'])) {
    if ($_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $file = fopen($filename, "r");

        while (($row = fgetcsv($file)) !== FALSE) {

            if (count($row) < 3) continue; 

            $username = trim($row[0]);
            $password = password_hash(trim($row[1]), PASSWORD_BCRYPT);
            $role = trim($row[2]);

            $college = !empty($row[3]) ? trim($row[3]) : NULL;
            $prog = !empty($row[4]) ? trim($row[4]) : NULL;
            $year = !empty($row[5]) ? (int)$row[5] : NULL;
            $classid = !empty($row[6]) ? trim($row[6]) : NULL;
            $teamid = !empty($row[7]) ? trim($row[7]) : NULL;

            $sql = "INSERT INTO USERS (username, password, role, college, prog, year, classid, teamid)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiss", $username, $password, $role, $college, $prog, $year, $classid, $teamid);
            $stmt->execute();
        }
        fclose($file);

        $message = "Users uploaded successfully!";
    } else {
        $message = "Error uploading CSV file!";
    }
}

// ---------------- ADD USER MANUALLY ----------------
if (isset($_POST['add_user'])) {

    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $college = !empty($_POST['college']) ? trim($_POST['college']) : NULL;
    $prog = !empty($_POST['prog']) ? trim($_POST['prog']) : NULL;
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : NULL;
    $classid = !empty($_POST['classid']) ? trim($_POST['classid']) : NULL;
    $teamid = !empty($_POST['teamid']) ? trim($_POST['teamid']) : NULL;

    $sql = "INSERT INTO USERS (username, password, role, college, prog, year, classid, teamid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Correct bind_param format
    $stmt->bind_param("sssssiss", $username, $password, $role, $college, $prog, $year, $classid, $teamid);

    $stmt->execute();

    $message = "User added successfully!";
}

// ---------------- DELETE USER ----------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM USERS WHERE id = $id");
    $message = "User deleted!";
}

// ---------------- FETCH USERS ----------------
$result = $conn->query("SELECT * FROM USERS ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
    <h3>Manage Users</h3>

    <?php if (!empty($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

    <!-- Upload CSV -->
    <div class="card p-3 mb-3">
        <h5>Bulk Upload Users (CSV)</h5>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" class="form-control mb-2" required>
            <button type="submit" name="upload_csv" class="btn btn-success">Upload</button>
        </form>
        <small>CSV: username,password,role,college,prog,year,classid,teamid</small>
    </div>

    <!-- Add User Manually -->
    <div class="card p-3 mb-3">
        <h5>Add User</h5>
        <form method="POST">
            <div class="row g-2">

                <div class="col"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
                <div class="col"><input type="password" name="password" class="form-control" placeholder="Password" required></div>

                <div class="col">
                    <select name="role" class="form-control" required>
                        <option>ADMIN</option>
                        <option>PR</option>
                        <option>TPO</option>
                        <option>HTPO</option>
                        <option>CPTO</option>
                        <option>TEAM</option>
                    </select>
                </div>

                <div class="col">
                    <select name="college" class="form-control" required>
                        <option value="">Select College</option>
                        <?php foreach ($colleges as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col">
                    <select name="prog" class="form-control" required>
                        <option value="">Select Program</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col">
                    <select name="year" class="form-control" required>
                        <option value="">Select Year</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col"><input type="text" name="classid" class="form-control" placeholder="ClassID"></div>
                <div class="col"><input type="text" name="teamid" class="form-control" placeholder="TeamID"></div>

                <div class="col">
                    <button type="submit" name="add_user" class="btn btn-primary">Add</button>
                </div>

            </div>
        </form>
    </div>

    <!-- User List -->
    <h5>User List</h5>
    <table class="table table-bordered table-sm">
        <thead class="table-dark">
            <tr>
                <th>S.No</th>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>College</th>
                <th>Program</th>
                <th>Year</th>
                <th>ClassID</th>
                <th>TeamID</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

        <?php 
        $sno = 1;
        while ($row = $result->fetch_assoc()):
        ?>
            <tr>
                <td><?= $sno++ ?></td>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['role']) ?></td>
                <td><?= htmlspecialchars($row['college']) ?></td>
                <td><?= htmlspecialchars($row['prog']) ?></td>
                <td><?= htmlspecialchars($row['year']) ?></td>
                <td><?= htmlspecialchars($row['classid']) ?></td>
                <td><?= htmlspecialchars($row['teamid']) ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>

        </tbody>
    </table>

</body>
</html>
