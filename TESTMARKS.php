<?php
session_start();
include 'db.php';

// Assuming logged-in user
$user_classid = $_SESSION['classid']; // Must be set on login
$message = "";

// Fetch tests for dropdown
$tests = $conn->query("SELECT * FROM tests ORDER BY created_at DESC");

// Handle selection
$selected_test = $_POST['test_id'] ?? null;
$toggle = $_POST['toggle'] ?? 'individual'; // 'individual' or 'team'

$report = [];
if ($selected_test) {
    if ($toggle === 'individual') {
        // Individual Rank & Avg
        $sql = "
        SELECT 
            S.htno, S.name, S.teamid, SUM(M.marks_obtained) AS total_marks
        FROM student_marks M
        JOIN STUDENTS S ON M.htno = S.htno
        JOIN USERS U ON U.classid = S.classid
        WHERE M.test_id = ? AND S.classid = ?
        GROUP BY S.htno
        ORDER BY total_marks DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $selected_test, $user_classid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }

        // Compute average
        $avg_sql = "SELECT AVG(total) AS class_avg FROM (
                        SELECT SUM(marks_obtained) AS total
                        FROM student_marks M
                        JOIN STUDENTS S ON M.htno = S.htno
                        WHERE M.test_id = ? AND S.classid = ?
                        GROUP BY S.htno
                    ) t";
        $stmt_avg = $conn->prepare($avg_sql);
        $stmt_avg->bind_param("is", $selected_test, $user_classid);
        $stmt_avg->execute();
        $avg_result = $stmt_avg->get_result()->fetch_assoc();
        $class_avg = $avg_result['class_avg'];

    } elseif ($toggle === 'team') {
        // Team Avg
        $sql = "
        SELECT S.teamid, AVG(total) AS team_avg
        FROM (
            SELECT S.htno, S.teamid, SUM(M.marks_obtained) AS total
            FROM student_marks M
            JOIN STUDENTS S ON M.htno = S.htno
            WHERE M.test_id = ? AND S.classid = ?
            GROUP BY S.htno
        ) t
        GROUP BY teamid
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $selected_test, $user_classid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Test Report</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h2>Test Report</h2>

<form method="post" class="mb-3 d-flex align-items-center gap-2">
    <select name="test_id" class="form-select" style="width:auto;">
        <option value="">Select Test</option>
        <?php while ($test = $tests->fetch_assoc()) { ?>
            <option value="<?= $test['test_id'] ?>" <?= ($selected_test==$test['test_id'])?'selected':'' ?>>
                <?= $test['test_name'] ?>
            </option>
        <?php } ?>
    </select>

    <select name="toggle" class="form-select" style="width:auto;">
        <option value="individual" <?= ($toggle=='individual')?'selected':'' ?>>Individual Rank & Avg</option>
        <option value="team" <?= ($toggle=='team')?'selected':'' ?>>Team Avg</option>
    </select>

    <button class="btn btn-primary" type="submit">View Report</button>
</form>

<?php if($selected_test): ?>
    <?php if($toggle=='individual'): ?>
        <h4>Class Average: <?= number_format($class_avg,2) ?></h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>HT No</th>
                    <th>Name</th>
                    <th>Team</th>
                    <th>Total Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank=1; foreach($report as $r): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= $r['htno'] ?></td>
                    <td><?= $r['name'] ?></td>
                    <td><?= $r['teamid'] ?></td>
                    <td><?= $r['total_marks'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Average Marks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report as $r): ?>
                <tr>
                    <td><?= $r['teamid'] ?></td>
                    <td><?= number_format($r['team_avg'],2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
