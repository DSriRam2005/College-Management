<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "db.php";

$error = "";

if (isset($_POST['login'])) {

    $empid = trim($_POST['empid'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($empid === '' || $pass === '') {
        $error = "EMPID and Password required";
    } else {

        $q = $conn->prepare("
            SELECT EMPID, NAME, password
            FROM kiet_staff
            WHERE EMPID = ?
            LIMIT 1
        ");

        if (!$q) {
            die("Prepare failed: " . $conn->error);
        }

        $q->bind_param("i", $empid);
        $q->execute();
        $res = $q->get_result();

        if ($row = $res->fetch_assoc()) {

            if ($row['password'] === NULL) {
                $error = "Password not generated. Contact admin.";
            }
            elseif (password_verify($pass, $row['password'])) {

                // ✅ LOGIN SUCCESS
                $_SESSION['role']  = 'STPO';
                $_SESSION['empid'] = $row['EMPID'];
                $_SESSION['name']  = $row['NAME'];

                header("Location: stpo_dashboard.php");
                exit;

            } else {
                $error = "Invalid password";
            }

        } else {
            $error = "EMPID not found";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>STPO Login</title>
<meta charset="utf-8">
<style>
body{
    font-family:Arial;
    background:#f1f5f9;
    display:flex;
    align-items:center;
    justify-content:center;
    height:100vh;
}
.box{
    background:#fff;
    padding:30px;
    width:350px;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
}
input{
    width:100%;
    padding:10px;
    margin-bottom:15px;
}
button{
    width:100%;
    padding:10px;
    background:#2563eb;
    color:#fff;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
.error{
    color:#b91c1c;
    text-align:center;
    margin-bottom:10px;
}
</style>
</head>

<body>
<div class="box">
<h2 style="text-align:center">STPO Login</h2>

<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <input type="text" name="empid" placeholder="EMPID" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
</form>
</div>
</body>
</html>
