<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

$classid   = $_GET['classid'] ?? '';
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date'] ?? date('Y-m-t');
$today     = date('Y-m-d');

if (!$classid) die("classid missing");

/* ===== AJAX ABSENCE DETAILS ===== */
if(isset($_GET['ajax']) && isset($_GET['htno'])){

    $htno=$_GET['htno'];

    $stmt=$conn->prepare("
        SELECT att_date, remark
        FROM attendance
        WHERE htno=?
        AND status='Absent'
        AND att_date BETWEEN ? AND ?
        ORDER BY att_date DESC
    ");
    $stmt->bind_param("sss",$htno,$from_date,$to_date);
    $stmt->execute();
    $res=$stmt->get_result();

    echo "<table class='table table-bordered text-center'>
            <thead class='table-light'>
            <tr><th>Date</th><th>Reason</th></tr>
            </thead><tbody>";

    if($res->num_rows>0){
        while($r=$res->fetch_assoc()){
            echo "<tr>
                    <td>{$r['att_date']}</td>
                    <td>".htmlspecialchars($r['remark'] ?? '-')."</td>
                  </tr>";
        }
    }else{
        echo "<tr><td colspan='2' class='text-muted'>No absence reasons found</td></tr>";
    }

    echo "</tbody></table>";
    exit;
}

/* ===== MAIN QUERY ===== */
$query="
SELECT 
    s.htno,
    s.name,
    s.teamid,
    COUNT(a.att_date) AS working_days,
    SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS total_present,
    SUM(CASE WHEN a.status='Absent' THEN 1 ELSE 0 END) AS total_absent,
    COALESCE(t.status,'Not Marked') AS today_status
FROM STUDENTS s
LEFT JOIN attendance a
    ON s.htno=a.htno AND a.att_date BETWEEN ? AND ?
LEFT JOIN attendance t
    ON s.htno=t.htno AND t.att_date=?
WHERE s.classid=? AND s.debarred=0
GROUP BY s.htno
ORDER BY s.teamid,s.htno
";

$stmt=$conn->prepare($query);
$stmt->bind_param("ssss",$from_date,$to_date,$today,$classid);
$stmt->execute();
$result=$stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Attendance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<style>
body{background:#f4f6fb}
.text-present{color:#28a745;font-weight:700}
.text-absent{color:#dc3545;font-weight:700;cursor:pointer}
.text-not{color:#6c757d;font-weight:600}

@media print{
    .no-print{display:none}
}
</style>
</head>
<body>

<div class="container mt-4 mb-5">

<h4 class="fw-bold text-primary mb-4">
Classwise Attendance - <?= htmlspecialchars($classid) ?>
</h4>

<!-- FILTER -->
<div class="card p-3 mb-3 shadow-sm no-print">
<form method="get" class="row g-3 align-items-center">

<input type="hidden" name="classid" value="<?= htmlspecialchars($classid) ?>">

<div class="col-auto">
<input type="date" name="from_date" value="<?= $from_date ?>" class="form-control">
</div>

<div class="col-auto">
<input type="date" name="to_date" value="<?= $to_date ?>" class="form-control">
</div>

<div class="col-auto">
<button class="btn btn-primary">Apply</button>
</div>

<div class="col-auto">
<button type="button" onclick="window.print()" class="btn btn-secondary">Print</button>
</div>

</form>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">

<thead class="table-dark">
<tr>
<th>#</th>
<th>HT No</th>
<th>Name</th>
<th>Team</th>
<th>Today</th>
<th>Working</th>
<th>Present</th>
<th>Absent</th>
<th>%</th>
</tr>
</thead>

<tbody>
<?php
$sno=1;
while($row=$result->fetch_assoc()){

$working=$row['working_days']??0;
$present=$row['total_present']??0;
$absent=$row['total_absent']??0;
$percent=$working?round(($present/$working)*100,1):0;

$today_status=$row['today_status'];
$today_class=$today_status=='Present'?'text-present':
             ($today_status=='Absent'?'text-absent':'text-not');

echo "<tr>
<td>{$sno}</td>
<td>{$row['htno']}</td>
<td>".htmlspecialchars($row['name'])."</td>
<td>{$row['teamid']}</td>
<td class='{$today_class}'>{$today_status}</td>
<td><b>{$working}</b></td>
<td class='text-present'>{$present}</td>
<td class='text-absent absent-click' 
    data-htno='{$row['htno']}'
    data-name='".htmlspecialchars($row['name'])."'>
    {$absent}
</td>
<td><b>{$percent}%</b></td>
</tr>";

$sno++;
}
?>
</tbody>

</table>
</div>
</div>
</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="absenceModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody">
        Loading...
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).on('click','.absent-click',function(){

    let htno=$(this).data('htno');
    let name=$(this).data('name');

    $('#modalTitle').text("Absence Details - "+name+" ("+htno+")");
    $('#modalBody').html("Loading...");

    $.get(window.location.href,{
        ajax:1,
        htno:htno,
        classid:"<?= $classid ?>",
        from_date:"<?= $from_date ?>",
        to_date:"<?= $to_date ?>"
    },function(data){
        $('#modalBody').html(data);
    });

    new bootstrap.Modal(document.getElementById('absenceModal')).show();
});
</script>

</body>
</html>