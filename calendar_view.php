<?php
session_start();
require_once 'db.php';

/* SHOW ERRORS */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ROLE CHECK */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    header("Location: calender_login.php");
    exit;
}

/* SUCCESS MESSAGES */
$msg = "";
if (isset($_GET['msg']) && $_GET['msg'] == "created") $msg = "Event created successfully.";
if (isset($_GET['msg']) && $_GET['msg'] == "deleted") $msg = "Event deleted.";

/* FETCH USER PROG + YEAR */
$user_id = $_SESSION['user_id'];
$q = $conn->prepare("SELECT prog, year FROM USERS WHERE id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$user_data = $q->get_result()->fetch_assoc();

$user_prog = $user_data['prog'] ?? '';
$user_year = $user_data['year'] ?? '';

/* BASE QUERY */
$base_sql = "
    SELECT c.*, 
           u.username AS created_user,
           e.expert_name,
           e.expert_qualification,
           e.expert_experience,
           e.expert_from,
           e.expert_phone,
           e.expert_photo
    FROM CLASS_CALENDAR c
    LEFT JOIN USERS u ON u.id = c.created_by
    LEFT JOIN EXPERTS e ON e.expert_id = c.expert_id
";

/* =======================================================
   TODAY EVENTS QUERY
   ======================================================= */

$today = date('Y-m-d');

if ($_SESSION['role'] === "ADMIN") {

    $today_sql = $base_sql . "
        WHERE c.date = '$today'
        ORDER BY c.start_time ASC, c.id ASC
    ";

} else {

    if (!empty($user_prog) && !empty($user_year)) {

        $today_sql = $base_sql . "
            WHERE c.date='$today'
              AND c.prog='$user_prog'
              AND c.year='$user_year'
            ORDER BY c.start_time ASC, c.id ASC
        ";

    } elseif (!empty($user_prog)) {

        $today_sql = $base_sql . "
            WHERE c.date='$today'
              AND c.prog='$user_prog'
            ORDER BY c.start_time ASC, c.id ASC
        ";

    } else {

        $today_sql = $base_sql . "
            WHERE c.date='$today'
            ORDER BY c.start_time ASC, c.id ASC
        ";
    }
}

$today_res = $conn->query($today_sql);


/* =======================================================
   ALL EVENTS QUERY (EXISTING)
   ======================================================= */

if ($_SESSION['role'] === "ADMIN") {

    $query = $base_sql . "
        ORDER BY c.date DESC, c.id DESC
    ";

} else {

    if (!empty($user_prog) && !empty($user_year)) {

        $query = $base_sql . "
            WHERE c.prog = '$user_prog'
              AND c.year = '$user_year'
            ORDER BY c.date DESC, c.id DESC
        ";

    } elseif (!empty($user_prog)) {

        $query = $base_sql . "
            WHERE c.prog = '$user_prog'
            ORDER BY c.date DESC, c.id DESC
        ";

    } else {

        $query = $base_sql . "
            ORDER BY c.date DESC, c.id DESC
        ";
    }
}

$res = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Calendar Events</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { font-family: 'Poppins', sans-serif; }
body { background: #eef2f7; }

.event-card {
    border-radius:16px;
    box-shadow:0 8px 25px rgba(0,0,0,0.10);
    border:none;
    transition:.2s;
}
.event-card:hover {
    transform:translateY(-4px);
}

.today-card {
    border:2px solid #ffc107;
    background:#fff8e1;
}

.badge-classid {
    background:#e8edf4;
    padding:6px 10px;
    border-radius:8px;
    margin:3px;
}

.page-header {
    background:white;
    padding:18px 25px;
    border-radius:14px;
    margin-bottom:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.10);
}

.expert-box {
    background:#f5f7fb;
    border-radius:10px;
    padding:12px;
}

.expert-photo {
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:10px;
}

@media print {
    .no-print { display:none; }
}
</style>
</head>

<body>

<div class="container mt-4">

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- HEADER -->
<div class="page-header d-flex justify-content-between align-items-center no-print">
    <h4><i class="fas fa-list-alt me-2"></i>Calendar Events</h4>

    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            Print
        </button>
        <a href="calendar_export.php" class="btn btn-outline-success btn-sm">
            Download CSV
        </a>
    </div>
</div>


<!-- ======================================================
     TODAY EVENTS SECTION
     ====================================================== -->
<?php if ($today_res && $today_res->num_rows > 0): ?>

<div class="page-header bg-warning-subtle fw-bold">
    ⭐ Today's Events (<?= date("d M Y") ?>)
</div>

<?php while ($row = $today_res->fetch_assoc()):
    $classids = explode(",", $row['classids']);
?>

<div class="card event-card today-card mb-3">
    <div class="card-body">

        <h6>
            <?= htmlspecialchars($row['subject']) ?> — <?= htmlspecialchars($row['topic']) ?>
        </h6>

        <small>
            <?= substr($row['start_time'],0,5) ?> to <?= substr($row['end_time'],0,5) ?>
            | <?= htmlspecialchars($row['venue']) ?>
            | <?= htmlspecialchars($row['TYPE']) ?>
        </small>

        <div class="mt-2 no-print">
            <a href="calendar_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
            <a href="calendar_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
        </div>

    </div>
</div>

<?php endwhile; ?>
<?php endif; ?>


<!-- ======================================================
     ALL EVENTS SECTION (EXISTING)
     ====================================================== -->

<?php
if ($res && $res->num_rows > 0):
while ($row = $res->fetch_assoc()):
$classids = explode(",", $row['classids']);
?>

<div class="card event-card mb-4">
<div class="card-body">

<h5><?= htmlspecialchars($row['subject']) ?> — <?= htmlspecialchars($row['topic']) ?></h5>

<small>
Date: <?= date("d M Y", strtotime($row['date'])) ?> |
Time: <?= substr($row['start_time'],0,5) ?> to <?= substr($row['end_time'],0,5) ?> |
Venue: <?= htmlspecialchars($row['venue']) ?>
</small>

<div class="mt-2">
<?php foreach ($classids as $cid): ?>
<span class="badge-classid"><?= htmlspecialchars($cid) ?></span>
<?php endforeach; ?>
</div>

<div class="mt-2 no-print">
<a href="calendar_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
<a href="calendar_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
</div>

</div>
</div>

<?php endwhile; endif; ?>

</div>
</body>
</html>
