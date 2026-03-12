<?php
ini_set('display_errors', 1);
ini_set('start_up_errors', 1);
error_reporting(E_ALL);

session_start();
include "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn->query("SET SQL_BIG_SELECTS = 1");

// selected month
$selectedMonth = $_GET['month'] ?? date("Y-m");
$mode = $_GET['mode'] ?? "NO_MESS";
   // <-- toggle value
[$yr, $mn] = explode("-", $selectedMonth);

// ----------------------------------------------------
// PROGRAM COLUMN LOGIC (B.TECH yearwise, others combined)
// ----------------------------------------------------
$progYears = [];

// B.TECH yearwise
$q1 = $conn->query("
    SELECT DISTINCT CONCAT('B.TECH ', year) AS p
    FROM STUDENTS
    WHERE UPPER(prog) = 'B.TECH'
    ORDER BY year
");
while ($r = $q1->fetch_assoc()) $progYears[] = $r['p'];

// Other progs combined
$q2 = $conn->query("
    SELECT DISTINCT UPPER(prog) AS p
    FROM STUDENTS
    WHERE UPPER(prog) <> 'B.TECH' AND prog IS NOT NULL AND prog <> ''
    ORDER BY prog
");
while ($r = $q2->fetch_assoc()) $progYears[] = $r['p'];

// ----------------------------
// AMOUNT CALCULATION BASED ON TOGGLE
// ----------------------------
$amountSQL = match ($mode) {
    "NO_MESS" => "(paid_tf + paid_ot + paid_bus + paid_hos + paid_old)",
    "ONLY_MESS" => "paid_mess",
    default => "(paid_tf + paid_ot + paid_bus + paid_hos + paid_old + paid_mess)"
};

// ---------------------------------------------
// GET PAYMENT DATA
// ---------------------------------------------
$sql = "
    SELECT pay_date, htno, $amountSQL AS amount
    FROM PAYMENTS
    WHERE MONTH(pay_date) = ? AND YEAR(pay_date) = ?
    ORDER BY pay_date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mn, $yr);
$stmt->execute();
$res = $stmt->get_result();

// pivot table data
$data = [];

while ($r = $res->fetch_assoc()) {
    $date = $r["pay_date"];
    $htno = $r["htno"];
    $amount = (float)$r["amount"];

    // fetch student prog+year
    $st = $conn->prepare("SELECT UPPER(prog) AS prog, year FROM STUDENTS WHERE htno = ? LIMIT 1");
    $st->bind_param("s", $htno);
    $st->execute();
    $stData = $st->get_result()->fetch_assoc();

    if (!$stData) {
        $progYear = "UNKNOWN";
    } else {
        $prog = $stData['prog'];
        $year = $stData['year'];
        $progYear = ($prog === "B.TECH") ? "B.TECH $year" : $prog;
    }

    if (!isset($data[$date])) {
        $data[$date] = array_fill_keys($progYears, 0);
        $data[$date]["total"] = 0;
    }

    if (!isset($data[$date][$progYear])) {
        // In case a program/year appears that wasn't in headers
        $data[$date][$progYear] = 0;
        if (!in_array($progYear, $progYears, true)) $progYears[] = $progYear;
    }

    $data[$date][$progYear] += $amount;
    $data[$date]["total"] += $amount;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Monthly Fee Report</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<style>
    :root{
        --brand:#2563eb;
        --brand-ink:#fff;
        --bg:#f7f7f9;
        --ink:#1f2937;
        --muted:#6b7280;
        --card:#ffffff;
        --border:#e5e7eb;
        --accent:#fef3c7;
    }
    *{box-sizing:border-box}
    body{
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        background: var(--bg);
        color: var(--ink);
        margin:0;
        padding:16px;
    }
    .wrap{
        max-width: 1200px;  /* medium, no huge wide table */
        margin: 0 auto;
    }
    h2{
        margin:.25rem 0 1rem;
        font-weight:700;
        letter-spacing:.2px;
    }
    /* Card for controls */
    .controls{
        background: var(--card);
        border:1px solid var(--border);
        border-radius:12px;
        padding:12px;
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        align-items:center;
        position:sticky;
        top:8px; /* keeps filters visible while scrolling page */
        z-index:5;
    }
    .controls .field{
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    }
    input[type="month"]{
        padding:8px 10px;
        border:1px solid var(--border);
        border-radius:8px;
        background:#fff;
        color:var(--ink);
        font-size:14px;
        height:36px;
    }
    .segmented{
        background:#f1f5f9;
        padding:4px;
        border-radius:999px;
        display:inline-flex;
        gap:4px;
        border:1px solid var(--border);
    }
    .segmented button{
        border:0;
        padding:6px 12px;
        font-size:13px;
        border-radius:999px;
        background:transparent;
        color:var(--ink);
        cursor:pointer;
        line-height:1;
    }
    .segmented button.active{
        background:var(--brand);
        color:var(--brand-ink);
    }
    .controls .submit-btn{
        padding:8px 14px;
        border-radius:10px;
        border:1px solid var(--brand);
        background:var(--brand);
        color:#fff;
        font-weight:600;
        cursor:pointer;
        height:36px;
    }

    /* Data table card */
    .card{
        background: var(--card);
        border:1px solid var(--border);
        border-radius:12px;
        padding:8px;
        margin-top:12px;
        overflow:hidden; /* prevent weird overflows */
    }

    /* Compact, medium-readable table */
    table.dataTable{
        width:100% !important;
        table-layout: fixed;        /* keeps columns from growing too wide */
        border-collapse: collapse !important;
        font-size: 13px;            /* compact */
    }
table.dataTable th,
table.dataTable td{
    padding: 6px 6px !important;
    text-align: center;
    white-space: normal !important;  /* allow wrapping */
    overflow: visible !important;    /* show full number */
    text-overflow: unset !important; /* remove dots */
    word-break: break-word !important; /* break long numbers if needed */
    border-color: var(--border) !important;
}
    table.dataTable thead th{
        background: var(--brand) !important;
        color: #fff !important;
        position: sticky;
        top: 0;                       /* sticky header inside card */
        z-index: 2;
    }
    table.dataTable tfoot tr{
        background: var(--accent) !important;
        font-weight:700;
    }

    /* DataTables UI tweaks */
    .dataTables_wrapper .dataTables_filter input{
        border:1px solid var(--border);
        border-radius:8px;
        padding:6px 10px;
        height:32px;
        font-size:13px;
        outline:none;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button{
        border-radius:8px !important;
        padding:4px 8px !important;
        border:1px solid var(--border) !important;
        margin:0 2px !important;
    }
    .dataTables_wrapper .dataTables_length{
        display:none; /* hide dropdown to keep UI clean */
    }

    /* Make it feel “medium” and avoid page scroll sideways */
    @media (max-width: 1280px){
        .wrap{max-width: 1000px;}
    }
    @media (max-width: 900px){
        .wrap{max-width: 100%;}
        .controls{border-radius:10px}
        table.dataTable{font-size:12px}
        .segmented button{padding:6px 10px;font-size:12px}
    }
</style>
</head>
<body>
<div class="wrap">
    <h2>Monthly Fee Collection Summary</h2>

    <form class="controls" method="GET" id="filtersForm">
        <div class="field">
            <label for="month"><strong>Month</strong></label>
            <input type="month" name="month" id="month" value="<?= htmlspecialchars($selectedMonth) ?>" required>
        </div>

        <!-- SEGMENTED TOGGLE (replaces dropdown) -->
        <div class="field">
            <strong>Mess Fee</strong>
            <div class="segmented" id="modeToggle">
                <button type="button" data-mode="ALL">With</button>
                <button type="button" data-mode="NO_MESS">Without</button>
                <button type="button" data-mode="ONLY_MESS">Only</button>
            </div>
            <input type="hidden" name="mode" id="modeInput" value="<?= htmlspecialchars($mode) ?>">
        </div>

        <button class="submit-btn" type="submit">Apply</button>
    </form>

    <div class="card">
        <table id="summary" class="display compact stripe">
            <thead>
                <tr>
                    <th>Date</th>
                    <?php foreach ($progYears as $c): ?>
                        <th><?= htmlspecialchars($c) ?></th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $footerTotals = array_fill_keys($progYears, 0);
            $footerTotals["grand"] = 0;

            foreach ($data as $date => $row): ?>
                <tr>
                    <td><?= htmlspecialchars($date) ?></td>

                    <?php foreach ($progYears as $c): ?>
                        <td>
                            <?php
                            $v = $row[$c] ?? 0;
                            echo number_format($v, 2);
                            $footerTotals[$c] += $v;
                            ?>
                        </td>
                    <?php endforeach; ?>

                    <td><b>
                        <?php
                        echo number_format($row["total"], 2);
                        $footerTotals["grand"] += $row["total"];
                        ?>
                    </b></td>
                </tr>
            <?php endforeach; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td><b>Total</b></td>
                    <?php foreach ($footerTotals as $key => $val): if ($key === "grand") continue; ?>
                        <td><b><?= number_format($val, 2) ?></b></td>
                    <?php endforeach; ?>
                    <td><b><?= number_format($footerTotals["grand"], 2) ?></b></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
$(document).ready(function () {
    // Activate current toggle
    const currentMode = "<?= htmlspecialchars($mode) ?>";
    $("#modeToggle button").each(function(){
        if ($(this).data("mode") === currentMode) $(this).addClass("active");
    });

    // Toggle click -> set hidden field (no dropdown)
    $("#modeToggle button").on("click", function(){
        $("#modeToggle button").removeClass("active");
        $(this).addClass("active");
        $("#modeInput").val($(this).data("mode"));
    });

    // DataTable init: compact, no horizontal scroll, medium page
    $("#summary").DataTable({
        paging: true,
        pageLength: 15,       // medium chunk
        lengthChange: false,  // no page length dropdown
        info: true,
        searching: true,
        ordering: false,      // keep natural order (by date already)
        responsive: true,
        autoWidth: false,
        // Simple DOM layout: search box on top, table, then pagination
        dom: '<"top"f>t<"bottom"ip><"clear">'
    });
});
</script>
</body>
</html>
