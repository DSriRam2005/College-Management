<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "ADMIN") {
    header("Location: index.php");
    exit;
}

$old_month = $_GET['old_month'] ?? "";
$old_month_date = "";

$students = [];

if ($old_month != "") {

    // Convert YYYY-MM → YYYY-MM-01
    if (strlen($old_month) == 7) {
        $old_month_date = $old_month . "-01";
    }

    // Load students who have messfee for that month
    $q = $conn->query("
        SELECT s.htno, s.name, s.classid, m.ttamt, m.due
        FROM messfee m
        JOIN STUDENTS s ON s.htno = m.htno
        WHERE m.month_year = '$old_month_date'
        ORDER BY s.htno
    ");

    while ($r = $q->fetch_assoc()) {
        $students[] = $r;
    }
}

/* ==========================================================
   SAVE NEW MONTH MESSFEE
========================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_month = $_POST['new_month'];   // YYYY-MM
    $ttamt     = $_POST['ttamt'];
    $due       = $_POST['due'];

    // convert to YYYY-MM-01
    if (strlen($new_month) == 7) {
        $new_month = $new_month . "-01";
    }

    if (!empty($_POST['selected_htno'])) {
        foreach ($_POST['selected_htno'] as $htno) {
            $conn->query("
                INSERT INTO messfee (htno, ttamt, due, month_year)
                VALUES ('$htno', '$ttamt', '$due', '$new_month')
            ");
        }
    }

    header("Location: newmess.php?old_month=$old_month&success=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Copy Mess Fee from Old Month</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container bg-white mt-5 p-4 shadow">

<h3>Copy Mess Fee From Previous Month</h3>

<!-- SELECT OLD MONTH -->
<form method="get" class="row mb-4">
    <div class="col-4">
        <label>Select Old Month</label>
        <input type="month" name="old_month" class="form-control" value="<?= $old_month ?>" required>
    </div>
    <div class="col-2 d-flex align-items-end">
        <button class="btn btn-primary w-100">Load</button>
    </div>
</form>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">New Month Mess Fee Added Successfully.</div>
<?php endif; ?>

<?php if ($old_month == ""): ?>
    <p class="text-muted">Please select a month.</p>
<?php endif; ?>


<?php if ($old_month != ""): ?>

<h4>Students Found in <?= $old_month ?> (<?= count($students) ?>)</h4>

<form method="post">

    <input type="hidden" name="old_month" value="<?= $old_month ?>">

    <!-- NEW MONTH & AMOUNTS -->
    <div class="row mb-3">
        <div class="col-3">
            <label>New Month</label>
            <input type="month" name="new_month" class="form-control" required>
        </div>
        <div class="col-3">
            <label>Total Amount</label>
            <input type="number" step="0.01" name="ttamt" class="form-control" required>
        </div>
        <div class="col-3">
            <label>Due Amount</label>
            <input type="number" step="0.01" name="due" class="form-control" required>
        </div>
    </div>

    <table class="table table-bordered">
        <tr>
            <th><input type="checkbox" onclick="toggleAll(this)"></th>
            <th>HTNO</th>
            <th>Name</th>
            <th>Class</th>
            <th>Prev Amt</th>
            <th>Prev Due</th>
        </tr>

        <?php foreach ($students as $s): ?>
        <tr>
            <td><input type="checkbox" name="selected_htno[]" value="<?= $s['htno'] ?>"></td>
            <td><?= $s['htno'] ?></td>
            <td><?= $s['name'] ?></td>
            <td><?= $s['classid'] ?></td>
            <td><?= $s['ttamt'] ?></td>
            <td><?= $s['due'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <button class="btn btn-success mt-3">Add Mess Fee For New Month</button>
</form>

<?php endif; ?>

</div>

<script>
function toggleAll(src) {
    let boxes = document.querySelectorAll("input[name='selected_htno[]']");
    boxes.forEach(b => b.checked = src.checked);
}
</script>

</body>
</html>
