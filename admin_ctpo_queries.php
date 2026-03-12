<?php
session_start();
include 'db.php';

/* ACCESS CONTROL – ADMIN ONLY */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit;
}

/* HANDLE ADMIN REPLY */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $reply   = trim($_POST['admin_reply']);
    $queryId = intval($_POST['query_id']);

    if ($reply && $queryId > 0) {
        $stmt = $conn->prepare("
            UPDATE ctpo_queries
            SET admin_reply = ?, replied_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reply, $queryId);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_ctpo_queries.php");
        exit;
    }
}

/*
 ORDERING LOGIC
 1️⃣ Pending first (admin_reply IS NULL) → oldest first
 2️⃣ Replied next → latest first
*/
$result = $conn->query("
    SELECT id, classid, ctpo_query, admin_reply, created_at, replied_at
    FROM ctpo_queries
    ORDER BY
        CASE WHEN admin_reply IS NULL THEN 0 ELSE 1 END,
        CASE 
            WHEN admin_reply IS NULL THEN created_at
            ELSE replied_at
        END ASC,
        replied_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin | CPTO Queries</title>

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

.table thead th {
    background: #2c3e50;
    color: #fff;
    vertical-align: middle;
}

.table td {
    vertical-align: top;
}

.status-pending {
    color: #dc3545;
    font-weight: 600;
}

.status-replied {
    color: #198754;
    font-weight: 600;
}

textarea {
    resize: none;
}
</style>
</head>

<body>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="page-title">CPTO Queries – Admin Panel</h4>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">No CPTO queries found.</div>
    <?php else: ?>

    <div class="table-responsive shadow-sm bg-white">
        <table class="table table-bordered table-hover mb-0">
            <thead>
                <tr>
                    <th width="4%">S.No</th>
                    <th width="10%">Class</th>
                    <th width="32%">CPTO Query</th>
                    <th width="36%">Admin Reply</th>
                    <th width="10%">Status</th>
                    <th width="8%">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>

                    <td><?= $sno++ ?></td>

                    <td><?= htmlspecialchars($row['classid']) ?></td>

                    <td>
                        <?= nl2br(htmlspecialchars($row['ctpo_query'])) ?>
                    </td>

                    <td>
                        <?php if ($row['admin_reply']): ?>
                            <?= nl2br(htmlspecialchars($row['admin_reply'])) ?>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="query_id" value="<?= $row['id'] ?>">
                                <textarea name="admin_reply" class="form-control mb-2"
                                          rows="2" required></textarea>
                                <button name="reply_submit"
                                        class="btn btn-success btn-sm">
                                    Send Reply
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($row['admin_reply']): ?>
                            <span class="status-replied">Replied</span>
                        <?php else: ?>
                            <span class="status-pending">Pending</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($row['admin_reply']): ?>
                            <?= date('d-M-Y', strtotime($row['replied_at'])) ?><br>
                            <small class="text-muted">
                                <?= date('h:i A', strtotime($row['replied_at'])) ?>
                            </small>
                        <?php else: ?>
                            <?= date('d-M-Y', strtotime($row['created_at'])) ?><br>
                            <small class="text-muted">
                                <?= date('h:i A', strtotime($row['created_at'])) ?>
                            </small>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

</div>

</body>
</html>
