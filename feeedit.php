<?php
// Debug (remove later)
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "ADMIN") {
    header("Location: index.php");
    exit;
}

$student = null;
$msg = "";
$messfee = null;

/* =====================================================
   UPDATE DATA (POST REQUEST)
===================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id   = $_POST['id'];
    $htno = $_POST['htno'];

    // safe set
    $selected_month = $_POST["selected_month"] ?? "";
    $month_year     = $_POST["month_year"] ?? "";

    // priority: selected_month → month_year
    $month = ($selected_month != "") ? $selected_month : $month_year;

    // convert YYYY-MM to YYYY-MM-01
    if (!empty($month) && strlen($month) == 7) {
        $month = $month . "-01";   // final date format
    }

    // Update STUDENTS table
    $conn->query("
        UPDATE STUDENTS SET
            tfdue_12_9    = '{$_POST['tfdue_12_9']}',
            tfdue_today   = '{$_POST['tfdue_today']}',
            otdues_12_9   = '{$_POST['otdues_12_9']}',
            otdues_today  = '{$_POST['otdues_today']}',
            busdue_12_9   = '{$_POST['busdue_12_9']}',
            busdue_today  = '{$_POST['busdue_today']}',
            hosdue_12_9   = '{$_POST['hosdue_12_9']}',
            hosdue_today  = '{$_POST['hosdue_today']}',
            olddue_12_9   = '{$_POST['olddue_12_9']}',
            olddue_today  = '{$_POST['olddue_today']}'
        WHERE id = '$id'
    ");

    // Messfee save only if values provided
    if (!empty($_POST['ttamt']) && !empty($_POST['due']) && !empty($month)) {

        $check = $conn->query("SELECT id FROM messfee WHERE htno='$htno' AND month_year='$month'");

        if ($check->num_rows > 0) {

            // UPDATE
            $conn->query("
                UPDATE messfee SET
                    ttamt = '{$_POST['ttamt']}',
                    due   = '{$_POST['due']}'
                WHERE htno='$htno' AND month_year='$month'
            ");

        } else {

            // INSERT NEW
            $conn->query("
                INSERT INTO messfee (htno, ttamt, due, month_year)
                VALUES ('$htno', '{$_POST['ttamt']}', '{$_POST['due']}', '$month')
            ");

        }
    }

    header("Location: feeedit.php?htno=$htno&updated=1");
    exit;
}

/* =====================================================
   SEARCH STUDENT (GET REQUEST)
===================================================== */
if (isset($_GET['htno'])) {

    $htno = $_GET['htno'];

    $res = $conn->query("SELECT * FROM STUDENTS WHERE htno='$htno'");
    if ($res->num_rows > 0) {
        $student = $res->fetch_assoc();
    } else {
        $msg = "HTNO not found.";
    }

    // last messfee row (for display)
    $messfee = $conn->query("
        SELECT * FROM messfee WHERE htno='$htno' ORDER BY id DESC LIMIT 1
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Fee Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    body { background:#f6f7fb; }
    .container-box { max-width:1100px; margin:auto; }
    label { font-weight:600; }
</style>
</head>
<body>

<div class="container container-box mt-5">
<div class="card shadow p-4">

<h3>Search Student Fee Details</h3>

<form method="get" class="row g-2 mb-3">
    <div class="col-6">
        <input type="text" name="htno" class="form-control" placeholder="Enter HTNO" required>
    </div>
    <div class="col-3">
        <button class="btn btn-primary w-100">Search</button>
    </div>
</form>

<?php if ($msg): ?>
<div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<?php if ($student): ?>

<div class="alert alert-info">
    <b>HTNO:</b> <?= $student["htno"] ?> &nbsp;&nbsp;
    <b>Name:</b> <?= $student["name"] ?> &nbsp;&nbsp;
    <b>Class:</b> <?= $student["classid"] ?>
</div>

<form method="post" class="row g-3">
<input type="hidden" name="id" value="<?= $student['id'] ?>">
<input type="hidden" name="htno" value="<?= $student['htno'] ?>">

<h4 class="text-center text-primary mb-4">Fee Due Details</h4>

<div class="row g-4">

    <!-- LEFT -->
    <div class="col-6">
        <h5 class="text-danger">Previous Dues (12-9)</h5>
        <?php
        $left = [
            "tfdue_12_9" => "TF Due (12-9)",
            "otdues_12_9" => "OT Due (12-9)",
            "busdue_12_9" => "Bus Due (12-9)",
            "hosdue_12_9" => "Hostel Due (12-9)",
            "olddue_12_9" => "Old Due (12-9)"
        ];
        foreach ($left as $col => $label): ?>
            <div class="mb-3">
                <label><?= $label ?></label>
                <input type="number" step="0.01" class="form-control" name="<?= $col ?>" value="<?= $student[$col] ?>">
            </div>
        <?php endforeach; ?>
    </div>

    <!-- RIGHT -->
    <div class="col-6">
        <h5 class="text-success">Current Dues (Today)</h5>
        <?php
        $right = [
            "tfdue_today" => "TF Due (Today)",
            "otdues_today" => "OT Due (Today)",
            "busdue_today" => "Bus Due (Today)",
            "hosdue_today" => "Hostel Due (Today)",
            "olddue_today" => "Old Due (Today)"
        ];
        foreach ($right as $col => $label): ?>
            <div class="mb-3">
                <label><?= $label ?></label>
                <input type="number" step="0.01" class="form-control" name="<?= $col ?>" value="<?= $student[$col] ?>">
            </div>
        <?php endforeach; ?>
    </div>
</div>

<hr>

<h4 class="text-center text-primary">Mess Fee Entry</h4>

<div class="col-6">
    <label>Select Month (existing)</label>
    <select name="selected_month" id="selected_month" class="form-control" onchange="loadMessFee()">
        <option value="">-- Select Month --</option>
        <?php
        $rows = $conn->query("SELECT * FROM messfee WHERE htno='$htno' ORDER BY id DESC");
        while ($r = $rows->fetch_assoc()):
            $month_ym = substr($r['month_year'], 0, 7); // convert YYYY-MM-DD → YYYY-MM
        ?>
            <option value="<?= $month_ym ?>"
                data-ttamt="<?= $r['ttamt'] ?>"
                data-due="<?= $r['due'] ?>">
                <?= $month_ym ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="col-6">
    <label>Or enter new Month</label>
    <input type="month" class="form-control" name="month_year" id="month_year">
</div>

<div class="col-4">
    <label>Total Amount (ttamt)</label>
    <input type="number" step="0.01" id="ttamt" name="ttamt"
           class="form-control" value="<?= $messfee['ttamt'] ?? '' ?>">
</div>

<div class="col-4">
    <label>Due</label>
    <input type="number" step="0.01" id="due" name="due"
           class="form-control" value="<?= $messfee['due'] ?? '' ?>">
</div>

<div class="col-12 text-end mt-4">
    <button class="btn btn-success px-4">Save / Update</button>
</div>

</form>

<?php endif; ?>

</div>
</div>

<script>
function loadMessFee() {
    const opt = document.getElementById("selected_month").selectedOptions[0];
    if (!opt) return;

    document.getElementById("month_year").value = opt.value;
    document.getElementById("ttamt").value = opt.getAttribute("data-ttamt");
    document.getElementById("due").value = opt.getAttribute("data-due");
}
</script>

</body>
</html>
