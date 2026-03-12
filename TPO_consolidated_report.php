<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn->query("SET SQL_BIG_SELECTS=1");

// --- Handle test selection ---
$selected_test = isset($_POST['test_name']) ? strtoupper(trim($_POST['test_name'])) : '';
$test_ids = [];
$test_names = [];

// Fetch tests matching approximately selected name
if ($selected_test !== '') {
    $sql = "SELECT test_id, test_name FROM tests WHERE UPPER(test_name) LIKE '%" . $conn->real_escape_string($selected_test) . "%'";
    $res = $conn->query($sql);
    while ($r = $res->fetch_assoc()) {
        $test_ids[] = $r['test_id'];
        $test_names[] = $r['test_name'];
    }
}

// Stop if no tests found
if ($selected_test !== '' && count($test_ids) == 0) {
    die("<h3 style='text-align:center;color:red;'>❌ No tests found with name like '$selected_test'.</h3>");
}

// --- Fetch total possible marks ---
$total_possible = 0;
if (count($test_ids) > 0) {
    $sec_sql = "SELECT SUM(section_marks) AS total_marks FROM test_sections WHERE test_id IN (" . implode(",", $test_ids) . ")";
    $sec_res = $conn->query($sec_sql);
    $total_possible = ($sec_res && $row = $sec_res->fetch_assoc()) ? $row['total_marks'] : 0;
}

// --- Report generation ---
$report_data = [];
$highest_percent = 0;
$lowest_percent = 0;

if (count($test_ids) > 0 && $total_possible > 0) {
    $sql = "
        SELECT s.htno, s.name, s.teamid, 
               SUM(sm.marks_obtained) AS total
        FROM STUDENTS s
        INNER JOIN student_marks sm 
            ON s.htno = sm.htno AND sm.test_id IN (" . implode(",", $test_ids) . ")
        GROUP BY s.htno
        ORDER BY total DESC
    ";
    $res = $conn->query($sql);

    while ($r = $res->fetch_assoc()) {
        $percent = round(($r['total'] / $total_possible) * 100, 2);
        $r['percent'] = $percent;
        $report_data[] = $r;
        if ($percent > $highest_percent) $highest_percent = $percent;
        if ($lowest_percent == 0 || $percent < $lowest_percent) $lowest_percent = $percent;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Report (TCS / Infosys)</title>
<style>
body { font-family:"Segoe UI",sans-serif; background:#f8f9fc; margin:0; padding:0; }
.container { width:90%; margin:30px auto; background:#fff; padding:25px 35px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.15); }
h2 { color:#333; margin-bottom:20px; text-align:center; }
form { display:flex; justify-content:center; gap:15px; margin-bottom:25px; align-items:center; }
select, button { padding:8px 12px; border-radius:8px; border:1px solid #ccc; font-size:15px; }
button { background:#007bff; color:white; cursor:pointer; font-weight:500; }
button:hover { background:#0056b3; }
.print-btn { background:#28a745; }
.print-btn:hover { background:#218838; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { border:1px solid #ddd; padding:8px 10px; font-size:14px; text-align:center; }
th { background:#007bff; color:white; }
.green { background-color:#28a745; color:white; font-weight:bold; }
.orange { background-color:#fd7e14; color:white; font-weight:bold; }
.red { background-color:#dc3545; color:white; font-weight:bold; }
.legend { margin-top:15px; font-size:14px; text-align:center; }
.legend span { display:inline-block; padding:4px 12px; margin-right:10px; border-radius:5px; color:white; font-weight:bold; }
.legend .green { background-color:#28a745; }
.legend .orange { background-color:#fd7e14; }
.legend .red { background-color:#dc3545; }
@media print {
    form, .print-btn, .legend { display:none !important; }
    .container { box-shadow:none; border:none; margin:0; width:100%; }
}
</style>
<script>function printReport(){window.print();}</script>
</head>
<body>
<div class="container">
<h2>📊 Test Report – TCS / Infosys</h2>

<form method="post">
    <label for="test_name"><b>Select Test:</b></label>
    <select name="test_name" id="test_name" required>
        <option value="">-- Choose --</option>
        <option value="INFOSYS" <?= $selected_test==='INFOSYS'?'selected':'' ?>>INFOSYS</option>
        <option value="TCS" <?= $selected_test==='TCS'?'selected':'' ?>>TCS</option>
    </select>
    <button type="submit">Show Report</button>
    <?php if(count($report_data)>0): ?>
        <button type="button" class="print-btn" onclick="printReport()">🖨 Print</button>
    <?php endif; ?>
</form>

<?php if(count($report_data)>0): ?>
    <p><strong>Included Tests:</strong> <?= implode(", ", $test_names) ?></p>
    <p><strong>Total Possible Marks:</strong> <?= $total_possible ?> |
       <strong>Highest %:</strong> <?= $highest_percent ?> |
       <strong>Lowest %:</strong> <?= $lowest_percent ?></p>

    <div class="legend">
        <span class="green">≥ 74.6%</span>
        <span class="orange">64.6% - 74.59%</span>
        <span class="red">< 64.6%</span>
    </div>

    <table>
    <tr>
        <th>Rank</th>
        <th>HT No</th>
        <th>Name</th>
        <th>Team</th>
        <th>Total Marks</th>
        <th>%</th>
    </tr>
    <?php 
    $rank=1;
    foreach($report_data as $r):
        $cls = $r['percent'] >= 74.6 ? 'green' : ($r['percent'] >= 64.6 ? 'orange' : 'red');
    ?>
    <tr class="<?= $cls ?>">
        <td><?= $rank++ ?></td>
        <td><?= htmlspecialchars($r['htno']) ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['teamid']) ?></td>
        <td><strong><?= $r['total'] ?></strong></td>
        <td><strong><?= $r['percent'] ?>%</strong></td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php elseif ($selected_test !== ''): ?>
    <p style="text-align:center;color:#888;">No marks found for the selected test.</p>
<?php endif; ?>
</div>
</body>
</html>
