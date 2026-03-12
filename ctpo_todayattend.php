<?php
session_start();
include 'db.php';

// FIX FOR INFINITYFREE BIG SELECT LIMIT
$conn->query("SET SQL_BIG_SELECTS=1");
date_default_timezone_set('Asia/Kolkata');

// Restrict access to CPTO only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

$classid = $_SESSION['classid'] ?? '';
$today = date('Y-m-d');

// Get selected date (default = today)
$selected_date = $_GET['date'] ?? $today;

// Fetch attendance for selected date
$sql = "
SELECT a.id, s.htno, s.name, s.teamid, s.st_phone, s.f_phone,
       a.status, a.remark, a.ph_no,
       CAST(SUBSTRING_INDEX(s.teamid, '_', -1) AS UNSIGNED) AS teamnum
FROM attendance a
JOIN STUDENTS s ON a.htno = s.htno
WHERE s.classid = ? 
  AND a.att_date = ?
ORDER BY
    CASE WHEN s.teamid IS NULL OR s.teamid = '' THEN 1 ELSE 0 END,
    teamnum ASC,
    CAST(s.htno AS UNSIGNED),
    s.htno
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $classid, $selected_date);
$stmt->execute();
$result = $stmt->get_result();

// Attendance summary
$summary_sql = "SELECT a.htno,
                       SUM(a.status='Present') AS present_days,
                       COUNT(*) AS total_days,
                       s.teamid
                FROM attendance a
                JOIN STUDENTS s ON a.htno = s.htno
                WHERE s.classid = ?
                GROUP BY a.htno, s.teamid
                ORDER BY s.teamid, CAST(a.htno AS UNSIGNED)";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("s", $classid);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

$summary = [];
while ($row = $summary_result->fetch_assoc()) {
    $summary[$row['htno']] = $row;
}

// Save Remarks + Called Numbers (only for today)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all']) && $selected_date === $today) {

    // Update ph_no
    if (isset($_POST['ph_no'])) {
        foreach ($_POST['ph_no'] as $id => $ph) {
            $ph = trim($ph);
            $update = $conn->prepare("UPDATE attendance SET ph_no=? WHERE id=?");
            $update->bind_param("si", $ph, $id);
            $update->execute();
        }
    }

    // Update remarks
    if (isset($_POST['remark'])) {
        foreach ($_POST['remark'] as $id => $remark) {
            $remark = trim($remark);
            $update = $conn->prepare("UPDATE attendance SET remark=? WHERE id=?");
            $update->bind_param("si", $remark, $id);
            $update->execute();
        }
    }

    echo "<script>alert('Updated successfully!');window.location.href='ctpo_todayattend.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CTPO Attendance</title>
<style>
    body {
        background: linear-gradient(135deg, #e3f2fd, #ffe3e3);
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1200px;
        margin: 20px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
        font-size: 22px;
    }
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    select, input[type="date"], button {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    button {
        background-color: #007bff;
        color: white;
        cursor: pointer;
        border: none;
    }
    button:hover {
        background-color: #0056b3;
    }
    .table-wrapper {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        min-width: 700px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
        font-size: 14px;
    }
    th {
        background-color: #007bff;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .status-present {
        color: #28a745;
        font-weight: bold;
    }
    .status-absent {
        color: #dc3545;
        font-weight: bold;
    }
    input[type="text"]:disabled {
        background: #eee;
        color: #555;
    }
    .actions {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .print-btn {
        background: #28a745;
    }
    .print-btn:hover {
        background: #1e7e34;
    }
    @media print {
        .top-bar, .actions {
            display: none;
        }
        body { background: white; }
    }
</style>
</head>
<body>
<div class="container">
    <h2>📅 Attendance (<?= htmlspecialchars($selected_date) ?>)</h2>

    <div class="top-bar">
        <form method="GET">
            <label><strong>Date:</strong></label>
            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" max="<?= $today ?>">

            <label><strong>Status:</strong></label>
            <select name="status">
                <option value="">All</option>
                <option value="Present" <?= ($_GET['status'] ?? '') === 'Present' ? 'selected' : '' ?>>Present</option>
                <option value="Absent" <?= ($_GET['status'] ?? '') === 'Absent' ? 'selected' : '' ?>>Absent</option>
            </select>

            <button type="submit">🔍 Filter</button>
        </form>

        <button class="print-btn" onclick="window.print()">🖨️ Print</button>
    </div>

    <form method="POST">
        <div class="table-wrapper">
        <table>
            <tr>
                <th>Roll No</th>
                <th>Name</th>
                <th>Team ID</th>
                <th>Contact Numbers</th>
                <th>Present / Total</th>
                <th>Status</th>
                <th>Called No</th>
                <th>Remark</th>
            </tr>

            <?php
            $status_filter = $_GET['status'] ?? '';

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    if ($status_filter && $row['status'] !== $status_filter) continue;

                    $htno = $row['htno'];
                    $present = $summary[$htno]['present_days'] ?? 0;
                    $total = $summary[$htno]['total_days'] ?? 0;

                    $status_class = $row['status'] === 'Present' ? 'status-present' : 'status-absent';

                    // Editable only if today + absent
                    $editable = ($selected_date === $today && $row['status'] === 'Absent');
                    $disabled = $editable ? '' : 'disabled';

                    echo "<tr>
                        <td>{$row['htno']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['teamid']}</td>

                        <td>
                            ST: " . htmlspecialchars($row['st_phone']) . "<br>
                            F: " . htmlspecialchars($row['f_phone']) . "
                        </td>

                        <td>{$present} / {$total}</td>
                        <td class='{$status_class}'>{$row['status']}</td>

                        <td>
                            <input type='text' name='ph_no[{$row['id']}]'
                                   value='" . htmlspecialchars($row['ph_no']) . "' $disabled>
                        </td>

                        <td>
                            <input type='text' name='remark[{$row['id']}]'
                                   value='" . htmlspecialchars($row['remark']) . "' $disabled>
                        </td>
                    </tr>";

                }
            } else {
                echo "<tr><td colspan='9'>No attendance records found for this date.</td></tr>";
            }
            ?>
        </table>
        </div>

        <div class="actions">
            <button type="submit" name="save_all" <?= ($selected_date !== $today) ? 'disabled title="Updates only allowed for today"' : '' ?>>
                💾 Save
            </button>
        </div>
    </form>
</div>
</body>
</html>
