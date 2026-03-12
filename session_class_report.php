<?php
session_start();
include "db.php";

// Check database connection
if (!$conn) {
    die("Database connection failed: " . $conn->connect_error);
}

$session = $_GET['session'] ?? '';
if (empty($session)) {
    die("Invalid session parameter");
}

// Sanitize inputs
$session = $conn->real_escape_string($session);
$filter_class = $_GET['classid'] ?? '';

// Fetch classes list for filter dropdown
$class_stmt = $conn->prepare("SELECT DISTINCT classid FROM guest_feedback WHERE session_name = ? ORDER BY classid");
if (!$class_stmt) {
    die("Prepare failed: " . $conn->error);
}

$class_stmt->bind_param("s", $session);
$class_stmt->execute();
$class_list = $class_stmt->get_result();

// Build main query
$query = "SELECT f.htno, f.name, f.classid, f.topic, f.rating, f.remark, s.teamid 
          FROM guest_feedback f 
          JOIN STUDENTS s ON s.htno = f.htno 
          WHERE f.session_name = ?";

if (!empty($filter_class)) {
    $query .= " AND f.classid = ?";
    $query .= " ORDER BY s.teamid, f.htno";
} else {
    $query .= " ORDER BY f.classid, s.teamid, f.htno";
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($filter_class)) {
    $stmt->bind_param("ss", $session, $filter_class);
} else {
    $stmt->bind_param("s", $session);
}

$stmt->execute();
$res = $stmt->get_result();

// Get session statistics
$stats_query = "SELECT 
    COUNT(DISTINCT f.htno) as student_count,
    COUNT(DISTINCT f.classid) as class_count,
    COUNT(DISTINCT s.teamid) as team_count,
    ROUND(AVG(f.rating), 2) as avg_rating
    FROM guest_feedback f
    JOIN STUDENTS s ON s.htno = f.htno
    WHERE f.session_name = ?";

if (!empty($filter_class)) {
    $stats_query .= " AND f.classid = ?";
}

$stats_stmt = $conn->prepare($stats_query);
if (!$stats_stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($filter_class)) {
    $stats_stmt->bind_param("ss", $session, $filter_class);
} else {
    $stats_stmt->bind_param("s", $session);
}

$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Default values if no data
if (!$stats) {
    $stats = [
        'student_count' => 0,
        'class_count' => 0,
        'team_count' => 0,
        'avg_rating' => '0.00'
    ];
}

function stars($n) {
    $n = floatval($n);
    $full = floor($n);
    $s = "";
    for ($i = 1; $i <= 5; $i++) {
        $s .= ($i <= $full) ? "★" : "☆";
    }
    return $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .filter-section { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-item { background: #ecf0f1; padding: 15px; text-align: center; border-radius: 5px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        .class-header { background: #d6eaf8; font-weight: bold; }
        .team-header { background: #f8f9fa; font-weight: bold; }
        .stars { color: gold; font-size: 18px; }
        .btn { padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Session Report: <?php echo htmlspecialchars($session); ?></h1>
            
            <div class="filter-section">
                <form method="GET">
                    <input type="hidden" name="session" value="<?php echo htmlspecialchars($session); ?>">
                    <label><strong>Filter by Class:</strong></label>
                    <select name="classid" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php 
                        if ($class_list) {
                            while($cl = $class_list->fetch_assoc()) {
                                $selected = ($filter_class == $cl['classid']) ? 'selected' : '';
                                echo "<option value='{$cl['classid']}' $selected>{$cl['classid']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <?php if ($filter_class): ?>
                        <a href="?session=<?php echo urlencode($session); ?>" class="btn">Clear Filter</a>
                    <?php endif; ?>
                </form>
                <?php if ($filter_class): ?>
                    <p><strong>Currently viewing:</strong> <?php echo htmlspecialchars($filter_class); ?></p>
                <?php endif; ?>
            </div>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['student_count']; ?></div>
                    <div>Students</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['class_count']; ?></div>
                    <div>Classes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['team_count']; ?></div>
                    <div>Teams</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['avg_rating']; ?>/5</div>
                    <div>Avg Rating</div>
                </div>
            </div>
        </div>

        <div style="margin: 15px 0;">
            <a href="guest_feedback_report.php" class="btn">← Back to Summary</a>
            <button onclick="window.print()" class="btn">Print Report</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Class</th>
                    <th>Team</th>
                    <th>Student</th>
                    <th>Topic</th>
                    <th>Rating</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($res->num_rows === 0) {
                    echo '<tr><td colspan="7" style="text-align: center; padding: 40px;">No feedback data found.</td></tr>';
                } else {
                    $i = 1;
                    $current_class = "";
                    $current_team = "";
                    
                    while ($row = $res->fetch_assoc()) {
                        // Show class header only when class changes and not filtering
                        if (empty($filter_class) && $current_class != $row['classid']) {
                            echo "<tr class='class-header'><td colspan='7'>Class: {$row['classid']}</td></tr>";
                            $current_class = $row['classid'];
                            $current_team = "";
                        }
                        
                        // Show team header only when team changes
                        if ($current_team != $row['teamid']) {
                            echo "<tr class='team-header'><td colspan='7'>Team: {$row['teamid']}</td></tr>";
                            $current_team = $row['teamid'];
                        }
                        
                        echo "<tr>
                            <td>{$i}</td>
                            <td>{$row['classid']}</td>
                            <td>{$row['teamid']}</td>
                            <td><strong>{$row['name']}</strong><br><small>{$row['htno']}</small></td>
                            <td>" . htmlspecialchars($row['topic']) . "</td>
                            <td><span class='stars'>" . stars($row['rating']) . "</span> {$row['rating']}/5</td>
                            <td>" . nl2br(htmlspecialchars($row['remark'])) . "</td>
                        </tr>";
                        $i++;
                    }
                }
                ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>Report generated on <?php echo date('F j, Y, g:i A'); ?></p>
        </div>
    </div>
</body>
</html>