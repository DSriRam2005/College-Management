<?php
session_start();

/* FORCE IST EVERYWHERE */
date_default_timezone_set('Asia/Kolkata');

include 'db.php';

/* ACCESS CONTROL */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit;
}

$classid = $_SESSION['classid'];

/* SUBMIT QUERY */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
    $query = trim($_POST['ctpo_query']);

    if ($query !== '') {
        $createdAt = date('Y-m-d H:i:s'); // IST time

        $stmt = $conn->prepare("
            INSERT INTO ctpo_queries (classid, ctpo_query, created_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $classid, $query, $createdAt);
        $stmt->execute();
        $stmt->close();

        header("Location: cpto_queries.php");
        exit;
    }
}

/* FETCH PREVIOUS QUERIES */
$stmt = $conn->prepare("
    SELECT id, ctpo_query, admin_reply, created_at
    FROM ctpo_queries
    WHERE classid = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("s", $classid);
$stmt->execute();
$queries = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>CPTO Queries</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}
.page-title {
    font-weight: 600;
    color: #2c3e50;
}
.card-custom {
    border-radius: 8px;
    border: none;
}
.table thead th {
    background: #2c3e50;
    color: #fff;
}
.badge-pending {
    background: #dc3545;
}
.badge-replied {
    background: #198754;
}
textarea {
    resize: none;
}
.footer-note {
    font-size: 13px;
    color: #6c757d;
}
</style>
</head>

<body>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="page-title">CTPO Queries – <?= htmlspecialchars($classid) ?></h4>
    </div>

    <div class="card card-custom shadow-sm mb-4">
        <div class="card-header fw-bold bg-white">
            Raise New Query
        </div>
        <div class="card-body">
            <form method="post">
                <textarea name="ctpo_query" class="form-control mb-3" rows="3"
                          placeholder="Type your query clearly..." required></textarea>
                <button name="submit_query" class="btn btn-primary">
                    Submit Query
                </button>
            </form>
        </div>
    </div>

    <h5 class="mb-3">Previous Queries</h5>

    <?php if ($queries->num_rows === 0): ?>
        <div class="alert alert-info">No queries raised yet.</div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover shadow-sm bg-white">
            <thead>
                <tr>
                    <th width="5%">S.No</th>
                    <th width="45%">Query</th>
                    <th width="35%">Admin Reply</th>
                    <th width="15%">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; ?>
                <?php while ($row = $queries->fetch_assoc()): ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= nl2br(htmlspecialchars($row['ctpo_query'])) ?></td>
                    <td>
                        <?php if ($row['admin_reply']): ?>
                            <span class="badge badge-replied mb-1">Replied</span><br>
                            <?= nl2br(htmlspecialchars($row['admin_reply'])) ?>
                        <?php else: ?>
                            <span class="badge badge-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= date('d-M-Y', strtotime($row['created_at'])) ?><br>
                        <small class="text-muted">
                            <?= date('h:i A', strtotime($row['created_at'])) ?>
                        </small>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

    <div class="footer-note mt-3">
        * Queries are visible only to your class CPTO and Admin.
    </div>

</div>

</body>
</html>
