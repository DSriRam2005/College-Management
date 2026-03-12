<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';
date_default_timezone_set('Asia/Kolkata');

/* ================= ACCESS ================= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['STPO','PR'])) {
    die("Access denied");
}

if ($_SESSION['role'] === 'STPO') {
    if (empty($_SESSION['empid'])) die("STPO ID missing");
    $stpo = $_SESSION['empid'];
} else {
    $stpo = $_GET['stpo'] ?? null;
    if (!$stpo) die("STPO not selected");
}

/* ================= AJAX : ABSENCE HISTORY ================= */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'absence' && isset($_GET['htno'])) {

    if (!isset($_SESSION['role'])) exit;

    $htno  = $_GET['htno'];
    $month = $_GET['month'] ?? date('Y-m');

    $stmt = $conn->prepare("
        SELECT att_date, remark
        FROM attendance
        WHERE htno = ?
          AND status = 'Absent'
          AND DATE_FORMAT(att_date,'%Y-%m') = ?
        ORDER BY att_date DESC
    ");
    $stmt->bind_param("ss", $htno, $month);
    $stmt->execute();
    $res = $stmt->get_result();

    echo "<table class='table table-bordered text-center'>
            <thead class='table-dark'>
            <tr><th>Date</th><th>Remark</th></tr>
            </thead><tbody>";

    if ($res->num_rows) {
        while ($r = $res->fetch_assoc()) {
            echo "<tr>
                    <td>{$r['att_date']}</td>
                    <td>".htmlspecialchars($r['remark'] ?? '-')."</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='2' class='text-muted'>No absences</td></tr>";
    }

    echo "</tbody></table>";
    exit;
}

/* ================= FILTERS ================= */
$date   = $_GET['date'] ?? date('Y-m-d');
$month  = date('Y-m', strtotime($date));
$filter = $_GET['filter'] ?? 'all';

/* ================= MAIN DATA ================= */
$sql = "
SELECT
    s.htno,
    s.name,
    s.teamid,
    a.status,
    a.remark,
    a.ph_no,

    /* overall */
    (SELECT COUNT(*) FROM attendance WHERE htno=s.htno AND status='Present') AS total_present,
    (SELECT COUNT(*) FROM attendance WHERE htno=s.htno AND status='Absent')  AS total_absent,

    /* this month */
    (SELECT COUNT(*) FROM attendance 
        WHERE htno=s.htno AND status='Present'
          AND DATE_FORMAT(att_date,'%Y-%m') = ?) AS month_present,

    (SELECT COUNT(*) FROM attendance 
        WHERE htno=s.htno AND status='Absent'
          AND DATE_FORMAT(att_date,'%Y-%m') = ?) AS month_absent

FROM STUDENTS s
LEFT JOIN attendance a
    ON a.htno = s.htno
    AND a.att_date = ?
WHERE s.stpo = ?
  AND IFNULL(s.debarred,0)=0
";

if ($filter === 'present') $sql .= " AND a.status='Present'";
elseif ($filter === 'absent') $sql .= " AND a.status='Absent'";

$sql .= "
ORDER BY
    SUBSTRING_INDEX(s.teamid,'_',1),
    CAST(SUBSTRING_INDEX(s.teamid,'_',-1) AS UNSIGNED),
    s.htno
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $month, $month, $date, $stpo);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>STPO Attendance – Teamwise</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body { background:#f8f9fa; }
.badge-present { background:#28a745; }
.badge-absent  { background:#dc3545; }
.badge-not     { background:#6c757d; }
.student-link { font-weight:600; text-decoration:none; }
.student-link:hover { text-decoration:underline; }
</style>
</head>
<body class="p-4">

<h4 class="mb-3">📋 STPO Attendance – Teamwise</h4>

<!-- FILTERS -->
<form class="row g-3 mb-3">
    <div class="col-auto">
        <label class="fw-bold">Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control" onchange="this.form.submit()">
    </div>
    <div class="col-auto">
        <label class="fw-bold">Status</label>
        <select name="filter" class="form-select" onchange="this.form.submit()">
            <option value="all" <?= $filter==='all'?'selected':'' ?>>All</option>
            <option value="present" <?= $filter==='present'?'selected':'' ?>>Present</option>
            <option value="absent" <?= $filter==='absent'?'selected':'' ?>>Absent</option>
        </select>
    </div>
</form>

<table class="table table-bordered table-striped align-middle text-center">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>HT No</th>
    <th>Name</th>
    <th>Status</th>
    <th>This Month (P / A)</th>
    <th>Total (P / A)</th>
    <th>Overall %</th>
    <th>Called No</th>
    <th>Remark</th>
</tr>
</thead>
<tbody>

<?php
$sno = 1;
$currentTeam = null;

while ($r = $result->fetch_assoc()):
    $status = $r['status'] ?? 'Not Marked';
    $badge  = $status === 'Present' ? 'badge-present' :
              ($status === 'Absent'  ? 'badge-absent'  : 'badge-not');

    $overallTotal = $r['total_present'] + $r['total_absent'];
    $overallPct   = $overallTotal > 0
        ? round(($r['total_present'] / $overallTotal) * 100, 2)
        : 0;

    if ($currentTeam !== $r['teamid']) {
        $currentTeam = $r['teamid'];
        echo "<tr class='table-info'>
                <td colspan='9' class='text-start fw-bold'>
                    Team : ".htmlspecialchars($currentTeam ?? '—')."
                </td>
              </tr>";
    }
?>
<tr>
    <td><?= $sno++ ?></td>
    <td><?= htmlspecialchars($r['htno']) ?></td>
    <td>
        <a href="#" class="student-link"
           data-htno="<?= $r['htno'] ?>"
           data-name="<?= htmlspecialchars($r['name']) ?>">
           <?= htmlspecialchars($r['name']) ?>
        </a>
    </td>
    <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
    <td><?= $r['month_present'] ?> / <span class="text-danger"><?= $r['month_absent'] ?></span></td>
    <td><?= $r['total_present'] ?> / <span class="text-danger"><?= $r['total_absent'] ?></span></td>
    <td><strong><?= $overallPct ?>%</strong></td>
    <td><?= htmlspecialchars($r['ph_no'] ?? '-') ?></td>
    <td><?= htmlspecialchars($r['remark'] ?? '-') ?></td>
</tr>
<?php endwhile; ?>

<?php if ($sno === 1): ?>
<tr><td colspan="9" class="text-muted">No records found</td></tr>
<?php endif; ?>

</tbody>
</table>

<!-- ================= MODAL ================= -->
<div class="modal fade" id="absenceModal">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="studentTitle"></h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="month" id="monthPicker"
               class="form-control mb-3"
               value="<?= date('Y-m') ?>">
        <div id="absenceContent">Loading…</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).on('click','.student-link',function(e){
    e.preventDefault();
    let htno = $(this).data('htno');
    let name = $(this).data('name');

    $('#studentTitle').text('Absence History – ' + name);
    loadAbsence(htno, $('#monthPicker').val());

    $('#monthPicker').off().on('change', function(){
        loadAbsence(htno, this.value);
    });

    new bootstrap.Modal('#absenceModal').show();
});

function loadAbsence(htno, month){
    $('#absenceContent').html('Loading…');
    $.get(window.location.pathname, {
        ajax: 'absence',
        htno: htno,
        month: month
    }, function(res){
        $('#absenceContent').html(res);
    });
}
</script>

</body>
</html>
