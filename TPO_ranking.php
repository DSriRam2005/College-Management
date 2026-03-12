<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn->query("SET SQL_BIG_SELECTS=1");

// --- Get POST values ---
$selected_test = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
$view_mode = isset($_POST['view_mode']) ? $_POST['view_mode'] : 'individual';

// --- Fetch available tests ---
$tests = $conn->query("SELECT test_id, test_name FROM tests ORDER BY created_at DESC");

// --- Fetch sections ---
$sections = [];
$total_possible = 0;
if ($selected_test) {
    $sec_result = $conn->query("SELECT * FROM test_sections WHERE test_id=$selected_test ORDER BY section_id");
    while ($row = $sec_result->fetch_assoc()) {
        $sections[] = $row;
        $total_possible += $row['section_marks'];
    }
}

// --- Fetch report data ---
$report_data = [];
$highest_percent = 0;
$lowest_percent = 0;

if ($selected_test && count($sections) > 0) {
    if ($view_mode === 'individual') {
        $sql = "
            SELECT s.htno, s.name, s.teamid,
            " . implode(",", array_map(fn($sec) =>
                "MAX(CASE WHEN sm.section_id={$sec['section_id']} THEN sm.marks_obtained END) AS `sec_{$sec['section_id']}`",
                $sections)) . "
            FROM STUDENTS s
            INNER JOIN student_marks sm 
                ON s.htno = sm.htno AND sm.test_id = $selected_test
            GROUP BY s.htno
        ";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $total = 0;
            foreach ($sections as $sec) {
                $total += floatval($r["sec_{$sec['section_id']}"] ?? 0);
            }
            $percent = $total_possible > 0 ? round(($total / $total_possible) * 100, 2) : 0;
            $r['total'] = $total;
            $r['percent'] = $percent;
            $report_data[] = $r;
            if ($percent > $highest_percent) $highest_percent = $percent;
            if ($lowest_percent == 0 || $percent < $lowest_percent) $lowest_percent = $percent;
        }
        usort($report_data, fn($a, $b) => $b['total'] <=> $a['total']);
    } else {
        $sql = "
            SELECT teamid,
            ROUND(AVG(student_total),2) AS total,
            " . implode(",", array_map(fn($sec) =>
                "ROUND(AVG(section_totals.sec_{$sec['section_id']}),2) AS `sec_{$sec['section_id']}`",
                $sections)) . "
            FROM (
                SELECT s.htno, s.teamid,
                " . implode(",", array_map(fn($sec) =>
                    "MAX(CASE WHEN sm.section_id={$sec['section_id']} THEN sm.marks_obtained END) AS `sec_{$sec['section_id']}`",
                    $sections)) . ",
                (" . implode(" + ", array_map(fn($sec) =>
                    "COALESCE(MAX(CASE WHEN sm.section_id={$sec['section_id']} THEN sm.marks_obtained END),0)",
                    $sections)) . ") AS student_total
                FROM STUDENTS s
                INNER JOIN student_marks sm 
                    ON s.htno = sm.htno AND sm.test_id = $selected_test
                GROUP BY s.htno
            ) AS section_totals
            GROUP BY teamid
        ";
        $res = $conn->query($sql);
        while ($r = $res->fetch_assoc()) {
            $percent = $total_possible > 0 ? round(($r['total'] / $total_possible) * 100, 2) : 0;
            $r['percent'] = $percent;
            $report_data[] = $r;
            if ($percent > $highest_percent) $highest_percent = $percent;
            if ($lowest_percent == 0 || $percent < $lowest_percent) $lowest_percent = $percent;
        }
        usort($report_data, fn($a, $b) => $b['total'] <=> $a['total']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Marks Report</title>
<style>
body { font-family:"Segoe UI",sans-serif; background:#f8f9fc; margin:0; padding:0; }
.container { width:95%; margin:30px auto; background:#fff; padding:25px 35px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.15); }
h2 { text-align:left; color:#333; margin-bottom:20px; }
form { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:25px; align-items:center; }
select, button { padding:8px 12px; border-radius:8px; border:1px solid #ccc; font-size:15px; }
button { background:#007bff; color:white; cursor:pointer; border:none; font-weight:500; }
button:hover { background:#0056b3; }
.print-btn { background:#28a745; margin-left:10px; }
.print-btn:hover { background:#218838; }
.toggle { display:inline-block; margin:0 10px; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { border:1px solid #ddd; padding:8px 10px; font-size:14px; text-align:left; }
th { background:#007bff; color:white; }
.rank { font-weight:bold; color:#000; }
.green { background-color:#28a745; color:white; font-weight:bold; }
.orange { background-color:#fd7e14; color:white; font-weight:bold; }
.red { background-color:#dc3545; color:white; font-weight:bold; }
.legend { margin-top:15px; font-size:14px; }
.legend span { display:inline-block; padding:4px 12px; margin-right:10px; border-radius:5px; color:white; font-weight:bold; }
.legend .green { background-color:#28a745; }
.legend .orange { background-color:#fd7e14; }
.legend .red { background-color:#dc3545; }

/* 🖨 Print Styles */
@media print {
    body { background:white; }
    form, .legend, .print-btn { display:none !important; }
    .container { box-shadow:none; border:none; margin:0; width:100%; }
    table { font-size:13px; }
    h2 { text-align:center; }
}
</style>
<script>
function printReport() {
    window.print();
}
</script>
</head>
<body>
<div class="container">
    <h2>📊 Test Marks Report</h2>

    <!-- Legend -->
    <div class="legend">
        <span class="green">≥ 74.6%</span>
        <span class="orange">64.6% - 74.59%</span>
        <span class="red">< 64.6%</span>
    </div>

    <form method="post">
        <select name="test_id" required>
            <option value="">-- Select Test --</option>
            <?php while ($t = $tests->fetch_assoc()) { ?>
                <option value="<?= $t['test_id'] ?>" <?= $t['test_id']==$selected_test?'selected':'' ?>>
                    <?= htmlspecialchars($t['test_name']) ?>
                </option>
            <?php } ?>
        </select>

        <div class="toggle">
            <label><input type="radio" name="view_mode" value="individual" <?= $view_mode==='individual'?'checked':'' ?>> Individual Rank</label>
            <label><input type="radio" name="view_mode" value="teamwise" <?= $view_mode==='teamwise'?'checked':'' ?>> Teamwise Average</label>
        </div>

        <button type="submit">Show Report</button>

        <?php if ($selected_test && count($report_data)>0): ?>
            <button type="button" class="print-btn" onclick="printReport()">🖨 Print Page</button>
        <?php endif; ?>
    </form>

    <?php if ($selected_test && count($report_data)>0): ?>
    <p><strong>Highest Percent:</strong> <?= $highest_percent ?>% &nbsp; | &nbsp; <strong>Lowest Percent:</strong> <?= $lowest_percent ?>%</p>
    <table>
        <tr>
            <th>Rank</th>
            <?php if ($view_mode==='individual'): ?>
                <th>HT No</th><th>Name</th><th>Team</th>
            <?php else: ?>
                <th>Team</th>
            <?php endif; ?>
            <?php foreach ($sections as $sec): ?>
                <th style="text-align:center;"><?= htmlspecialchars($sec['section_name']) ?></th>
            <?php endforeach; ?>
            <th>Total</th><th>Percent (%)</th>
        </tr>

        <?php $rank=1; foreach($report_data as $r): ?>
        <?php $row_class = $r['percent']>=74.6?'green':($r['percent']>=64.6?'orange':'red'); ?>
        <tr class="<?= $row_class ?>">
            <td class="rank"><?= $rank++ ?></td>
            <?php if ($view_mode==='individual'): ?>
                <td><?= htmlspecialchars($r['htno']) ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['teamid']) ?></td>
            <?php else: ?>
                <td><?= htmlspecialchars($r['teamid']) ?></td>
            <?php endif; ?>
            <?php foreach ($sections as $sec): ?>
                <td style="text-align:center;"><?= htmlspecialchars($r["sec_{$sec['section_id']}"] ?? 0) ?></td>
            <?php endforeach; ?>
            <td><strong><?= $r['total'] ?? 0 ?></strong></td>
            <td><strong><?= $r['percent'] ?>%</strong></td>
        </tr>
        <?php endforeach; ?>

        <tfoot>
            <tr>
                <td colspan="<?= ($view_mode==='individual'?4:2)+count($sections) ?>" style="text-align:right;">Max Marks:</td>
                <td colspan="2"><strong><?= $total_possible ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php elseif($selected_test): ?>
        <p style="text-align:center;color:#888;">No marks data found for the selected test.</p>
    <?php endif; ?>
</div>
</body>
</html>
