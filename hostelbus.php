<?php
// login.php
session_start();
include 'db.php'; // ✅ Your DB connection

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $busname = trim($_POST['busname']);
    $password = trim($_POST['password']);

    if (!empty($busname) && !empty($password)) {
        // Fetch bus details
        $stmt = $conn->prepare("SELECT ID, PASSWORD FROM DBUSES WHERE BUSNAME = ?");
        $stmt->bind_param("s", $busname);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_pass);
            $stmt->fetch();

            // Verify password
            if (password_verify($password, $hashed_pass)) {
                $_SESSION['bus_id'] = $id;
                $_SESSION['busname'] = $busname;
                header("Location: hostelbus_dashboard.php"); // ✅ redirect after login
                exit();
            } else {
                $msg = "❌ Invalid password!";
            }
        } else {
            $msg = "❌ Bus not found!";
        }
        $stmt->close();
    } else {
        $msg = "⚠️ Please enter all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bus Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: #fff; padding: 30px; border-radius: 10px; width: 350px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .login-box h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .msg { margin: 10px 0; text-align: center; color: red; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Bus Login</h2>
        <?php if (!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>
        <form method="post">
            <input type="text" name="busname" placeholder="Bus Name" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
