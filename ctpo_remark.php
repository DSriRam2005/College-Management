 

<?php
session_start();
include 'db.php';

// ✅ Allow only CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

// ✅ Restrict to CPTO’s classid
$classid = $_SESSION['classid'] ?? null;
if (!$classid) {
    echo "<p style='color:red;text-align:center;'>No class assigned.</p>";
    exit();
}

// ✅ Handle remark submission (only one remark per day)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['htno'])) {
    $htno = $_POST['htno'];
    $called_no = trim($_POST['called_no']);
    $remark = trim($_POST['remark']);
    $today = date("Y-m-d");

    if (!empty($remark)) {
        // Check if remark already exists today
        $check = $conn->prepare("SELECT id FROM REMARKS WHERE htno=? AND remark_date=? LIMIT 1");
        $check->bind_param("ss", $htno, $today);
        $check->execute();
        $res = $check->get_result();

        if ($res && $res->num_rows > 0) {
            // Update existing remark
            $row = $res->fetch_assoc();
            $upd = $conn->prepare("UPDATE REMARKS SET remark=?, called_no=? WHERE id=?");
            $upd->bind_param("ssi", $remark, $called_no, $row['id']);
            if (!$upd->execute()) {
                die("Update failed: " . $upd->error);
            }
        } else {
            // Insert new remark
            $ins = $conn->prepare("INSERT INTO REMARKS (htno, remark, called_no, remark_date) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $htno, $remark, $called_no, $today);
            if (!$ins->execute()) {
                die("Insert failed: " . $ins->error);
            }
        }
    }

    // Refresh page after insert/update
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ✅ Fetch students with today dues > 0 in this class
// ✅ Fetch students with today dues > 0 in this class, ignoring debarred students
$sql = "
    SELECT htno, name, teamid,
           (COALESCE(tfdue_today,0) + COALESCE(otdues_today,0) + 
            COALESCE(busdue_today,0) + COALESCE(hosdue_today,0) + 
            COALESCE(olddue_today,0)) AS today_due
    FROM STUDENTS
    WHERE classid = ?
      AND (COALESCE(tfdue_today,0) + COALESCE(otdues_today,0) +
           COALESCE(busdue_today,0) + COALESCE(hosdue_today,0) +
           COALESCE(olddue_today,0)) > 0
      AND (debarred = 0 OR debarred IS NULL)   -- ✅ Ignore debarred students
    ORDER BY
      IF(LOCATE('_', IFNULL(TRIM(teamid),'')) > 0,
         SUBSTRING_INDEX(TRIM(teamid), '_', 1),
         IFNULL(TRIM(teamid), '')
      ) ASC,
      CAST(
        IF(LOCATE('_', IFNULL(TRIM(teamid),'')) > 0,
           SUBSTRING_INDEX(TRIM(teamid), '_', -1),
           '0'
        ) AS UNSIGNED
      ) ASC,
      today_due DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $classid);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Today Due List - CPTO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
    h2 { text-align: center; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; vertical-align: top; }
    th { background: #2c3e50; color: #fff; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #e8f4fd; }
    .nodata { text-align:center; padding:20px; color:red; font-weight:bold; }
    .remark-box { text-align:left; }
    .remark-form textarea { width: 100%; height: 40px; }
    .remark-form input[type=text] { width: 100%; }
    .remark-form button { margin-top: 4px; padding: 4px 8px; }
    .oldremarks { font-size: 12px; color: #333; margin-top: 5px; }
  </style>
</head>
<body>
  <h2>Today Due List (Class: <?php echo htmlspecialchars($classid); ?>)</h2>

  <?php if ($result && $result->num_rows > 0): ?>
    <table>
    <tr>
      <th>S.No</th>
      <th>Team ID</th>
      <th>Hall Ticket No</th>
      <th>Name</th>
      <th>Total Today Due (₹)</th>
      <th>Last 3 Remarks</th> <!-- ✅ New column -->
      <th>Called No / Remark (Today)</th>
    </tr>
    <?php 
    $i = 1; 
    while ($row = $result->fetch_assoc()):
        $htno = $row['htno'];
        $today = date("Y-m-d");

        // ✅ Fetch last 3 remarks excluding today
        $last3Sql = "SELECT remark, remark_date 
                     FROM REMARKS 
                     WHERE htno = ? AND remark_date < ? 
                     ORDER BY remark_date DESC 
                     LIMIT 3";
        $lst = $conn->prepare($last3Sql);
        $lst->bind_param("ss", $htno, $today);
        $lst->execute();
        $last3Res = $lst->get_result();

        // ✅ Fetch today’s remark
        $remarkSql = "SELECT remark, called_no FROM REMARKS 
                      WHERE htno = ? AND remark_date = ? LIMIT 1";
        $rstmt = $conn->prepare($remarkSql);
        $rstmt->bind_param("ss", $htno, $today);
        $rstmt->execute();
        $todayRemark = $rstmt->get_result()->fetch_assoc();
    ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td><?php echo htmlspecialchars($row['teamid']); ?></td>
        <td><?php echo htmlspecialchars($htno); ?></td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td style="color:red; font-weight:bold;"><?php echo number_format($row['today_due'],2); ?></td>
        
        <!-- ✅ Last 3 remarks column -->
        <td class="oldremarks">
          <?php if ($last3Res->num_rows > 0): ?>
              <?php while ($lr = $last3Res->fetch_assoc()): ?>
                  <div><b><?php echo htmlspecialchars($lr['remark_date']); ?>:</b> 
                       <?php echo htmlspecialchars($lr['remark']); ?></div>
              <?php endwhile; ?>
          <?php else: ?>
              <em>No past remarks</em>
          <?php endif; ?>
        </td>

        <!-- ✅ Today’s remark column -->
        <td class="remark-box">
          <form method="post" class="remark-form">
            <input type="hidden" name="htno" value="<?php echo htmlspecialchars($htno); ?>">
            <input type="text" name="called_no" placeholder="Called No" value="<?php echo htmlspecialchars($todayRemark['called_no'] ?? ''); ?>">
            <textarea name="remark" placeholder="Enter remark..."><?php echo htmlspecialchars($todayRemark['remark'] ?? ''); ?></textarea>
            <button type="submit"><?php echo $todayRemark ? 'Update' : 'Save'; ?></button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php else: ?>
    <div class="nodata">No dues for today in your class 🎉</div>
  <?php endif; ?>

</body>
</html>
