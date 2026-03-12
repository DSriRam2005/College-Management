<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

/* CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'db.php';

/* DB */
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $db = $mysqli;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} else {
    error_log("DB connection error");
    die("Database connection error.");
}

/* Role */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    error_log("Unauthorized CPTO access");
    die("Unauthorized");
}

/* Resolve user */
$user_id = null;
if (isset($_SESSION['id'])) {
    $user_id = (int)$_SESSION['id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
} elseif (isset($_SESSION['username'])) {
    $u = $db->prepare("SELECT id FROM USERS WHERE username=?");
    $u->bind_param("s", $_SESSION['username']);
    $u->execute();
    $r = $u->get_result()->fetch_assoc();
    if ($r) $user_id = (int)$r['id'];
}
if (!$user_id) die("Session expired");

$msg = "";
$error = "";

/* Helpers */
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function validPhone($p){ $p=preg_replace('/\D/','',$p); return strlen($p)>=10 && strlen($p)<=15; }

/* SAVE PHONE */
if (isset($_POST['save_profile'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error="Invalid CSRF token";
    } elseif (!validPhone($_POST['ph_no'])) {
        $error="Invalid phone number";
    } else {
        $q=$db->prepare("UPDATE USERS SET ph_no=? WHERE id=?");
        $q->bind_param("si", $_POST['ph_no'], $user_id);
        $q->execute();
        $msg="Phone number updated";
    }
}

/* CHANGE EMP ID */
if (isset($_POST['change_empid'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error="Invalid CSRF token";
    } else {
        $emp=trim($_POST['emp_id']);
        $ph=trim($_POST['ph_no']);

        $c=$db->prepare("SELECT id FROM USERS WHERE EMP_ID=? AND id!=?");
        $c->bind_param("si",$emp,$user_id);
        $c->execute();
        if ($c->get_result()->num_rows>0){
            $error="EMP ID already in use";
        } else {
            $k=$db->prepare("SELECT NAME FROM kiet_staff WHERE EMPID=?");
            $k->bind_param("s",$emp);
            $k->execute();
            $staff=$k->get_result()->fetch_assoc();

            if(!$staff){
                $error="EMP ID not found in staff records";
            } else {
                $u=$db->prepare("UPDATE USERS SET EMP_ID=?, name=?, ph_no=? WHERE id=?");
                $u->bind_param("sssi",$emp,$staff['NAME'],$ph,$user_id);
                $u->execute();
                session_regenerate_id(true);
                $msg="EMP ID updated & name synced";
            }
        }
    }
}

/* RESET PASSWORD */
if (isset($_POST['reset_password'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error="Invalid CSRF token";
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error="Passwords do not match";
    } elseif (strlen($_POST['new_password']) < 8) {
        $error="Password too short";
    } else {
        $hash=password_hash($_POST['new_password'],PASSWORD_BCRYPT);
        $p=$db->prepare("UPDATE USERS SET password=? WHERE id=?");
        $p->bind_param("si",$hash,$user_id);
        $p->execute();
        $msg="Password reset successfully";
    }
}

/* FETCH USER */
$s=$db->prepare("
SELECT EMP_ID,name,ph_no,username,classid,role,college,prog,year
FROM USERS WHERE id=?");
$s->bind_param("i",$user_id);
$s->execute();
$user=$s->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CPTO Profile</title>

<style>
:root{
--bg:#f5f7fb;--card:#fff;--text:#1f2937;--muted:#6b7280;
--primary:#2563eb;--primary-dark:#1e40af;--danger:#dc2626;
--border:#e5e7eb;--radius:10px}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui;background:var(--bg);color:var(--text)}
.container{max-width:1100px;margin:32px auto;padding:0 16px}
.grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);border-radius:var(--radius);
box-shadow:0 8px 24px rgba(0,0,0,.05);padding:24px}
h1,h2{margin:0 0 16px}
.alert{padding:12px;border-radius:8px;margin-bottom:16px}
.success{background:#ecfdf5;color:#065f46}
.error{background:#fef2f2;color:#7f1d1d}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.form-grid{grid-template-columns:1fr}}
label{font-weight:600;font-size:13px}
input{width:100%;padding:10px;border-radius:8px;border:1px solid var(--border)}
.readonly{background:#f9fafb}
.actions{margin-top:18px;display:flex;gap:10px;flex-wrap:wrap}
button{border:none;border-radius:8px;padding:10px 16px;cursor:pointer}
.primary{background:var(--primary);color:#fff}
.primary:hover{background:var(--primary-dark)}
.secondary{background:#e5e7eb}
.danger{background:var(--danger);color:#fff}
.hidden{display:none}
</style>

<script>
function enableEmpEdit(){
 let e=document.getElementById('emp_id');
 e.removeAttribute('readonly');
 e.classList.remove('readonly');
 document.getElementById('empBtn').classList.remove('hidden');
 e.focus();
}
</script>
</head>
<body>

<div class="container">
<h1>CPTO Profile</h1>

<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>

<div class="grid">

<div class="card">
<h2>Profile Information</h2>
<form method="post">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">

<div class="form-grid">
<label>EMP ID<input id="emp_id" name="emp_id" class="readonly" readonly value="<?=e($user['EMP_ID'])?>"></label>
<label>Name<input class="readonly" readonly value="<?=e($user['name'])?>"></label>
<label>Phone<input name="ph_no" value="<?=e($user['ph_no'])?>" required></label>
<label>Username<input class="readonly" readonly value="<?=e($user['username'])?>"></label>
<label>Class ID<input class="readonly" readonly value="<?=e($user['classid'])?>"></label>
<label>Role<input class="readonly" readonly value="<?=e($user['role'])?>"></label>
<label>College<input class="readonly" readonly value="<?=e($user['college'])?>"></label>
<label>Program / Year<input class="readonly" readonly value="<?=e($user['prog'].' - '.$user['year'])?>"></label>
</div>

<div class="actions">
<button type="submit" name="save_profile" class="primary">Save Phone</button>
<button type="button" onclick="enableEmpEdit()" class="secondary">Change EMP ID</button>
<button type="submit" name="change_empid" id="empBtn" class="primary hidden">Save EMP ID</button>
</div>
</form>
</div>


</form>
</div>

</div>
</div>
</body>
</html>
