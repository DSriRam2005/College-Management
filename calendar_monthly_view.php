<?php
// calendar_monthly_view.php
require_once "db.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

/* MONTH + YEAR */
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date("m"));
$year_full = isset($_GET['year']) ? intval($_GET['year']) : intval(date("Y"));

/* B.TECH year mapping */
$years = [25,24,23,22];

$year_labels = [
    25 => "1st B.TECH",
    24 => "2nd B.TECH",
    23 => "3rd B.TECH",
    22 => "4th B.TECH",
];

/* DIP and PG labels */
$dip_label = "DIPLOMA";
$pg_label  = "PG";

/* Start/end of month */
$start = sprintf('%04d-%02d-01', $year_full, $month);
$end   = date("Y-m-t", strtotime($start));

/* PROGRAM NORMALIZATION ACCORDING TO DB */
function map_prog($p) {
    $p = strtoupper(trim($p));

    if ($p === "B.TECH") return "B.TECH";
    if ($p === "DIP")    return "DIP";

    if (in_array($p, ["M.TECH","MBA","MCA"])) return "PG";

    return $p;
}

/* FETCH EVENTS WITH EXPERT DETAILS */
$sql = "SELECT C.*, E.expert_name 
        FROM CLASS_CALENDAR C
        LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
        WHERE C.date BETWEEN ? AND ?
        ORDER BY C.date ASC, C.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$events_by_day = [];

while ($row = $res->fetch_assoc()) {

    $d    = intval(date("j", strtotime($row['date'])));
    $prog = map_prog($row['prog']);
    $yr   = intval($row['year']);

    if ($prog === "B.TECH" && in_array($yr, $years)) {
        $events_by_day[$d][$yr][] = $row;
    }
    elseif ($prog === "DIP") {
        $events_by_day[$d]["DIP"][] = $row;
    }
    elseif ($prog === "PG") {
        $events_by_day[$d]["PG"][] = $row;
    }
}

/* class-type colors */
$colors = [
    'GATE EXPERT'      => '#3a86ff',
    'CAMPUS EXPERT'    => '#38b000',
    'PLACEMENT EXPERT' => '#ff6d00'
];

$month_name = date("F", mktime(0,0,0,$month,1));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Academic Calendar — <?= "$month_name $year_full" ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body {
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    color: #333;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 10px;
}

/* HEADER */
.page-header {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 10px;
}

.page-header h1 {
    font-size: 1.2rem;
    margin: 0 0 4px 0;
}

.page-header h1 i {
    margin-right: 6px;
}

.page-header .text-white-50 {
    color: #666 !important;
    font-size: 0.9rem;
}

/* FILTER FORM */
form.row.g-2.mb-3 {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px;
    margin-bottom: 10px !important;
}

.form-label {
    font-size: 0.8rem;
    margin-bottom: 4px;
    color: #555;
}

.form-select {
    font-size: 0.85rem;
    padding: 4px 6px;
    height: 32px;
    border-radius: 4px;
}

.btn-primary {
    font-size: 0.85rem;
    padding: 4px 6px;
    height: 32px;
    border-radius: 4px;
}

/* CALENDAR WRAPPER */
.calendar-container {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px;
}

/* TABLE */
.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.calendar-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
}

.calendar-table thead th {
    background: #007bff;
    color: #fff;
    padding: 6px 4px;
    text-align: center;
    border: 1px solid #ddd;
    white-space: nowrap;
}

/* DATE CELL */
.date-cell {
    width: 60px;
    text-align: center;
    background: #f8f9fa;
    font-weight: 600;
    padding: 6px 4px;
    border: 1px solid #ddd;
}

.date-cell small {
    display: block;
    font-size: 0.7rem;
    color: #666;
}

/* BODY CELLS */
.calendar-table td {
    border: 1px solid #eee;
    padding: 3px;
    vertical-align: top;
    min-width: 120px;
    background: #fff;
}

/* EVENT BOX – SMALL & SIMPLE */
.event-box {
    border-left: 3px solid #6c757d; /* overwritten by inline border-color */
    padding: 3px 4px;
    margin-bottom: 3px;
    border-radius: 3px;
    font-size: 0.7rem;
}

.event-box b {
    display: block;
    font-size: 0.72rem;
    margin-bottom: 2px;
    color: #222;
}

.event-box small {
    display: block;
    font-size: 0.65rem;
    color: #666;
}

/* EMPTY CELL INDICATOR */
.calendar-table td:empty::after {
    content: '—';
    display: block;
    text-align: center;
    color: #ccc;
    padding: 8px 0;
    font-size: 0.75rem;
}

/* TODAY HIGHLIGHT ROW */
.calendar-table tbody tr.table-active td {
    background: #e8f3ff;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .container {
        padding: 5px;
    }

    .page-header {
        padding: 8px;
        text-align: left;
    }

    .page-header h1 {
        font-size: 1rem;
    }

    form.row.g-2.mb-3 {
        padding: 6px;
    }

    .calendar-table {
        font-size: 0.7rem;
    }

    .calendar-table td {
        min-width: 110px;
        padding: 2px;
    }

    .date-cell {
        width: 50px;
        padding: 4px 2px;
    }

    .event-box {
        padding: 2px 3px;
        margin-bottom: 2px;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 0.95rem;
    }

    .page-header .text-white-50 {
        font-size: 0.75rem;
    }

    .form-select,
    .btn-primary {
        font-size: 0.75rem;
        height: 30px;
    }

    .calendar-table td {
        min-width: 100px;
    }
}
</style>

</head>

<body>
<div class="container py-4">

    <!-- HEADER -->
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i>Academic Calendar</h1>
        <div class="text-white-50"><?= "$month_name $year_full" ?></div>
    </div>

    <!-- FILTER -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <label class="form-label">Month</label>
            <select name="month" class="form-select">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-5">
            <label class="form-label">Year</label>
            <select name="year" class="form-select">
                <?php for($y=date("Y")-2; $y<=date("Y")+1; $y++): ?>
                <option value="<?= $y ?>" <?= $y==$year_full?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label d-none d-md-block">&nbsp;</label>
            <button class="btn btn-primary w-100">Load</button>
        </div>
    </form>

    <!-- CALENDAR -->
    <div class="calendar-container">
        <div class="table-responsive">
            <table class="calendar-table table">
                <thead>
                    <tr>
                        <th style="width:110px;">Date</th>
                        <?php foreach($years as $y): ?>
                            <th><?= htmlspecialchars($year_labels[$y]) ?></th>
                        <?php endforeach; ?>
                        <th><?= htmlspecialchars($dip_label) ?></th>
                        <th><?= htmlspecialchars($pg_label) ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $days = date("t", strtotime($start));
                    for ($d=1; $d<=$days; $d++):
                        $isToday = ($d == date('j') && $month == date('n') && $year_full == date('Y'));
                        $isWeekend = in_array(date('w', strtotime("$year_full-$month-$d")), [0, 6]);
                        $dateCellClass = $isToday ? 'today' : ($isWeekend ? 'weekend' : '');
                    ?>
                    <tr>
                        <td class="date-cell <?= $dateCellClass ?>">
                            <?= $d ?><br>
                            <small><?= date("D", strtotime("$year_full-$month-$d")) ?></small>
                        </td>

                        <!-- B.TECH COLUMNS -->
                        <?php foreach($years as $yr): ?>
                        <td>
                            <?php
                            if (!empty($events_by_day[$d][$yr])) {
                                foreach ($events_by_day[$d][$yr] as $ev) {

                                    $color = $colors[$ev['classtype']] ?? "#6c757d";
                                    $link  = "calendar_view_details.php?id=" . $ev['id'];

                                    echo "<a href='$link' style='text-decoration:none;color:inherit;'>";
                                    echo "<div class='event-box' style='border-color:$color'>";
                                    // SUBJECT - EXPERTNAME in one line
                                    echo "<b>" . htmlspecialchars($ev['subject']) . " - " . htmlspecialchars($ev['expert_name']) . "</b>";
                                    echo "</div>";
                                    echo "</a>";
                                }
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>

                        <!-- DIP COLUMN -->
                        <td>
                            <?php
                            if (!empty($events_by_day[$d]['DIP'])) {
                                foreach ($events_by_day[$d]['DIP'] as $ev) {
                                    $color = $colors[$ev['classtype']] ?? "#6c757d";
                                    $link  = "calendar_view_details.php?id=" . $ev['id'];

                                    echo "<a href='$link' style='text-decoration:none;color:inherit;'>";
                                    echo "<div class='event-box' style='border-color:$color'>";
                                    echo "<b>" . htmlspecialchars($ev['subject']) . " - " . htmlspecialchars($ev['expert_name']) . "</b>";
                                    echo "</div>";
                                    echo "</a>";
                                }
                            }
                            ?>
                        </td>

                        <!-- PG COLUMN -->
                        <td>
                            <?php
                            if (!empty($events_by_day[$d]['PG'])) {
                                foreach ($events_by_day[$d]['PG'] as $ev) {
                                    $color = $colors[$ev['classtype']] ?? "#6c757d";
                                    $link  = "calendar_view_details.php?id=" . $ev['id'];

                                    echo "<a href='$link' style='text-decoration:none;color:inherit;'>";
                                    echo "<div class='event-box' style='border-color:$color'>";
                                    echo "<b>" . htmlspecialchars($ev['subject']) . " - " . htmlspecialchars($ev['expert_name']) . "</b>";
                                    echo "</div>";
                                    echo "</a>";
                                }
                            }
                            ?>
                        </td>

                    </tr>
                    <?php endfor; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<script>
// Add today highlighting
document.addEventListener('DOMContentLoaded', function() {
    // Today's date highlighting
    const today = new Date();
    const currentDay = today.getDate();
    const currentMonth = today.getMonth() + 1;
    const currentYear = today.getFullYear();
    
    // Check if current month/year matches
    const urlParams = new URLSearchParams(window.location.search);
    const selectedMonth = parseInt(urlParams.get('month')) || <?= $month ?>;
    const selectedYear = parseInt(urlParams.get('year')) || <?= $year_full ?>;
    
    if (currentMonth === selectedMonth && currentYear === selectedYear) {
        // Highlight today's row
        const rows = document.querySelectorAll('.calendar-table tbody tr');
        rows[currentDay - 1]?.classList.add('table-active');
    }
    
    // Simple hover transform (optional – very light)
    const eventBoxes = document.querySelectorAll('.event-box');
    eventBoxes.forEach(box => {
        box.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(2px)';
        });
        box.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});
</script>

</body>
</html>
