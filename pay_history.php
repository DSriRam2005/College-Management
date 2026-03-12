<?php
session_start();
include 'db.php';

/* ONLY ADMIN */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

/* -----------------------------------------------------
   DELETE LOGIC (UNCHANGED)
----------------------------------------------------- */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("SELECT * FROM PAYMENTS WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if ($payment) {
        $htno = $payment['htno'];

        /* Restore dues */
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

        /* Restore mess (latest month only) */
        $month_stmt = $conn->prepare("
            SELECT month_year FROM messfee 
            WHERE htno = ? 
            ORDER BY month_year DESC 
            LIMIT 1
        ");
        $month_stmt->bind_param("s", $htno);
        $month_stmt->execute();
        $month = $month_stmt->get_result()->fetch_assoc();

        if ($month) {
            $mess = $conn->prepare("
                UPDATE messfee 
                SET due = IFNULL(due,0) + ?
                WHERE htno = ? AND month_year = ?
            ");
            $mess->bind_param("dss", $payment['paid_mess'], $htno, $month['month_year']);
            $mess->execute();
        }

        $del = $conn->prepare("DELETE FROM PAYMENTS WHERE id=?");
        $del->bind_param("i", $delete_id);
        $del->execute();

        $_SESSION['msg'] = "Payment deleted and dues restored.";
    }

    header("Location: payment_his.php");
    exit();
}

/* -----------------------------------------------------
   HTNO FILTER (MANDATORY)
----------------------------------------------------- */
$htno = trim($_GET['htno'] ?? '');

$result = null;

if ($htno !== '') {
    $stmt = $conn->prepare("
        SELECT * 
        FROM PAYMENTS 
        WHERE htno = ?
        ORDER BY pay_date DESC, id DESC
    ");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment History by HTNO</title>
    <style>
        body { font-family: Arial; padding: 20px; background:#f9f9f9; }
        table { width:100%; border-collapse:collapse; background:#fff; margin-top:15px; }
        th,td { border:1px solid #ccc; padding:8px; text-align:center; }
        th { background:#eee; }
        .box { background:#fff; padding:10px; border:1px solid #ddd; }
        .msg { padding:10px; background:#d4edda; border:1px solid #c3e6cb; margin-bottom:10px; }
        a.delete { color:red; text-decoration:none; }
    </style>
</head>
<body>

<h2>💰 Payment History (HTNO-wise)</h2>

<?php if (!empty($_SESSION['msg'])): ?>
    <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<div class="box">
    <form method="GET">
        <b>HTNO:</b>
        <input type="text" name="htno" required value="<?= htmlspecialchars($htno) ?>">
        <button type="submit">View History</button>
    </form>
</div>

<?php if ($result): ?>
<table>
<tr>
    <th>ID</th>
    <th>Date</th>
    <th>TF</th>
    <th>OT</th>
    <th>BUS</th>
    <th>HOS</th>
    <th>OLD</th>
    <th>MESS</th>
    <th>Total</th>
    <th>Receipt</th>
    <th>Method</th>
    <th>Action</th>
</tr>

<?php
$grand = 0;
while ($r = $result->fetch_assoc()):
    $total =
        $r['paid_tf'] +
        $r['paid_ot'] +
        $r['paid_bus'] +
        $r['paid_hos'] +
        $r['paid_old'] +
        $r['paid_mess'];
    $grand += $total;
?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= $r['pay_date'] ?></td>
    <td><?= $r['paid_tf'] ?></td>
    <td><?= $r['paid_ot'] ?></td>
    <td><?= $r['paid_bus'] ?></td>
    <td><?= $r['paid_hos'] ?></td>
    <td><?= $r['paid_old'] ?></td>
    <td><?= $r['paid_mess'] ?></td>
    <td><b><?= number_format($total,2) ?></b></td>
    <td><?= $r['receiptno'] ?></td>
    <td><?= $r['method'] ?></td>
    <td>
        <a class="delete"
           href="?htno=<?= urlencode($htno) ?>&delete_id=<?= $r['id'] ?>"
           onclick="return confirm('Delete payment and restore dues?')">
           ❌
        </a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<div class="box">
    <b>Total Collected:</b> ₹<?= number_format($grand,2) ?>
</div>
<?php endif; ?>

</body>
</html>
