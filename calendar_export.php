<?php
session_start();
require_once 'db.php';

/* ================= ACCESS CHECK ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    die("ACCESS DENIED");
}

/* ================= USER FILTER DATA ================= */
$user_id = (int)$_SESSION['user_id'];

$q = $conn->prepare("SELECT prog, year FROM USERS WHERE id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$user = $q->get_result()->fetch_assoc();
$q->close();

$user_prog = $user['prog'] ?? '';
$user_year = $user['year'] ?? '';

/* ================= BASE QUERY ================= */
$sql = "
    SELECT 
        c.date,
        c.subject,
        c.topic,
        c.prog,
        c.year,
        c.start_time,
        c.end_time,
        c.classtype,
        c.TYPE,
        c.venue,
        c.classids,
        c.faculty_coordinator,
        e.expert_name,
        u.username AS created_by
    FROM CLASS_CALENDAR c
    LEFT JOIN USERS u   ON u.id = c.created_by
    LEFT JOIN EXPERTS e ON e.expert_id = c.expert_id
";

/* ================= ROLE BASED FILTER ================= */
if ($_SESSION['role'] !== 'ADMIN') {

    if (!empty($user_prog) && !empty($user_year)) {
        $sql .= " WHERE c.prog = '$user_prog' AND c.year = '$user_year'";
    } elseif (!empty($user_prog)) {
        $sql .= " WHERE c.prog = '$user_prog'";
    }
}

/* ================= ORDER ================= */
$sql .= " ORDER BY c.date DESC, c.id DESC";

$res = $conn->query($sql);

/* ================= CSV HEADERS ================= */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=calendar_events.csv');

$output = fopen('php://output', 'w');

/* ================= CSV COLUMN TITLES ================= */
fputcsv($output, [
    'Date',
    'Subject',
    'Topic',
    'Program',
    'Year',
    'Start Time',
    'End Time',
    'Class Type',
    'Mode',
    'Venue',
    'Class IDs',
    'Coordinator',
    'Expert Name',
    'Created By'
]);

/* ================= CSV DATA ================= */
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        fputcsv($output, [
            $row['date'],
            $row['subject'],
            $row['topic'],
            $row['prog'],
            $row['year'],
            $row['start_time'],
            $row['end_time'],
            $row['classtype'],
            $row['TYPE'],
            $row['venue'],
            $row['classids'],
            $row['faculty_coordinator'],
            $row['expert_name'],
            $row['created_by']
        ]);
    }
}

fclose($output);
exit;
