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

// ✅ Decide condition based on HTPO (KIET or KIEW)
$extra_condition = "";
if (stripos($htpo_user, "KT") !== false) {
    $extra_condition = " (htno LIKE '%B2%' OR htno LIKE '%6Q%') ";
} elseif (stripos($htpo_user, "KW") !== false) {
    $extra_condition = " (htno LIKE '%JN%') ";
}

// ✅ Fetch students of selected class (allow negative dues)
$sql = "SELECT htno, name,
               tfdue_today AS tf_due,
               otdues_today AS ot_due,
               busdue_today AS bus_due,
               hosdue_today AS hos_due,
               olddue_today AS old_due
        FROM STUDENTS
        WHERE year = ? AND prog='B.TECH' 
          AND classid=? 
          AND college = ?"; // ✅ added college filter

$params = [$htpo_year, $classid, "KIET"];
$types  = "iss"; // year=int, classid=string, college=string

// Add extra HTPO-specific condition
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
    .dues-positive { color: #e74c3c; font-weight: bold; } /* red for positive dues */
    .dues-zero { color: #27ae60; font-weight: bold; }     /* green for zero or negative dues */
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

            // ✅ Green for 0 or negative, Red for positive
            $tf_class    = $s['tf_due'] <= 0 ? "dues-zero" : "dues-positive";
            $ot_class    = $s['ot_due'] <= 0 ? "dues-zero" : "dues-positive";
            $bus_class   = $s['bus_due'] <= 0 ? "dues-zero" : "dues-positive";
            $hos_class   = $s['hos_due'] <= 0 ? "dues-zero" : "dues-positive";
            $old_class   = $s['old_due'] <= 0 ? "dues-zero" : "dues-positive";
            $total_class = $total <= 0 ? "dues-zero" : "dues-positive";
    ?>
    <tr>
        <td><?= htmlspecialchars($s['htno']); ?></td>
        <td><?= htmlspecialchars($s['name']); ?></td>
        <td class="<?= $tf_class; ?>"><?= number_format($s['tf_due'],2); ?></td>
        <td class="<?= $ot_class; ?>"><?= number_format($s['ot_due'],2); ?></td>
        <td class="<?= $bus_class; ?>"><?= number_format($s['bus_due'],2); ?></td>
        <td class="<?= $hos_class; ?>"><?= number_format($s['hos_due'],2); ?></td>
        <td class="<?= $old_class; ?>"><?= number_format($s['old_due'],2); ?></td>
        <td class="<?= $total_class; ?>"><?= number_format($total,2); ?></td>
    </tr>
    <?php endwhile; else: ?>
    <tr><td colspan="8">No students found for this class.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
