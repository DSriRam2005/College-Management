<?php
session_start();
include 'db2.php';

// ✅ Only HTPO allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

$htpo_year = $_SESSION['year'] ?? null;
$htpo_user = $_SESSION['username'] ?? "";
$classid   = $_GET['classid'] ?? "";

if (!$classid) {
    exit("Class ID not provided.");
}

// ✅ Decide college + HTNO filter based on HTPO username
$college = "";
$extra_condition = "";
if (stripos($htpo_user, "KT") !== false) {
    $college = "KIET";
    $extra_condition = "(htno LIKE '%B2%' OR htno LIKE '%6Q%')";
} elseif (stripos($htpo_user, "KW") !== false) {
    $college = "KIEW";
    $extra_condition = "(htno LIKE '%JN%')";
} else {
    // fallback
    $college = "KIET";
}

// ✅ Fetch students of selected class with same filters as report
$sql = "SELECT htno, name,
               GREATEST(tfdue_today,0) AS tf_due,
               GREATEST(otdues_today,0) AS ot_due,
               GREATEST(busdue_today,0) AS bus_due,
               GREATEST(hosdue_today,0) AS hos_due,
               GREATEST(olddue_today,0) AS old_due
        FROM STUDENTS
        WHERE year = ? 
          AND prog='B.TECH' 
          AND classid = ? 
          AND college = ?";

$params = [$htpo_year, $classid, $college];
$types  = "iss";

// Add extra HTNO condition if needed
if ($extra_condition) {
    $sql .= " AND $extra_condition";
}

// Prepare and execute
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - <?= htmlspecialchars($classid); ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th, td { padding: 8px 10px; border: 1px solid #ddd; text-align: center; }
    th { background: #34495e; color: #fff; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #eef; }
    .dues { color: #e74c3c; font-weight: bold; }
    .paid { color: #27ae60; font-weight: bold; }
    .back { margin-bottom: 15px; display: inline-block; background: #3498db; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; }
    .back:hover { background: #2980b9; }
</style>
</head>
<body>

<a href="htpo_report.php" class="back">⬅ Back to Report</a>
<h2>👩‍🎓 Students - Class <?= htmlspecialchars($classid); ?> (Year <?= htmlspecialchars($htpo_year); ?>)</h2>

<table>
    <tr>
        <th>HT No</th>
        <th>Name</th>
        <th>TF Due</th>
        <th>OT Due</th>
        <th>Bus Due</th>
        <th>Hostel Due</th>
        <th>Old Due</th>
        <th>Total Due</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): 
        while($s = $result->fetch_assoc()):
            $total = $s['tf_due'] + $s['ot_due'] + $s['bus_due'] + $s['hos_due'] + $s['old_due'];
    ?>
    <tr>
        <td><?= htmlspecialchars($s['htno']); ?></td>
        <td><?= htmlspecialchars($s['name']); ?></td>
        <td class="<?= $s['tf_due'] > 0 ? 'dues' : 'paid'; ?>"><?= number_format($s['tf_due'],2); ?></td>
        <td class="<?= $s['ot_due'] > 0 ? 'dues' : 'paid'; ?>"><?= number_format($s['ot_due'],2); ?></td>
        <td class="<?= $s['bus_due'] > 0 ? 'dues' : 'paid'; ?>"><?= number_format($s['bus_due'],2); ?></td>
        <td class="<?= $s['hos_due'] > 0 ? 'dues' : 'paid'; ?>"><?= number_format($s['hos_due'],2); ?></td>
        <td class="<?= $s['old_due'] > 0 ? 'dues' : 'paid'; ?>"><?= number_format($s['old_due'],2); ?></td>
        <td class="<?= $total > 0 ? 'dues' : 'paid'; ?>"><?= number_format($total,2); ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="8">No students found for this class.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
