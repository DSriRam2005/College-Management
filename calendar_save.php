<?php
session_start();
require_once 'db.php';

/* SHOW ERRORS */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ROLE CHECK */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    header('Location: calender_login.php');
    exit;
}

/* INPUT */
$event_date   = $_POST['event_date'] ?? '';
$prog         = trim($_POST['prog'] ?? '');
$year         = intval($_POST['year'] ?? 0);
$classids     = $_POST['classids'] ?? [];

$classtype    = $_POST['classtype'] ?? '';
$TYPE         = $_POST['TYPE'] ?? '';  // ONLINE / OFFLINE

$expert_id    = intval($_POST['expert_id'] ?? 0);

$faculty_coordinator = trim($_POST['faculty_coordinator'] ?? '');

$subject      = trim($_POST['subject'] ?? '');
$topic        = trim($_POST['topic'] ?? '');
$venue        = trim($_POST['venue'] ?? '');

$start_time   = $_POST['start_time'] ?? '';
$end_time     = $_POST['end_time'] ?? '';

$created_by   = intval($_SESSION['user_id']);

/* VALIDATION */
$errors = [];

if ($event_date == '')      $errors[] = "Select event date.";
if ($prog == '')            $errors[] = "Program missing.";
if ($year <= 0)             $errors[] = "Year missing.";
if (empty($classids))       $errors[] = "Select at least one class.";

if ($TYPE == '')            $errors[] = "Select TYPE (Online / Offline).";
if ($classtype == '')       $errors[] = "Select class type.";

if ($expert_id <= 0)        $errors[] = "Select a valid expert.";
if ($faculty_coordinator == '') $errors[] = "Enter faculty coordinator.";

if ($subject == '')         $errors[] = "Enter subject.";
if ($topic == '')           $errors[] = "Enter topic.";

if ($TYPE == 'OFFLINE' && $venue == '') {
    $errors[] = "Venue required for OFFLINE events.";
}

if ($TYPE == 'ONLINE') {
    $venue = "";   // force empty
}

if ($start_time == '')      $errors[] = "Select start time.";
if ($end_time == '')        $errors[] = "Select end time.";

if (!empty($errors)) {
    echo "<h3>ERRORS:</h3>";
    foreach ($errors as $e) {
        echo "<div style='color:red;margin-bottom:5px;'>• " . htmlspecialchars($e) . "</div>";
    }
    echo "<br><a href='calendar_add.php'>← Go Back</a>";
    exit;
}

/* CLEAN CLASSIDS */
$classids_clean = array_filter(array_map('trim', $classids));
$classids_csv   = implode(",", $classids_clean);

/* =====================================================
   DUPLICATE CHECK (DATE + EXPERT + TIME OVERLAP)
   ===================================================== */

$dup = $conn->prepare("
    SELECT id 
    FROM CLASS_CALENDAR
    WHERE date = ?
      AND expert_id = ?
      AND (
            (start_time <= ? AND end_time >= ?) OR
            (start_time <= ? AND end_time >= ?)
          )
    LIMIT 1
");

$dup->bind_param(
    "sissss",
    $event_date,
    $expert_id,
    $start_time, $start_time,
    $end_time,   $end_time
);

$dup->execute();
$resDup = $dup->get_result();

if ($resDup->num_rows > 0) {
    echo "<script>alert('Duplicate Found: This expert already has a class on this date and time.'); history.back();</script>";
    exit;
}

/* =====================================================
   INSERT (EXACTLY MATCHES DB COLUMN ORDER)
   ===================================================== */

$sql = "
INSERT INTO CLASS_CALENDAR 
(date, prog, year, classids, TYPE, classtype, expert_id, faculty_coordinator, subject, topic, venue, start_time, end_time, created_by)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

/*
14 VALUES → 14 TYPE LETTERS:
s s i s s s i s s s s s s i
*/
$stmt->bind_param(
    "ssisssissssssi",
    $event_date,
    $prog,
    $year,
    $classids_csv,
    $TYPE,
    $classtype,
    $expert_id,
    $faculty_coordinator,
    $subject,
    $topic,
    $venue,
    $start_time,
    $end_time,
    $created_by
);

/* EXECUTE */
$ok = $stmt->execute();

if (!$ok) {
    echo "<h3 style='color:red;'>DB ERROR</h3>";
    echo "Message: " . htmlspecialchars($stmt->error);
    exit;
}

/* REDIRECT */
header("Location: calendar_view.php?msg=created");
exit;

?>
