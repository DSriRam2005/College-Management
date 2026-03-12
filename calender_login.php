<?php
// calender_login.php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: calendar_dashboard.php');
    exit;
}

require_once 'db.php';

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = 'Enter username and password.';
    } else {

        $sql = "SELECT id, username, password, role, name FROM USERS WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {

            $db_pass = $row['password'];

            // ONLY hashed password check — SAFE
            if (password_verify($password, $db_pass)) {

                if (in_array($row['role'], ['CALENDAR', 'ADMIN'])) {

                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['name'] = $row['name'] ?? $row['username'];

                    header('Location: calendar_dashboard.php');
                    exit;
                } 
                else {
                    $err = 'No permission.';
                }

            } else {
                $err = 'Invalid credentials.';
            }

        } else {
            $err = 'Invalid credentials.';
        }

        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Calendar Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-blue: #2c3e50;
        --secondary-blue: #3498db;
        --light-gray: #f6f8fa;
        --shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }

    * { font-family: 'Poppins', sans-serif; }

    body {
        background: linear-gradient(135deg, #e8edf3 0%, #d3dce8 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-wrapper { max-width: 420px; width: 100%; }

    .login-card {
        background: white;
        border-radius: 18px;
        padding: 35px;
        box-shadow: var(--shadow);
        animation: fadeIn 0.5s ease;
    }

    .login-title {
        font-weight: 700;
        font-size: 1.9rem;
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 5px;
    }

    .subtitle {
        text-align: center;
        font-size: 0.9rem;
        color: #6c7a8b;
        margin-bottom: 25px;
    }

    label { font-weight: 500; color: var(--primary-blue); }

    .form-control {
        border-radius: 10px;
        padding: 10px 14px;
        border: 2px solid #e1e5eb;
        transition: 0.2s;
    }

    .form-control:focus {
        border-color: var(--secondary-blue);
        box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
    }

    .btn-login {
        width: 100%;
        background: linear-gradient(90deg, var(--secondary-blue), #2980b9);
        border: none;
        padding: 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        color: white;
        transition: 0.3s;
        margin-top: 5px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(52, 152, 219, 0.4);
    }

    .footer-text { text-align: center; margin-top: 12px; color: #7f8c8d; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: none; }
    }

    .alert { border-radius: 12px; }
</style>
</head>

<body>

<div class="login-wrapper">
    <div class="login-card">
        <h2 class="login-title"><i class="bi bi-calendar-check"></i> Calendar Login</h2>
        <p class="subtitle">Authorized access for Calendar & Admin users</p>

        <?php if ($err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter your username">
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password">
            </div>

            <button class="btn-login">Login</button>
        </form>

        <div class="footer-text small mt-3">
            Access allowed for <strong>ADMIN</strong> & <strong>CALENDAR</strong> users.
        </div>
    </div>

    <div class="text-center mt-3 small text-muted">© KIET</div>
</div>

</body>
</html>
