<?php
session_start();
include 'db.php'; // your mysqli connection

// ✅ Check if ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: index.php");
    exit();
}

// Fetch counts
$studentCount = $conn->query("SELECT COUNT(*) as total FROM STUDENTS")->fetch_assoc()['total'];
$cpCount = $conn->query("SELECT COUNT(*) as total FROM USERS WHERE role='CPTO'")->fetch_assoc()['total'];
$teamCount = $conn->query("SELECT COUNT(*) as total FROM USERS WHERE role='TEAM'")->fetch_assoc()['total'];

// Fetch sum of dues
$sumQuery = "
SELECT 
    SUM(tfdue_12_9) as tf_12_9,
    SUM(tfdue_today) as tf_today,
    SUM(otdues_12_9) as ot_12_9,
    SUM(otdues_today) as ot_today,
    SUM(busdue_12_9) as bus_12_9,
    SUM(busdue_today) as bus_today,
    SUM(hosdue_12_9) as hos_12_9,
    SUM(hosdue_today) as hos_today,
    SUM(olddue_12_9) as old_12_9,
    SUM(olddue_today) as old_today
FROM STUDENTS
";
$sum = $conn->query($sumQuery)->fetch_assoc();

// Calculate grand totals
$grand_total_12_9 = $sum['tf_12_9'] + $sum['ot_12_9'] + $sum['bus_12_9'] + $sum['hos_12_9'] + $sum['old_12_9'];
$grand_total_today = $sum['tf_today'] + $sum['ot_today'] + $sum['bus_today'] + $sum['hos_today'] + $sum['old_today'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.card { margin: 15px 0; }
.card h3 { font-size: 1.5rem; }
.due-table td, .due-table th { padding: 8px 12px; text-align: center; }
.due-table th { background-color: #343a40; color: white; }
.due-table tfoot td { font-weight: bold; background-color: #f8f9fa; }
</style>
</head>
<body>
<div class="container mt-4">
    <h2>Admin Dashboard</h2>
    <div class="row">

        <!-- Students Card -->
        <div class="col-md-4 col-sm-6">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h3><?= $studentCount ?></h3>
                    <p>Students</p>
                </div>
            </div>
        </div>

        <!-- CPTO Card -->
        <div class="col-md-4 col-sm-6">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h3><?= $cpCount ?></h3>
                    <p>CPTO</p>
                </div>
            </div>
        </div>

        <!-- TEAM Card -->
        <div class="col-md-4 col-sm-6">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h3><?= $teamCount ?></h3>
                    <p>Teams</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Total Dues Table -->
    <div class="row mt-4">
        <div class="col-12">
            <h4>Total Dues</h4>
            <table class="table table-bordered due-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>12_9</th>
                        <th>Today</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>TF</td>
                        <td><?= number_format($sum['tf_12_9'],2) ?></td>
                        <td><?= number_format($sum['tf_today'],2) ?></td>
                    </tr>
                    <tr>
                        <td>OT</td>
                        <td><?= number_format($sum['ot_12_9'],2) ?></td>
                        <td><?= number_format($sum['ot_today'],2) ?></td>
                    </tr>
                    <tr>
                        <td>Bus</td>
                        <td><?= number_format($sum['bus_12_9'],2) ?></td>
                        <td><?= number_format($sum['bus_today'],2) ?></td>
                    </tr>
                    <tr>
                        <td>Hostel</td>
                        <td><?= number_format($sum['hos_12_9'],2) ?></td>
                        <td><?= number_format($sum['hos_today'],2) ?></td>
                    </tr>
                    <tr>
                        <td>Old</td>
                        <td><?= number_format($sum['old_12_9'],2) ?></td>
                        <td><?= number_format($sum['old_today'],2) ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Grand Total</td>
                        <td><?= number_format($grand_total_12_9,2) ?></td>
                        <td><?= number_format($grand_total_today,2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
</body>
</html>
