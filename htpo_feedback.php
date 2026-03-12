<?php
session_start();
include 'db2.php'; // ✅ DB connection

// ✅ Allow only HTPO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HTPO') {
    header("Location: index.php");
    exit();
}

// ✅ Get HTPO’s year + username from session
$htpo_year  = $_SESSION['year'] ?? null;
$htpo_user  = $_SESSION['username'] ?? "";

// ✅ Detect college from HTPO username (example: 25KTHTPO → KIET)
$college_filter = null;
if (stripos($htpo_user, "KT") !== false) {
    $college_filter = "KIET";
} elseif (stripos($htpo_user, "KW") !== false) {
    $college_filter = "KIEW";
}

// ✅ Branch filter (optional)
$branch_filter = $_GET['branch'] ?? "";

// ✅ Search keyword (optional)
$search = $_GET['search'] ?? "";

// ✅ Fetch distinct branches (restricted by year + college)
$branch_sql = "SELECT DISTINCT classid 
               FROM STUDENTS 
               WHERE year = ? AND college = ?";
$stmt = $conn->prepare($branch_sql);
$stmt->bind_param("is", $htpo_year, $college_filter);
$stmt->execute();
$branch_result = $stmt->get_result();

// ✅ Build feedback query
$sql = "SELECT f.id, f.htno, s.name, s.classid, s.year, s.college, f.feedback_text, f.created_at
        FROM FEEDBACK f
        JOIN STUDENTS s ON f.htno = s.htno
        WHERE s.year = ? AND s.college = ?";

$params = [$htpo_year, $college_filter];
$types  = "is";

if (!empty($branch_filter)) {
    $sql .= " AND s.classid = ?";
    $params[] = $branch_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (s.htno LIKE ? OR s.name LIKE ? OR f.feedback_text LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY f.created_at DESC";

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
<title>HTPO - Student Feedback</title>
<style>
    body { font-family: Arial, sans-serif; background: #f4f6f7; margin: 0; padding: 20px; }
    h1 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
    form { text-align: center; margin-bottom: 20px; }
    input, select, button { padding: 8px 12px; font-size: 14px; margin: 5px; }
    table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
    th { background: #34495e; color: #fff; text-align: center; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #f1f1f1; }
    .feedback-text { max-width: 400px; word-wrap: break-word; white-space: pre-line; }
    .date { color: #555; font-size: 13px; }
</style>
</head>
<body>

<h1>📝 Student Feedback (<?= htmlspecialchars($college_filter) ?> - Year <?= htmlspecialchars($htpo_year) ?>)</h1>

<!-- ✅ Branch + Search Filter -->
<form method="get" action="">
    <label for="branch">Branch:</label>
    <select name="branch" id="branch">
        <option value="">-- All Branches --</option>
        <?php while ($branch = $branch_result->fetch_assoc()): ?>
            <option value="<?= $branch['classid']; ?>" <?= ($branch_filter == $branch['classid']) ? "selected" : ""; ?>>
                <?= $branch['classid']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input type="text" name="search" placeholder="Search by HT No, Name or Feedback" value="<?= htmlspecialchars($search); ?>">

    <button type="submit">🔍 Search</button>
</form>

<table>
    <tr>
        <th>ID</th>
        <th>Hall Ticket</th>
        <th>Name</th>
        <th>Class</th>
        <th>Feedback</th>
        <th>Date</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= $row['htno']; ?></td>
            <td><?= $row['name']; ?></td>
            <td><?= $row['classid']; ?></td>
            <td class="feedback-text"><?= nl2br(htmlspecialchars($row['feedback_text'])); ?></td>
            <td class="date"><?= date("d-M-Y h:i A", strtotime($row['created_at'])); ?></td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No feedback found.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
