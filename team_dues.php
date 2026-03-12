<?php
session_start();
include 'db.php'; // DB connection

$student = null;
$team_members = [];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $htno = trim($_POST['htno']);

    if (!empty($htno)) {
        // Fetch searched student (negative → 0)
        $stmt = $conn->prepare("SELECT htno, name, teamid,
                   GREATEST(COALESCE(tfdue_today,0),0) as tfdue,
                   GREATEST(COALESCE(otdues_today,0),0) as otdue,
                   GREATEST(COALESCE(busdue_today,0),0) as busdue,
                   GREATEST(COALESCE(hosdue_today,0),0) as hosdue,
                   GREATEST(COALESCE(olddue_today,0),0) as olddue
            FROM STUDENTS WHERE htno = ?");
        $stmt->bind_param("s", $htno);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student) {
            $teamid = $student['teamid'];

            if (!empty($teamid)) {
                // Fetch all members in the same team
                $stmt2 = $conn->prepare("SELECT htno, name,
                           GREATEST(COALESCE(tfdue_today,0),0) as tfdue,
                           GREATEST(COALESCE(otdues_today,0),0) as otdue,
                           GREATEST(COALESCE(busdue_today,0),0) as busdue,
                           GREATEST(COALESCE(hosdue_today,0),0) as hosdue,
                           GREATEST(COALESCE(olddue_today,0),0) as olddue
                    FROM STUDENTS WHERE teamid = ?");
                $stmt2->bind_param("s", $teamid);
                $stmt2->execute();
                $team_members = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            $error = "No student found with HTNO: " . htmlspecialchars($htno);
        }
    } else {
        $error = "Please enter HTNO.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Student</title>
  <style>
:root{
  --bg: #f6f8fb;
  --card: #ffffff;
  --accent: #0d6efd;
  --accent-600: #0056b3;
  --muted: #6b7280;
  --surface-2: #e9f2ff;
  --danger: #dc2626;
  --radius: 10px;
  --shadow: 0 6px 18px rgba(13, 38, 77, 0.06);
  --glass: 0 2px 6px rgba(16,24,40,0.04);
  --max-width: 1100px;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
}

html,body{
  height:100%;
  margin:0;
  background:linear-gradient(180deg,var(--bg), #fff);
  color:#111827;
  font-size:15px;
  line-height:1.45;
  padding:16px;
  box-sizing:border-box;
}

.container{
  max-width:var(--max-width);
  margin:0 auto;
  background:var(--card);
  border-radius:var(--radius);
  padding:16px;
  box-shadow:var(--shadow);
  border:1px solid rgba(15,23,42,0.03);
}

.header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
  flex-wrap:wrap;
}
.header h2{ margin:0; font-size:20px; letter-spacing:-0.2px; }
.header .meta{ color:var(--muted); font-size:13px; }

.search-form{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:8px;
}
.input{ flex:1 1 260px; display:flex; }
.input input[type="text"]{
  flex:1;
  padding:12px 14px;
  border-radius:8px;
  border:1px solid #e6e9ee;
  outline:none;
  font-size:15px;
  transition:box-shadow .15s, border-color .15s;
}
.input input[type="text"]:focus{
  border-color:var(--accent);
  box-shadow:0 4px 14px rgba(13,110,253,0.12);
}

.btn{
  padding:12px 18px;
  background:var(--accent);
  color:#fff;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
  font-size:15px;
  box-shadow:var(--glass);
  flex:0 0 auto;
}
.btn:hover{ background:var(--accent-600); }

.message{
  margin-top:12px;
  padding:12px;
  border-radius:8px;
  background:#fff7f7;
  color:var(--danger);
  border:1px solid rgba(220,38,38,0.08);
  font-size:14px;
}

.result{
  margin-top:16px;
  padding:12px;
  border-radius:8px;
  background: linear-gradient(180deg, rgba(14,165,233,0.04), rgba(14,165,233,0.01));
  border:1px solid rgba(14,165,233,0.06);
  display:flex;
  flex-wrap:wrap;
  gap:12px;
  font-size:14px;
}
.badge{
  font-size:13px;
  padding:5px 10px;
  border-radius:999px;
  background:var(--surface-2);
  color:var(--accent-600);
  border:1px solid rgba(13,110,253,0.06);
}

.table-wrap{
  margin-top:16px;
  overflow-x:auto;
  border-radius:8px;
  border:1px solid rgba(15,23,42,0.04);
  background:#fff;
}
table{
  width:100%;
  min-width:720px;
  border-collapse:collapse;
  font-size:14px;
}
thead th{
  position:sticky;
  top:0;
  background:linear-gradient(180deg,var(--accent), #0069e0);
  color:#fff;
  font-weight:600;
  padding:10px;
  text-align:center;
}
tbody td{
  padding:10px;
  text-align:center;
  border-bottom:1px solid rgba(15,23,42,0.04);
}
tbody tr:hover td{
  background:linear-gradient(90deg, rgba(2,6,23,0.02), transparent);
}
td.money{ font-variant-numeric:tabular-nums; }
.total-row td{
  font-weight:700;
  background:linear-gradient(180deg,var(--surface-2), #f7fcff);
  color:#0b2a66;
  border-top:2px solid rgba(13,110,253,0.08);
}

/* 📱 Mobile tweaks */
@media (max-width: 600px) {
  body{ padding:10px; font-size:14px; }
  .header h2{ font-size:16px; }
  .btn{ width:100%; text-align:center; padding:12px; font-size:14px; }
  .input{ flex:1 1 100%; }
  .input input[type="text"]{ font-size:14px; padding:10px; width:100%; }
  table{ font-size:13px; min-width:600px; }
}

/* 📱 Card view for very small screens */
@media (max-width: 400px) {
  table, thead, tbody, th, td, tr { display:block; }
  thead { display:none; }
  tbody tr {
    margin-bottom:12px;
    background:#f9fafb;
    border-radius:8px;
    padding:10px;
    box-shadow:var(--glass);
  }
  tbody td {
    text-align:left;
    padding:6px 0;
    border:none;
  }
  tbody td::before {
    content: attr(data-label);
    font-weight:600;
    display:block;
    margin-bottom:2px;
    color:var(--muted);
  }
}
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2>Search Student by HTNO</h2>
    <div class="meta small">Enter HT number and view category-wise dues</div>
  </div>

  <form method="post" class="search-form" autocomplete="off">
    <div class="input">
      <input type="text" name="htno" placeholder="Enter HTNO" required>
    </div>
    <button type="submit" class="btn">Search</button>
  </form>

  <?php if($error): ?>
    <div class="message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if($student): ?>
    <div class="result">
      <p><strong>Team ID:</strong>
         <span class="badge"><?= $student['teamid'] ? htmlspecialchars($student['teamid']) : "No Team" ?></span>
      </p>
    </div>

    <?php if(!empty($team_members) && count($team_members) > 1): ?>
      <h3>Team Members (Category-wise Dues)</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>HTNO</th>
              <th>Name</th>
              <th>TF Due</th>
              <th>OT Due</th>
              <th>Bus Due</th>
              <th>Hostel Due</th>
              <th>Old Due</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $tot_tf=$tot_ot=$tot_bus=$tot_hos=$tot_old=0;
          foreach($team_members as $m):
              $total = $m['tfdue'] + $m['otdue'] + $m['busdue'] + $m['hosdue'] + $m['olddue'];
              $tot_tf  += $m['tfdue'];
              $tot_ot  += $m['otdue'];
              $tot_bus += $m['busdue'];
              $tot_hos += $m['hosdue'];
              $tot_old += $m['olddue'];
          ?>
            <tr>
              <td data-label="HTNO"><?= htmlspecialchars($m['htno']) ?></td>
              <td data-label="Name"><?= htmlspecialchars($m['name']) ?></td>
              <td data-label="TF Due" class="money">₹<?= number_format($m['tfdue'],2) ?></td>
              <td data-label="OT Due" class="money">₹<?= number_format($m['otdue'],2) ?></td>
              <td data-label="Bus Due" class="money">₹<?= number_format($m['busdue'],2) ?></td>
              <td data-label="Hostel Due" class="money">₹<?= number_format($m['hosdue'],2) ?></td>
              <td data-label="Old Due" class="money">₹<?= number_format($m['olddue'],2) ?></td>
              <td data-label="Total" class="money"><strong>₹<?= number_format($total,2) ?></strong></td>
            </tr>
          <?php endforeach; ?>
          <tr class="total-row">
            <td colspan="2">Grand Total</td>
            <td class="money">₹<?= number_format($tot_tf,2) ?></td>
            <td class="money">₹<?= number_format($tot_ot,2) ?></td>
            <td class="money">₹<?= number_format($tot_bus,2) ?></td>
            <td class="money">₹<?= number_format($tot_hos,2) ?></td>
            <td class="money">₹<?= number_format($tot_old,2) ?></td>
            <td class="money">₹<?= number_format($tot_tf+$tot_ot+$tot_bus+$tot_hos+$tot_old,2) ?></td>
          </tr>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <h3>Student Dues (Category-wise)</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>HTNO</th>
              <th>Name</th>
              <th>TF Due</th>
              <th>OT Due</th>
              <th>Bus Due</th>
              <th>Hostel Due</th>
              <th>Old Due</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
          <?php $total = $student['tfdue'] + $student['otdue'] + $student['busdue'] + $student['hosdue'] + $student['olddue']; ?>
            <tr>
              <td data-label="HTNO"><?= htmlspecialchars($student['htno']) ?></td>
              <td data-label="Name"><?= htmlspecialchars($student['name']) ?></td>
              <td data-label="TF Due" class="money">₹<?= number_format($student['tfdue'],2) ?></td>
              <td data-label="OT Due" class="money">₹<?= number_format($student['otdue'],2) ?></td>
              <td data-label="Bus Due" class="money">₹<?= number_format($student['busdue'],2) ?></td>
              <td data-label="Hostel Due" class="money">₹<?= number_format($student['hosdue'],2) ?></td>
              <td data-label="Old Due" class="money">₹<?= number_format($student['olddue'],2) ?></td>
              <td data-label="Total" class="money"><strong>₹<?= number_format($total,2) ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
