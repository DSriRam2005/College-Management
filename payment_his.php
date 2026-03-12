<?php
session_start();
include 'db.php';

// Only allow ADMIN
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

// -----------------------------------------------------
// DELETE LOGIC (FIXED) — RESTORES ONLY CORRECT MONTH
// -----------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Fetch payment
    $stmt = $conn->prepare("SELECT * FROM PAYMENTS WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if ($payment) {

        $htno = $payment['htno'];

        // 1️⃣ Restore TF / OT / BUS / HOS / OLD
        $update = $conn->prepare("
            UPDATE STUDENTS 
            SET 
                tfdue_today  = IFNULL(tfdue_today,0)  + ?,
                otdues_today = IFNULL(otdues_today,0) + ?,
                busdue_today = IFNULL(busdue_today,0) + ?,
                hosdue_today = IFNULL(hosdue_today,0) + ?,
                olddue_today = IFNULL(olddue_today,0) + ?
            WHERE htno = ?
        ");

        $update->bind_param(
            "ddddds",
            $payment['paid_tf'],
            $payment['paid_ot'],
            $payment['paid_bus'],
            $payment['paid_hos'],
            $payment['paid_old'],
            $htno
        );
        $update->execute();


        // 2️⃣ Restore MESS FEE ONLY FOR THE CORRECT MONTH
        // Find latest mess month for this student
        $month_stmt = $conn->prepare("
            SELECT month_year FROM messfee 
            WHERE htno = ? 
            ORDER BY month_year DESC 
            LIMIT 1
        ");
        $month_stmt->bind_param("s", $htno);
        $month_stmt->execute();
        $month_res = $month_stmt->get_result()->fetch_assoc();

        $month_year = $month_res ? $month_res['month_year'] : null;

        if ($month_year) {
            // Update exact month
            $mess_update = $conn->prepare("
                UPDATE messfee 
                SET due = IFNULL(due,0) + ?
                WHERE htno = ? AND month_year = ?
            ");
            $mess_update->bind_param("dss", $payment['paid_mess'], $htno, $month_year);
            $mess_update->execute();

        } else {
            // If no mess record exists, create one
            $insert_mess = $conn->prepare("
                INSERT INTO messfee (htno, ttamt, due, month_year) 
                VALUES (?, 0, ?, CURDATE())
            ");
            $insert_mess->bind_param("sd", $htno, $payment['paid_mess']);
            $insert_mess->execute();
        }


        // 3️⃣ Delete Payment Record
        $del = $conn->prepare("DELETE FROM PAYMENTS WHERE id=?");
        $del->bind_param("i", $delete_id);
        $del->execute();

        $_SESSION['msg'] = "Payment ID $delete_id deleted successfully and dues restored.";
    } else {
        $_SESSION['msg'] = "Payment not found.";
    }

    header("Location: payment_his.php");
    exit();
}



// -----------------------------------------------------
// FILTER LOGIC
// -----------------------------------------------------
$htno   = $_GET['htno']   ?? '';
$teamid = $_GET['teamid'] ?? '';
$method = $_GET['method'] ?? '';
$date   = $_GET['date']   ?? '';

$sql = "SELECT * FROM PAYMENTS WHERE 1=1";
$params = [];
$types  = "";

// Filter by HTNO
if (!empty($htno)) {
    $sql .= " AND htno LIKE ?";
    $params[] = "%$htno%";
    $types .= "s";
}

// Filter by Team
if (!empty($teamid)) {
    $sql .= " AND teamid = ?";
    $params[] = $teamid;
    $types .= "s";
}

// Filter by Method
if (!empty($method)) {
    $sql .= " AND method = ?";
    $params[] = $method;
    $types .= "s";
}

// Filter by Date
if (!empty($date)) {
    $sql .= " AND pay_date = ?";
    $params[] = $date;
    $types .= "s";
}

// Order newest first
$sql .= " ORDER BY pay_date DESC, id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History (ADMIN)</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #eee; }
        tr:hover { background: #f1f1f1; }
        .filter-box { background: #fff; padding: 10px; border: 1px solid #ddd; margin-bottom: 15px; }
        .filter-box input, .filter-box select { padding: 5px; margin: 5px; }
        .total-box { margin-top: 15px; background: #fff; padding: 10px; border: 1px solid #ddd; font-weight: bold; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        a.delete { color: red; text-decoration: none; }
        a.delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h2>💰 Payment History (ADMIN)</h2>

<?php if (!empty($_SESSION['msg'])): ?>
    <p class="msg success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></p>
<?php endif; ?>

<div class="filter-box">
    <form method="GET">
        HTNO: <input type="text" name="htno" value="<?= htmlspecialchars($htno) ?>">
        TeamID: <input type="text" name="teamid" value="<?= htmlspecialchars($teamid) ?>">
        Method:
        <select name="method">
            <option value="">--All--</option>
            <option value="ONLINE" <?= $method=="ONLINE" ? "selected" : "" ?>>ONLINE</option>
            <option value="COUNTER" <?= $method=="COUNTER" ? "selected" : "" ?>>COUNTER</option>
        </select>
        Date: <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
        <button type="submit">Search</button>
    </form>
</div>

<table>
<tr>
    <th>ID</th>
    <th>HTNO</th>
    <th>Name</th>
    <th>Team</th>
    <th>TF</th>
    <th>OT</th>
    <th>BUS</th>
    <th>HOS</th>
    <th>OLD</th>
    <th>MESS</th>
    <th>Total Paid</th>
    <th>Date</th>
    <th>Receipt</th>
    <th>Method</th>
    <th>Action</th>
</tr>

<?php 
$grand_total = 0;
while ($row = $result->fetch_assoc()): 
    $total = 
        $row['paid_tf'] +
        $row['paid_ot'] +
        $row['paid_bus'] +
        $row['paid_hos'] +
        $row['paid_old'] +
        $row['paid_mess'];

    $grand_total += $total;
?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['htno']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['teamid']) ?></td>
    <td><?= $row['paid_tf'] ?></td>
    <td><?= $row['paid_ot'] ?></td>
    <td><?= $row['paid_bus'] ?></td>
    <td><?= $row['paid_hos'] ?></td>
    <td><?= $row['paid_old'] ?></td>
    <td><?= $row['paid_mess'] ?></td>
    <td><b><?= number_format($total, 2) ?></b></td>
    <td><?= $row['pay_date'] ?></td>
    <td><?= $row['receiptno'] ?></td>
    <td><?= $row['method'] ?></td>
    <td>
        <a href="payment_his.php?delete_id=<?= $row['id'] ?>" class="delete"
           onclick="return confirm('Are you sure you want to delete this payment? This will restore dues including Mess.');">
           ❌ Delete
        </a>
    </td>
</tr>
<?php endwhile; ?>

</table>

<div class="total-box">
    Grand Total Collected: ₹<?= number_format($grand_total, 2) ?>
</div>

</body>
</html>
