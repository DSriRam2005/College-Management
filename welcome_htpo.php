<?php
session_start();
include 'db.php'; // Database connection

// ✅ Check if user is logged in and role is HTPO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

// ✅ Fetch HTPO's college/year/program details
$htpo_username = $_SESSION['username'];
$user_stmt = $conn->prepare("SELECT prog, year, college FROM USERS WHERE username = ?");
$user_stmt->bind_param("s", $htpo_username);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$prog = $user['prog'];
$year = $user['year'];
$college = $user['college'];

// ✅ Count students (excluding debarred)
$stmt_count = $conn->prepare("
    SELECT COUNT(*) AS total_students
    FROM STUDENTS
    WHERE year=? AND prog=? AND (debarred=0 OR debarred IS NULL)
      AND FIND_IN_SET(college, ?)
");
$stmt_count->bind_param("iss", $year, $prog, $college);

$stmt_count->execute();
$total_students = $stmt_count->get_result()->fetch_assoc()['total_students'] ?? 0;
// Count teams (excluding debarred) for multiple colleges
$stmt_teams = $conn->prepare("
    SELECT COUNT(DISTINCT teamid) AS total_teams 
    FROM STUDENTS 
    WHERE year=? AND prog=? AND (debarred=0 OR debarred IS NULL)
      AND FIND_IN_SET(college, ?)
      AND teamid IS NOT NULL AND teamid<>'' AND teamid<>'0'
");
$stmt_teams->bind_param("iss", $year, $prog, $college);
$stmt_teams->execute();
$total_teams = $stmt_teams->get_result()->fetch_assoc()['total_teams'] ?? 0;

// Fetch total dues including negatives
$stmt3 = $conn->prepare("
    SELECT 
        IFNULL(SUM(tfdue_12_9),0) AS tf_12_9,
        IFNULL(SUM(tfdue_today),0) AS tf_today,
        IFNULL(SUM(otdues_12_9),0) AS ot_12_9,
        IFNULL(SUM(otdues_today),0) AS ot_today,
        IFNULL(SUM(busdue_12_9),0) AS bus_12_9,
        IFNULL(SUM(busdue_today),0) AS bus_today,
        IFNULL(SUM(hosdue_12_9),0) AS hos_12_9,
        IFNULL(SUM(hosdue_today),0) AS hos_today,
        IFNULL(SUM(olddue_12_9),0) AS old_12_9,
        IFNULL(SUM(olddue_today),0) AS old_today
    FROM STUDENTS
    WHERE year=? AND prog=? AND (debarred=0 OR debarred IS NULL)
      AND FIND_IN_SET(college, ?)
");
$stmt3->bind_param("iss", $year, $prog, $college);
$stmt3->execute();
$dues = $stmt3->get_result()->fetch_assoc();

// Fetch dues excluding negatives
$stmt5 = $conn->prepare("
    SELECT 
        IFNULL(SUM(CASE WHEN tfdue_12_9>=0 THEN tfdue_12_9 ELSE 0 END),0) AS tf_12_9,
        IFNULL(SUM(CASE WHEN tfdue_today>=0 THEN tfdue_today ELSE 0 END),0) AS tf_today,
        IFNULL(SUM(CASE WHEN otdues_12_9>=0 THEN otdues_12_9 ELSE 0 END),0) AS ot_12_9,
        IFNULL(SUM(CASE WHEN otdues_today>=0 THEN otdues_today ELSE 0 END),0) AS ot_today,
        IFNULL(SUM(CASE WHEN busdue_12_9>=0 THEN busdue_12_9 ELSE 0 END),0) AS bus_12_9,
        IFNULL(SUM(CASE WHEN busdue_today>=0 THEN busdue_today ELSE 0 END),0) AS bus_today,
        IFNULL(SUM(CASE WHEN hosdue_12_9>=0 THEN hosdue_12_9 ELSE 0 END),0) AS hos_12_9,
        IFNULL(SUM(CASE WHEN hosdue_today>=0 THEN hosdue_today ELSE 0 END),0) AS hos_today,
        IFNULL(SUM(CASE WHEN olddue_12_9>=0 THEN olddue_12_9 ELSE 0 END),0) AS old_12_9,
        IFNULL(SUM(CASE WHEN olddue_today>=0 THEN olddue_today ELSE 0 END),0) AS old_today
    FROM STUDENTS
    WHERE year=? AND prog=? AND (debarred=0 OR debarred IS NULL)
      AND FIND_IN_SET(college, ?)
");
$stmt5->bind_param("iss", $year, $prog, $college);
$stmt5->execute();
$dues_non_negative = $stmt5->get_result()->fetch_assoc();

// ✅ Compute paid amounts = (12_9 - today)
$paid_by_diff = [
    "tf"  => max(0, $dues_non_negative['tf_12_9'] - $dues_non_negative['tf_today']),
    "ot"  => max(0, $dues_non_negative['ot_12_9'] - $dues_non_negative['ot_today']),
    "bus" => max(0, $dues_non_negative['bus_12_9'] - $dues_non_negative['bus_today']),
    "hos" => max(0, $dues_non_negative['hos_12_9'] - $dues_non_negative['hos_today']),
    "old" => max(0, $dues_non_negative['old_12_9'] - $dues_non_negative['old_today']),
];

function format_currency($val) { return '₹ '.number_format($val); }
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HTPO Dashboard</title>
<style>
body {
    font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 25px;
    background: #f5f7fa;
    color: #2d3436;
    line-height: 1.6;
}
h1 { font-size: 2.4rem; text-align: center; color: #2d3436; margin-bottom: 15px; font-weight: 700; }
p { text-align: center; color: #636e72; margin-bottom: 30px; }

.cards { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; margin-top: 20px; }
.card {
    background: linear-gradient(135deg, #ffffff 0%, #f0f3f7 100%);
    flex: 1 1 280px; max-width: 320px; min-width: 250px;
    padding: 25px; border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    text-align: center; text-decoration: none; color: #2d3436;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover { transform: translateY(-5px); box-shadow: 0 14px 28px rgba(0,0,0,0.15); }
.card h3 { margin-bottom: 15px; color: #0984e3; font-size: 1.2rem; font-weight: 600; }
.card p { font-size: 2rem; font-weight: 700; }

.tables-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; margin-top: 40px; }
.table-card {
    flex: 1 1 400px; min-width: 320px; max-width: 550px;
    background: #ffffff; padding: 25px; border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    overflow-x: auto; transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.table-card:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(0,0,0,0.12); }
.table-card h3 { text-align: center; color: #0984e3; font-size: 1.4rem; margin-bottom: 20px; font-weight: 600; }
.table-card table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.table-card th, .table-card td { border: 1px solid #dfe6e9; padding: 12px 10px; text-align: center; }
.table-card th { background: #0984e3; color: #ffffff; font-weight: 600; }
.table-card tr:nth-child(even) { background: #f1f2f6; }
.table-card tr:hover { background: #dfe6e9; }
.table-card .total-row { background: #2d3436; color: #ffffff; font-weight: 700; }

@media (max-width: 768px) {
    .cards, .tables-container { flex-direction: column; align-items: center; }
    .card, .table-card { max-width: 90%; }
}
</style>
</head>
<body>

<h1>HTPO Dashboard</h1>
<p>Program: <b><?= htmlspecialchars($prog) ?></b> | Year: <?= htmlspecialchars($year) ?> | College: <b><?= htmlspecialchars($college) ?></b></p>

<div class="cards">
    <div class="card">
        <h3>Total Students</h3>
        <p><?= $total_students ?></p>
    </div>
    <div class="card">
        <h3>Total Teams</h3>
        <p><?= $total_teams ?></p>
    </div>
</div>

<div class="tables-container">

    <div class="table-card">
        <h3>Consolidated Dues (Including Negatives)</h3>
        <table>
            <tr><th>Category</th><th>ON 26-11-2025</th><th>Today</th><th>Paid (Diff)</th></tr>
            <tr><td>Tuition Fee</td><td><?= format_currency($dues['tf_12_9']) ?></td><td><?= format_currency($dues['tf_today']) ?></td><td><?= format_currency($dues['tf_12_9'] - $dues['tf_today']) ?></td></tr>
            <tr><td>Other Fee</td><td><?= format_currency($dues['ot_12_9']) ?></td><td><?= format_currency($dues['ot_today']) ?></td><td><?= format_currency($dues['ot_12_9'] - $dues['ot_today']) ?></td></tr>
            <tr><td>Bus Fee</td><td><?= format_currency($dues['bus_12_9']) ?></td><td><?= format_currency($dues['bus_today']) ?></td><td><?= format_currency($dues['bus_12_9'] - $dues['bus_today']) ?></td></tr>
            <tr><td>Hostel Fee</td><td><?= format_currency($dues['hos_12_9']) ?></td><td><?= format_currency($dues['hos_today']) ?></td><td><?= format_currency($dues['hos_12_9'] - $dues['hos_today']) ?></td></tr>
            <tr><td>Old Dues</td><td><?= format_currency($dues['old_12_9']) ?></td><td><?= format_currency($dues['old_today']) ?></td><td><?= format_currency($dues['old_12_9'] - $dues['old_today']) ?></td></tr>
            <tr class="total-row">
                <td>Total</td>
                <td><?= format_currency($dues['tf_12_9']+$dues['ot_12_9']+$dues['bus_12_9']+$dues['hos_12_9']+$dues['old_12_9']) ?></td>
                <td><?= format_currency($dues['tf_today']+$dues['ot_today']+$dues['bus_today']+$dues['hos_today']+$dues['old_today']) ?></td>
                <td><?= format_currency(($dues['tf_12_9']-$dues['tf_today'])+($dues['ot_12_9']-$dues['ot_today'])+($dues['bus_12_9']-$dues['bus_today'])+($dues['hos_12_9']-$dues['hos_today'])+($dues['old_12_9']-$dues['old_today'])) ?></td>
            </tr>
        </table>
    </div>

    <div class="table-card">
        <h3>Dues Up to This Sem (Negatives Ignored)</h3>
        <table>
            <tr><th>Category</th><th>ON 26-11-2025 (No Negatives)</th><th>Today (No Negatives)</th><th>Paid (Computed)</th></tr>
            <tr><td>Tuition Fee</td><td><?= format_currency($dues_non_negative['tf_12_9']) ?></td><td><?= format_currency($dues_non_negative['tf_today']) ?></td><td><?= format_currency($paid_by_diff['tf']) ?></td></tr>
            <tr><td>Other Fee</td><td><?= format_currency($dues_non_negative['ot_12_9']) ?></td><td><?= format_currency($dues_non_negative['ot_today']) ?></td><td><?= format_currency($paid_by_diff['ot']) ?></td></tr>
            <tr><td>Bus Fee</td><td><?= format_currency($dues_non_negative['bus_12_9']) ?></td><td><?= format_currency($dues_non_negative['bus_today']) ?></td><td><?= format_currency($paid_by_diff['bus']) ?></td></tr>
            <tr><td>Hostel Fee</td><td><?= format_currency($dues_non_negative['hos_12_9']) ?></td><td><?= format_currency($dues_non_negative['hos_today']) ?></td><td><?= format_currency($paid_by_diff['hos']) ?></td></tr>
            <tr><td>Old Dues</td><td><?= format_currency($dues_non_negative['old_12_9']) ?></td><td><?= format_currency($dues_non_negative['old_today']) ?></td><td><?= format_currency($paid_by_diff['old']) ?></td></tr>
            <tr class="total-row">
                <td>Total</td>
                <td><?= format_currency($dues_non_negative['tf_12_9']+$dues_non_negative['ot_12_9']+$dues_non_negative['bus_12_9']+$dues_non_negative['hos_12_9']+$dues_non_negative['old_12_9']) ?></td>
                <td><?= format_currency($dues_non_negative['tf_today']+$dues_non_negative['ot_today']+$dues_non_negative['bus_today']+$dues_non_negative['hos_today']+$dues_non_negative['old_today']) ?></td>
                <td><?= format_currency(array_sum($paid_by_diff)) ?></td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
