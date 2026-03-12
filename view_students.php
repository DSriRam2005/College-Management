<?php
session_start();
include 'db.php';

/* ===============================
   CSV DOWNLOAD
   =============================== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $search = $_GET['search'] ?? '';

    if ($search) {
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                u.name  AS cpto_name,
                u.ph_no AS cpto_phone
            FROM STUDENTS s
            LEFT JOIN USERS u
                ON u.classid = s.classid
               AND u.role = 'CPTO'
            WHERE s.htno LIKE ?
               OR s.name LIKE ?
               OR s.classid LIKE ?
            ORDER BY s.id DESC
        ");
        $like = "%$search%";
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("
            SELECT 
                s.*,
                u.name  AS cpto_name,
                u.ph_no AS cpto_phone
            FROM STUDENTS s
            LEFT JOIN USERS u
                ON u.classid = s.classid
               AND u.role = 'CPTO'
            ORDER BY s.id DESC
        ");
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=students_export.csv');

    $out = fopen('php://output', 'w');

    // CSV Header
    fputcsv($out, [
        'ID','HT No','Name','Prog','Year','ClassID','Team',
        'Student Phone','Father Phone',
        'CPTO Phone','CPTO Name','Ref','Ref Emp ID',
        'Total Due (12.9)','Total Due (Today)','Debarred'
    ]);

    while ($row = $result->fetch_assoc()) {

        $tot_due_12_9 =
            ($row['tfdue_12_9'] ?? 0) +
            ($row['otdues_12_9'] ?? 0) +
            ($row['busdue_12_9'] ?? 0) +
            ($row['hosdue_12_9'] ?? 0) +
            ($row['olddue_12_9'] ?? 0);

        $tot_due_today =
            ($row['tfdue_today'] ?? 0) +
            ($row['otdues_today'] ?? 0) +
            ($row['busdue_today'] ?? 0) +
            ($row['hosdue_today'] ?? 0) +
            ($row['olddue_today'] ?? 0);

        fputcsv($out, [
            $row['id'],
            $row['htno'],
            $row['name'],
            $row['prog'],
            $row['year'],
            $row['classid'],
            $row['teamid'],
            $row['st_phone'],
            $row['f_phone'],
            $row['ref'] ?? '',
			$row['REFEMPID'] ?? '',
            $row['cpto_phone'] ?? '',
            $row['cpto_name'] ?? '',
            number_format($tot_due_12_9, 2, '.', ''),
            number_format($tot_due_today, 2, '.', ''),
            $row['debarred'] ? 'YES' : 'NO'
        ]);
    }

    fclose($out);
    exit;
}

/* ===============================
   AJAX: Debar / Undebar
   =============================== */
if (isset($_POST['toggle_debar'], $_POST['id'], $_POST['status'])) {
    $id = (int) $_POST['id'];
    $status = (int) $_POST['status'];

    $stmt = $conn->prepare("UPDATE STUDENTS SET debarred = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();

    echo "success";
    exit;
}

/* ===============================
   Search
   =============================== */
$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            u.name  AS cpto_name,
            u.ph_no AS cpto_phone
        FROM STUDENTS s
        LEFT JOIN USERS u
            ON u.classid = s.classid
           AND u.role = 'CPTO'
        WHERE s.htno LIKE ?
           OR s.name LIKE ?
           OR s.classid LIKE ?
        ORDER BY s.id DESC
    ");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT 
            s.*,
            u.name  AS cpto_name,
            u.ph_no AS cpto_phone
        FROM STUDENTS s
        LEFT JOIN USERS u
            ON u.classid = s.classid
           AND u.role = 'CPTO'
        ORDER BY s.id DESC
    ");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Students List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="p-4">
<div class="container-fluid">

<h3 class="mb-4">Students List</h3>

<form method="get" class="d-flex mb-3">
    <input type="text" name="search" class="form-control me-2"
           placeholder="Search by HT No / Name / ClassID"
           value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary">Search</button>
    <?php if ($search): ?>
        <a href="?" class="btn btn-secondary ms-2">Reset</a>
    <?php endif; ?>
</form>
    <a href="?export=csv&search=<?= urlencode($search) ?>"
   class="btn btn-success ms-2">
   Download CSV
</a>


<div class="table-responsive">
<table class="table table-bordered table-sm table-striped align-middle text-center">

<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>HT No</th>
    <th>Name</th>
    <th>Prog</th>
    <th>Year</th>
    <th>ClassID</th>
    <th>Team</th>
    <th>Student Phone</th>
    <th>Father Phone</th>
    <th>CPTO</th>
    <th>CPTO Name</th>
    <th>Ref</th>
	<th>Ref Emp ID</th>
    <th>Total Due (12.9)</th>
    <th>Total Due (Today)</th>
    <th>Debarred</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php if ($result && $result->num_rows > 0): ?>
<?php while ($row = $result->fetch_assoc()): ?>

<?php
$tot_due_12_9 =
    ($row['tfdue_12_9'] ?? 0) +
    ($row['otdues_12_9'] ?? 0) +
    ($row['busdue_12_9'] ?? 0) +
    ($row['hosdue_12_9'] ?? 0) +
    ($row['olddue_12_9'] ?? 0);

$tot_due_today =
    ($row['tfdue_today'] ?? 0) +
    ($row['otdues_today'] ?? 0) +
    ($row['busdue_today'] ?? 0) +
    ($row['hosdue_today'] ?? 0) +
    ($row['olddue_today'] ?? 0);
?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['htno']) ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['prog']) ?></td>
    <td><?= htmlspecialchars($row['year']) ?></td>
    <td><?= htmlspecialchars($row['classid']) ?></td>
    <td><?= htmlspecialchars($row['teamid']) ?></td>

    <td><?= htmlspecialchars($row['st_phone']) ?></td>
    <td><?= htmlspecialchars($row['f_phone']) ?></td>

    <td><?= htmlspecialchars($row['cpto_phone'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['cpto_name'] ?? '—') ?></td>
	<td><?= htmlspecialchars($row['ref'] ?? '—') ?></td>
	<td><?= htmlspecialchars($row['REFEMPID'] ?? '—') ?></td>
    <td><?= number_format($tot_due_12_9, 2) ?></td>
    <td><?= number_format($tot_due_today, 2) ?></td>

    <td>
        <?= $row['debarred']
            ? '<span class="badge bg-danger">Yes</span>'
            : '<span class="badge bg-success">No</span>' ?>
    </td>

    <td>
        <button
            class="btn btn-sm <?= $row['debarred'] ? 'btn-success' : 'btn-danger' ?> toggle-debar"
            data-id="<?= $row['id'] ?>"
            data-status="<?= $row['debarred'] ? 0 : 1 ?>">
            <?= $row['debarred'] ? 'Undebar' : 'Debar' ?>
        </button>
    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr>
    <td colspan="15" class="text-muted text-center">No records found</td>
</tr>
<?php endif; ?>
</tbody>

</table>
</div>

</div>

<script>
$(document).on('click', '.toggle-debar', function () {
    $.post('', {
        toggle_debar: 1,
        id: $(this).data('id'),
        status: $(this).data('status')
    }, function (res) {
        if (res.trim() === 'success') location.reload();
        else alert('Update failed');
    });
});
</script>

</body>
</html>
