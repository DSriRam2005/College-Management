<?php
session_start();
include 'db.php';

// ✅ Allow only CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$classid = $_SESSION['classid'] ?? "";

// ✅ Count students (ignore debarred)
$stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM STUDENTS WHERE classid=? AND (debarred=0 OR debarred IS NULL)");
$stmt->bind_param("s", $classid);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$total_students = $res['total_students'] ?? 0;

// ✅ Count distinct teams (ignore debarred)
$stmt3 = $conn->prepare("
    SELECT COUNT(DISTINCT teamid) AS total_teams 
    FROM STUDENTS 
    WHERE classid=? 
      AND teamid IS NOT NULL 
      AND teamid <> '' 
      AND teamid <> '0'
      AND (debarred=0 OR debarred IS NULL)
");
$stmt3->bind_param("s", $classid);
$stmt3->execute();
$res3 = $stmt3->get_result()->fetch_assoc();
$total_teams = $res3['total_teams'] ?? 0;

// ✅ Fetch category-wise dues (ignore debarred)
$stmt4 = $conn->prepare("
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
    WHERE classid=? AND (debarred=0 OR debarred IS NULL)
");
$stmt4->bind_param("s", $classid);
$stmt4->execute();
$dues = $stmt4->get_result()->fetch_assoc();

// ✅ Payments made for this class (ignore debarred)
$stmt5 = $conn->prepare("
    SELECT 
        IFNULL(SUM(paid_tf),0) AS tf_paid,
        IFNULL(SUM(paid_ot),0) AS ot_paid,
        IFNULL(SUM(paid_bus),0) AS bus_paid,
        IFNULL(SUM(paid_hos),0) AS hos_paid,
        IFNULL(SUM(paid_old),0) AS old_paid
    FROM PAYMENTS
    WHERE htno IN (SELECT htno FROM STUDENTS WHERE classid=? AND (debarred=0 OR debarred IS NULL))
");
$stmt5->bind_param("s", $classid);
$stmt5->execute();
$paid = $stmt5->get_result()->fetch_assoc();

// ✅ Fetch non-negative category-wise dues (ignore debarred)
$stmt6 = $conn->prepare("
    SELECT 
        IFNULL(SUM(CASE WHEN tfdue_12_9 >= 0 THEN tfdue_12_9 ELSE 0 END),0) AS tf_12_9,
        IFNULL(SUM(CASE WHEN tfdue_today >= 0 THEN tfdue_today ELSE 0 END),0) AS tf_today,
        IFNULL(SUM(CASE WHEN otdues_12_9 >= 0 THEN otdues_12_9 ELSE 0 END),0) AS ot_12_9,
        IFNULL(SUM(CASE WHEN otdues_today >= 0 THEN otdues_today ELSE 0 END),0) AS ot_today,
        IFNULL(SUM(CASE WHEN busdue_12_9 >= 0 THEN busdue_12_9 ELSE 0 END),0) AS bus_12_9,
        IFNULL(SUM(CASE WHEN busdue_today >= 0 THEN busdue_today ELSE 0 END),0) AS bus_today,
        IFNULL(SUM(CASE WHEN hosdue_12_9 >= 0 THEN hosdue_12_9 ELSE 0 END),0) AS hos_12_9,
        IFNULL(SUM(CASE WHEN hosdue_today >= 0 THEN hosdue_today ELSE 0 END),0) AS hos_today,
        IFNULL(SUM(CASE WHEN olddue_12_9 >= 0 THEN olddue_12_9 ELSE 0 END),0) AS old_12_9,
        IFNULL(SUM(CASE WHEN olddue_today >= 0 THEN olddue_today ELSE 0 END),0) AS old_today
    FROM STUDENTS
    WHERE classid=? AND (debarred=0 OR debarred IS NULL)
");
$stmt6->bind_param("s", $classid);
$stmt6->execute();
$dues_non_negative = $stmt6->get_result()->fetch_assoc();

// ✅ Calculate paid = 12_9 - Today (for second table)
$paid_by_diff = [
    "tf"  => max(0, $dues_non_negative['tf_12_9'] - $dues_non_negative['tf_today']),
    "ot"  => max(0, $dues_non_negative['ot_12_9'] - $dues_non_negative['ot_today']),
    "bus" => max(0, $dues_non_negative['bus_12_9'] - $dues_non_negative['bus_today']),
    "hos" => max(0, $dues_non_negative['hos_12_9'] - $dues_non_negative['hos_today']),
    "old" => max(0, $dues_non_negative['old_12_9'] - $dues_non_negative['old_today']),
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome CTPO</title>
<style>
    body {font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;margin: 0;padding: 25px;background: #f5f7fa;color: #2d3436;line-height: 1.6;}
    h1 {font-size: 2.4rem;margin-bottom: 15px;color: #2d3436;text-align: center;font-weight: 700;}
    p {text-align: center;margin-bottom: 30px;color: #636e72;font-size: 1rem;}
    .cards {display: flex;flex-wrap: wrap;justify-content: center;gap: 25px;margin-top: 20px;}
    .card {background: linear-gradient(135deg, #ffffff 0%, #f0f3f7 100%);flex: 1 1 280px;max-width: 320px;min-width: 250px;padding: 25px;border-radius: 15px;box-shadow: 0 10px 20px rgba(0,0,0,0.08);text-align: center;text-decoration: none;color: #2d3436;transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .card:hover {transform: translateY(-5px);box-shadow: 0 14px 28px rgba(0,0,0,0.15);}
    .card h3 {margin-bottom: 15px;color: #0984e3;font-size: 1.2rem;font-weight: 600;}
    .card p {font-size: 2rem;font-weight: 700;}
    .tables-container {display: flex;flex-wrap: wrap;justify-content: center;gap: 25px;margin-top: 40px;}
    .table-card {flex: 1 1 400px;min-width: 320px;max-width: 550px;background: #ffffff;padding: 25px;border-radius: 15px;box-shadow: 0 10px 20px rgba(0,0,0,0.08);overflow-x: auto;transition: transform 0.2s ease, box-shadow 0.2s ease;}
    .table-card:hover {transform: translateY(-3px);box-shadow: 0 14px 28px rgba(0,0,0,0.12);}
    .table-card h3 {text-align: center;color: #0984e3;font-size: 1.4rem;margin-bottom: 20px;font-weight: 600;}
    .table-card table {width: 100%;border-collapse: collapse;font-size: 0.95rem;}
    .table-card th, .table-card td {border: 1px solid #dfe6e9;padding: 12px 10px;text-align: center;}
    .table-card th {background: #0984e3;color: #ffffff;font-weight: 600;}
    .table-card tr:nth-child(even) {background: #f1f2f6;}
    .table-card tr:hover {background: #dfe6e9;}
    .table-card .total-row {background: #2d3436;color: #ffffff;font-weight: 700;}
    @media (max-width: 768px) {.cards, .tables-container {flex-direction: column;align-items: center;}.card, .table-card {max-width: 90%;}}
</style>
</head>
<body>
    <h1>Welcome, CTPO!</h1>
    <p>This is your dashboard. From here you can manage students, view dues, and generate reports.</p>

    <div class="cards">
        <a href="ctpo_students.php" class="card">
            <h3>Students</h3>
            <p><?= $total_students ?></p>
        </a>
        <div class="card">
            <h3>Teams</h3>
            <p><?= $total_teams ?></p>
        </div>
    </div>

    <!-- ✅ Side-by-side tables -->
    <div class="tables-container">

        <!-- Full Dues & Payments Table -->
        

        <!-- Non-Negative Dues & Paid by Difference Table -->
        <div class="table-card">
            <h3>Dues Upto This Sem</h3>
            <table>
                <tr>
                    <th>Category</th>
                    <th>ON 19-01-2026</th>
                    <th>Today</th>
                    <th>Paid (19-01-2026 - Today)</th>
                </tr>
                <tr><td>Tuition Fee</td><td>₹ <?= number_format($dues_non_negative['tf_12_9']) ?></td><td>₹ <?= number_format($dues_non_negative['tf_today']) ?></td><td>₹ <?= number_format($paid_by_diff['tf']) ?></td></tr>
                <tr><td>Other Fee</td><td>₹ <?= number_format($dues_non_negative['ot_12_9']) ?></td><td>₹ <?= number_format($dues_non_negative['ot_today']) ?></td><td>₹ <?= number_format($paid_by_diff['ot']) ?></td></tr>
                <tr><td>Bus Fee</td><td>₹ <?= number_format($dues_non_negative['bus_12_9']) ?></td><td>₹ <?= number_format($dues_non_negative['bus_today']) ?></td><td>₹ <?= number_format($paid_by_diff['bus']) ?></td></tr>
                <tr><td>Hostel Fee</td><td>₹ <?= number_format($dues_non_negative['hos_12_9']) ?></td><td>₹ <?= number_format($dues_non_negative['hos_today']) ?></td><td>₹ <?= number_format($paid_by_diff['hos']) ?></td></tr>
                <tr><td>Old Dues</td><td>₹ <?= number_format($dues_non_negative['old_12_9']) ?></td><td>₹ <?= number_format($dues_non_negative['old_today']) ?></td><td>₹ <?= number_format($paid_by_diff['old']) ?></td></tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td>₹ <?= number_format($dues_non_negative['tf_12_9'] + $dues_non_negative['ot_12_9'] + $dues_non_negative['bus_12_9'] + $dues_non_negative['hos_12_9'] + $dues_non_negative['old_12_9']) ?></td>
                    <td>₹ <?= number_format($dues_non_negative['tf_today'] + $dues_non_negative['ot_today'] + $dues_non_negative['bus_today'] + $dues_non_negative['hos_today'] + $dues_non_negative['old_today']) ?></td>
                    <td>₹ <?= number_format($paid_by_diff['tf'] + $paid_by_diff['ot'] + $paid_by_diff['bus'] + $paid_by_diff['hos'] + $paid_by_diff['old']) ?></td>
                </tr>
            </table>
        </div>

    </div>
</body>
</html>
