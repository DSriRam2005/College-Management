<?php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Kolkata');

// allow only CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$message = "";
$student = null;
$mess_months = [];

// ================= SEARCH STUDENT =================
if (isset($_POST['search'])) {
    $htno = mysqli_real_escape_string($conn, $_POST['htno']);

    $sql = "SELECT htno, name, teamid, 
                   tfdue_today, otdues_today, busdue_today, 
                   hosdue_today, olddue_today
            FROM STUDENTS
            WHERE htno='$htno' LIMIT 1"; // change classid condition if required
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);

        $mess_q = "SELECT id, month_year, ttamt, due 
                   FROM messfee 
                   WHERE htno='$htno' AND due > 0 
                   ORDER BY month_year DESC";
        $mess_r = mysqli_query($conn, $mess_q);
        while ($row = mysqli_fetch_assoc($mess_r)) {
            $mess_months[] = $row;
        }
    } else {
        $message = "❌ No student found for HTNO: $htno";
    }
}

// ============== SAVE PAYMENT ==================
if (isset($_POST['save'])) {

    $htno = $_POST['htno'];
    $name = $_POST['name'];
    $teamid = $_POST['teamid'];
    $pay_date = $_POST['pay_date'];
    $receiptno = mysqli_real_escape_string($conn, $_POST['receiptno']);
    $method = $_POST['method'];
    $mess_month = $_POST['mess_month'] ?? null;

    $paid_tf  = floatval($_POST['paid_tf']);
    $paid_ot  = floatval($_POST['paid_ot']);
    $paid_bus = floatval($_POST['paid_bus']);
    $paid_hos = floatval($_POST['paid_hos']);
    $paid_old = floatval($_POST['paid_old']);
    $paid_mess = floatval($_POST['paid_mess']);

    $total_amt = $paid_tf + $paid_ot + $paid_bus + $paid_hos + $paid_old + $paid_mess;

    if ($total_amt <= 0) {
        $message = "❌ Enter at least one category amount.";
    } else {

        $dup_check = "SELECT id FROM PAYMENTS 
                      WHERE htno='$htno' 
                        AND pay_date='$pay_date' 
                        AND receiptno='$receiptno' 
                        AND method='$method'
                      LIMIT 1";
        $dup_result = mysqli_query($conn, $dup_check);

        if ($dup_result && mysqli_num_rows($dup_result) > 0) {
            $message = "❌ Duplicate payment found. Not saved.";
        } else {
$created_at = date("Y-m-d H:i:s");  // IST time, fully controlled by PHP

 $insert = "INSERT INTO PAYMENTS 
(htno,name,teamid,paid_tf,paid_ot,paid_bus,paid_hos,paid_old,paid_mess,pay_date,receiptno,method,created_at)
VALUES
('$htno','$name','$teamid',
 $paid_tf,$paid_ot,$paid_bus,$paid_hos,$paid_old,$paid_mess,
 '$pay_date','$receiptno','$method','$created_at')";


            mysqli_query($conn, $insert);

            if ($paid_mess > 0 && !empty($mess_month)) {
                mysqli_query($conn, "UPDATE messfee 
                                     SET due = GREATEST(due - $paid_mess, 0)
                                     WHERE htno='$htno' AND month_year='$mess_month' LIMIT 1");
            }

            mysqli_query($conn, "UPDATE STUDENTS SET
                tfdue_today = tfdue_today - $paid_tf,
                otdues_today = otdues_today - $paid_ot,
                busdue_today = busdue_today - $paid_bus,
                hosdue_today = hosdue_today - $paid_hos,
                olddue_today = olddue_today - $paid_old
                WHERE htno='$htno' LIMIT 1");

            $message = "✅ Payment saved successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CTPO Payments</title>
<style>
body {font-family: Segoe UI; background:#f4f6f9; padding:40px;}
.box {background:#fff; border-radius:10px; padding:30px; max-width:800px; margin:auto; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2 {text-align:center; color:#007bff;}
label {font-weight:600;}
input, select {padding:10px; border:1px solid #ccc; border-radius:5px; width:100%;}
button {padding:10px 20px; background:#007bff; border:none; color:#fff; border-radius:5px; cursor:pointer;}
table {width:100%; border-collapse:collapse; margin-top:15px;}
th, td {border:1px solid #ccc; padding:8px; text-align:center;}
th {background:#007bff; color:#fff;}
.msg {background:#d4edda; padding:10px; border-radius:5px; margin-bottom:15px;}
.error {background:#f8d7da; padding:10px; border-radius:5px; margin-bottom:15px;}
</style>
<script>
function toggleReceiptLabel() {
    let m = document.getElementById("method").value;
    document.getElementById("receiptLabel").innerText = 
        m === "ONLINE" ? "Transaction No:" : "Receipt No:";
}
</script>
</head>
<body>
<div class="box">
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">💰 CTPO Payments</h2>
    <a href="cpto_queries.php"
       style="text-decoration:none;
              background:#198754;
              color:#fff;
              padding:8px 14px;
              border-radius:6px;
              font-weight:600;">
        📩 CTPO Queries
    </a>
</div>

<?php if (!empty($message)): ?>
<div class="<?= strpos($message,'✅')!==false ? 'msg' : 'error' ?>"><?= $message ?></div>
<?php endif; ?>

<form method="post">
    <label>Enter HTNO:</label>
    <input type="text" name="htno" required>
    <br><br>
    <button type="submit" name="search">Search</button>
</form>

<?php if (!empty($student)): ?>
<hr>
<form method="post">
<input type="hidden" name="htno" value="<?= $student['htno'] ?>">
<input type="hidden" name="name" value="<?= $student['name'] ?>">
<input type="hidden" name="teamid" value="<?= $student['teamid'] ?>">

<p><b>Roll No:</b> <?= $student['htno'] ?></p>
<p><b>Name:</b> <?= $student['name'] ?></p>
<p><b>Team ID:</b> <?= $student['teamid'] ?></p>

<h3>Outstanding Dues</h3>
<table>
<tr><th>Tuition</th><th>Other</th><th>Bus</th><th>Hostel</th><th>Old</th></tr>
<tr>
<td><?= $student['tfdue_today'] ?></td>
<td><?= $student['otdues_today'] ?></td>
<td><?= $student['busdue_today'] ?></td>
<td><?= $student['hosdue_today'] ?></td>
<td><?= $student['olddue_today'] ?></td>
</tr>
</table>

<?php if (!empty($mess_months)): ?>
<h3>Mess Fee Dues by Month</h3>
<table>
<tr><th>Month-Year</th><th>Total</th><th>Due</th></tr>
<?php foreach ($mess_months as $m): ?>
<tr>
<td><?= date("M Y", strtotime($m['month_year'])) ?></td>
<td><?= $m['ttamt'] ?></td>
<td><?= $m['due'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br>
<h3>Enter Payment</h3>

<label>Payment Method:</label>
<select name="method" id="method" onchange="toggleReceiptLabel()" required>
<option value="">-- Select --</option>
<option value="ONLINE">ONLINE</option>
<option value="COUNTER">COUNTER</option>
</select><br><br>

<label>Payment Date:</label>
<input type="date" name="pay_date" max="<?= date('Y-m-d') ?>" required><br><br>


<label id="receiptLabel">Receipt/Payment Ref No:</label>
<input type="text" name="receiptno" required><br><br>

<table>
<tr><th>Category</th><th>Amount</th></tr>
<tr><td>Tuition Fee</td><td><input type="number" step="0.01" name="paid_tf" value="0"></td></tr>
<tr><td>Other Fee</td><td><input type="number" step="0.01" name="paid_ot" value="0"></td></tr>
<tr><td>Bus Fee</td><td><input type="number" step="0.01" name="paid_bus" value="0"></td></tr>
<tr><td>Hostel Fee</td><td><input type="number" step="0.01" name="paid_hos" value="0"></td></tr>
<tr><td>Old Fee</td><td><input type="number" step="0.01" name="paid_old" value="0"></td></tr>
<tr>
<td>Mess Fee</td>
<td>
<input type="number" step="0.01" name="paid_mess" value="0" oninput="toggleMessMonth()">
</td>
</tr>

</table>

<br>

<label>Mess Month (if mess paid):</label>
<select name="mess_month">
<option value="">-- Select Month --</option>
<?php foreach ($mess_months as $m): ?>
<option value="<?= $m['month_year'] ?>"><?= date("M Y", strtotime($m['month_year'])) ?></option>
<?php endforeach; ?>
</select>

<br><br>
<button type="submit" name="save">Save Payment</button>
</form>
<?php endif; ?>
</div>
<script>
function toggleReceiptLabel() {
    let m = document.getElementById("method").value;
    document.getElementById("receiptLabel").innerText = 
        m === "ONLINE" ? "Transaction No:" : "Receipt No:";
}

// ✅ NEW: Make Mess Month mandatory only if Mess Fee > 0
function toggleMessMonth() {
    let messAmount = parseFloat(document.getElementsByName("paid_mess")[0].value) || 0;
    let messSelect = document.getElementsByName("mess_month")[0];

    if (messAmount > 0) {
        messSelect.required = true;
    } else {
        messSelect.required = false;
        messSelect.value = "";
    }
}
</script>

</body>
</html>
