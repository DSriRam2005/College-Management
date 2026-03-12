<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    die("ACCESS DENIED");
}

if (!isset($_GET['id'])) {
    die("Invalid Event");
}

$id = (int)$_GET['id'];

/* FETCH EVENT */
$stmt = $conn->prepare("
    SELECT date, venue, TYPE, topic_covered, yt_link
    FROM CLASS_CALENDAR
    WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) die("Event not found");

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date          = $_POST['date'];
    $venue         = trim($_POST['venue']);
    $type          = trim($_POST['type']);
    $topicCovered  = trim($_POST['topic_covered']);
    $ytLink        = trim($_POST['yt_link']);

    $up = $conn->prepare("
        UPDATE CLASS_CALENDAR
        SET date=?,
            venue=?,
            TYPE=?,
            topic_covered=?,
            yt_link=?
        WHERE id=?
    ");

    $up->bind_param("sssssi", $date, $venue, $type, $topicCovered, $ytLink, $id);
    $up->execute();

    header("Location: calendar_view.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Event</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="card shadow">
<div class="card-body">

<h5 class="mb-3">Edit Event</h5>

<form method="post">

<div class="mb-3">
    <label class="form-label">Date</label>
    <input type="date" name="date" value="<?= $event['date'] ?>" class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">Venue</label>
    <input type="text" name="venue" value="<?= htmlspecialchars($event['venue']) ?>" class="form-control" required>
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="type" class="form-select" required>
        <option value="ONLINE"  <?= $event['TYPE']=='ONLINE'  ? 'selected' : '' ?>>ONLINE</option>
        <option value="OFFLINE" <?= $event['TYPE']=='OFFLINE' ? 'selected' : '' ?>>OFFLINE</option>
    </select>
</div>


<!-- NEW: Topic Covered -->
<div class="mb-3">
    <label class="form-label">Topic Covered</label>
    <textarea
        name="topic_covered"
        class="form-control"
        rows="4"
        placeholder="Enter topics covered in this session"
    ><?= htmlspecialchars($event['topic_covered']) ?></textarea>
</div>


<!-- NEW: YouTube Link -->
<div class="mb-3">
    <label class="form-label">YouTube Link</label>
    <input
        type="url"
        name="yt_link"
        value="<?= htmlspecialchars($event['yt_link']) ?>"
        class="form-control"
        placeholder="https://youtube.com/watch?v=xxxx"
    >
</div>


<div class="text-end">
    <a href="calendar_view.php" class="btn btn-secondary">Cancel</a>
    <button class="btn btn-primary">Update</button>
</div>

</form>

</div>
</div>
</div>

</body>
</html>
