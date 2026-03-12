<?php
session_start();
include 'db.php'; // 🔗 Database connection

$message = "";

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch user by username
    $sql = "SELECT * FROM USERS WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $user['password'])) {

            // ✅ Store session values
            $_SESSION['userid']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['year']     = $user['year'];
            $_SESSION['classid']  = $user['classid'];
            $_SESSION['teamid']   = $user['teamid'];
            $_SESSION['ZONE']     = $user['ZONE'];     // ✅ MANDATORY FIX

            // Redirect based on role
            if ($user['role'] == "ADMIN") {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == "TEAM") {
                header("Location: team_dashboard.php");
            } elseif ($user['role'] == "PR") {
                header("Location: pr_dashboard.php");
            } elseif ($user['role'] == "HTPO") {
                header("Location: htpo_dashboard.php");
            } elseif ($user['role'] == "CPTO") {
                header("Location: cpto_dashboard.php");
            } elseif ($user['role'] == "ZONE") {
                header("Location: zone_dashboard.php");
            } elseif ($user['role'] == "TPO") {
                header("Location: TPO_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $message = "❌ Invalid password!";
        }
    } else {
        $message = "❌ User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f1f3f6; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 12px; }
        h3 { font-size: 1.8rem; }
        .form-label { font-weight: 500; }
        @media (max-width: 576px) {
            .login-card { padding: 1.5rem; margin: 0 1rem; }
            h3 { font-size: 1.5rem; }
            .btn { font-size: 1rem; padding: 0.6rem 0; }
        }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

<div class="card shadow login-card">
    <h3 class="text-center mb-4">User Login</h3>
    <?php if (!empty($message)): ?>
        <div class="alert alert-danger text-center"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

</body>
</html>
