<?php
session_start();
include 'db.php';

// Show errors for debugging — remove in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------------------
// 1. ACCESS CONTROL
// -----------------------------------------------------------------------------
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ZONE') {
    header("Location: index.php");
    exit();
}

// -----------------------------------------------------------------------------
// 2. GET ZONE FROM SESSION OR DATABASE
// -----------------------------------------------------------------------------
$zone = $_SESSION['ZONE'] ?? $_SESSION['zone'] ?? '';

if (!$zone && isset($_SESSION['username'])) {
    $stmtZone = $conn->prepare("SELECT ZONE FROM USERS WHERE username = ?");
    $stmtZone->bind_param("s", $_SESSION['username']);
    $stmtZone->execute();
    $zr = $stmtZone->get_result()->fetch_assoc();
    $zone = $zr['ZONE'] ?? '';
    if ($zone) $_SESSION['ZONE'] = $zone;
}

if (!$zone) {
    echo "<div class='alert alert-danger text-center m-3'>
            ❌ Zone not set for this user. Check USERS.ZONE for your username.
          </div>";
    echo "<pre>SESSION:\n"; print_r($_SESSION); echo "</pre>";
    exit();
}

// -----------------------------------------------------------------------------
// 3. ENABLE LARGE JOINS (for MySQL only)
// -----------------------------------------------------------------------------
$conn->query("SET SQL_BIG_SELECTS=1");

// -----------------------------------------------------------------------------
// 4. MAIN QUERY — FETCH STUDENTS AND THEIR DUES
// -----------------------------------------------------------------------------
$sql = "
    SELECT 
        s.htno,
        s.name,
        s.teamid,
        s.ref,
        COALESCE(s.tfdue_today, 0) AS tfdue_today,
        COALESCE(s.otdues_today, 0) AS otdues_today,
        COALESCE(s.busdue_today, 0) AS busdue_today,
        COALESCE(s.hosdue_today, 0) AS hosdue_today,
        COALESCE(s.olddue_today, 0) AS olddue_today,
        COALESCE(SUM(m.due), 0) AS mess_due
    FROM STUDENTS s
    LEFT JOIN messfee m ON s.htno = m.htno
    WHERE s.ZONE = ?
    GROUP BY s.htno, s.name, s.teamid, s.ref, 
             s.tfdue_today, s.otdues_today, 
             s.busdue_today, s.hosdue_today, s.olddue_today
    ORDER BY s.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $zone);
$stmt->execute();
$result = $stmt->get_result();

// -----------------------------------------------------------------------------
// 5. OPTIONAL: EXPORT TO CSV
// -----------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="Zone_Dues_Report_' . $zone . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'HT No', 'Name', 'Team', 'Reference', 'TF Due', 'Bus Due', 'Hostel Due', 'Mess Fee', 'Total']);

    $sno = 1;
    while ($row = $result->fetch_assoc()) {
        $tf   = max(floatval($row['tfdue_today']), 0);
        $ot   = max(floatval($row['otdues_today']), 0);
        $bus  = max(floatval($row['busdue_today']), 0);
        $hos  = max(floatval($row['hosdue_today']), 0);
        $old  = max(floatval($row['olddue_today']), 0);
        $mess = max(floatval($row['mess_due']), 0);
        $total = $tf + $ot + $bus + $hos + $old + $mess;

        fputcsv($output, [
            $sno++,
            $row['htno'],
            $row['name'],
            $row['teamid'],
            $row['ref'],
            number_format($tf, 2),
            number_format($bus, 2),
            number_format($hos, 2),
            number_format($mess, 2),
            number_format($total, 2)
        ]);
    }

    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Zone Dues Report - <?= htmlspecialchars($zone) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f8f9fa; }
  th { background:#212529; color:#fff; text-align:center; }
  td, th { vertical-align: middle; }
  .num { text-align:right; }
</style>
</head>
<body>
<div class="container mt-4">
  <h3 class="text-center mb-4">💰 Zone Dues Report — <?= htmlspecialchars($zone) ?></h3>

  <div class="mb-3 text-end">
    <a href="?export=csv" class="btn btn-success btn-sm">⬇ Export CSV</a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>S.No</th>
          <th>HT No</th>
          <th>Name</th>
          <th>Team</th>
          <th>Reference</th>
          <th class="num">TF</th>
          <th class="num">Bus Today</th>
          <th class="num">Hostel Today</th>
          <th class="num">Mess Fee</th>
          <th class="num">Total Today</th>
        </tr>
      </thead>
      <tbody>
      <?php
      $sno = 1;
      $grand_tf = $grand_ot = $grand_bus = $grand_hos = $grand_old = $grand_mess = $grand_total = 0.0;

      if ($result && $result->num_rows > 0):
          mysqli_data_seek($result, 0); // rewind for HTML table
          while ($row = $result->fetch_assoc()):
              $tf   = max(floatval($row['tfdue_today']), 0);
              $ot   = max(floatval($row['otdues_today']), 0);
              $bus  = max(floatval($row['busdue_today']), 0);
              $hos  = max(floatval($row['hosdue_today']), 0);
              $old  = max(floatval($row['olddue_today']), 0);
              $mess = max(floatval($row['mess_due']), 0);
              $total = $tf + $ot + $bus + $hos + $old + $mess;

              $grand_tf += $tf;
              $grand_ot += $ot;
              $grand_bus += $bus;
              $grand_hos += $hos;
              $grand_old += $old;
              $grand_mess += $mess;
              $grand_total += $total;
      ?>
        <tr>
          <td class="text-center"><?= $sno++ ?></td>
          <td><?= htmlspecialchars($row['htno']) ?></td>
          <td class="text-start"><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['teamid'] ?? '—') ?></td>
          <td><?= htmlspecialchars($row['ref'] ?? '—') ?></td>
          <td class="num"><?= number_format($tf, 2) ?></td>
          <td class="num"><?= number_format($bus, 2) ?></td>
          <td class="num"><?= number_format($hos, 2) ?></td>
          <td class="num"><?= number_format($mess, 2) ?></td>
          <td class="num fw-bold text-danger"><?= number_format($total, 2) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="10" class="text-center text-muted">No students found for this zone.</td></tr>
      <?php endif; ?>
      </tbody>

      <?php if ($result && $result->num_rows > 0): ?>
      <tfoot class="table-secondary fw-bold">
        <tr>
          <td colspan="5" class="text-end">GRAND TOTAL</td>
          <td class="num"><?= number_format($grand_tf, 2) ?></td>
          <td class="num"><?= number_format($grand_bus, 2) ?></td>
          <td class="num"><?= number_format($grand_hos, 2) ?></td>
          <td class="num"><?= number_format($grand_mess, 2) ?></td>
          <td class="num text-danger"><?= number_format($grand_total, 2) ?></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>

  <div class="text-center mt-3">
    <a href="zone_home.php" class="btn btn-secondary">⬅ Back to Zone Home</a>
  </div>
</div>
</body>
</html>
