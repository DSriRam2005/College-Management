<?php
require_once "db.php";

$avg = [];
$remarks = [];
$classid = "";
$hide_identity = true; // Flag to hide names and HTNos

// -------------------- LOAD CLASSID LIST WITH COUNT --------------------
$classList = [];
$res = $conn->query("
    SELECT 
        classid, 
        COUNT(*) as student_count 
    FROM dip25_feedback 
    GROUP BY classid 
    ORDER BY classid
");
while ($row = $res->fetch_assoc()) {
    $classList[] = [
        'classid' => $row['classid'],
        'count' => $row['student_count']
    ];
}

// -------------------- WHEN CLASSID IS SUBMITTED --------------------
if (isset($_POST['get_report']) || isset($_POST['classid'])) {
    $classid = $_POST['classid'] ?? '';

    $sql = "
        SELECT
            AVG(english_rating) AS english,
            AVG(maths_rating) AS maths,
            AVG(physics_rating) AS physics,
            AVG(chemistry_rating) AS chemistry,
            AVG(bce_rating) AS bce,
            AVG(clang_rating) AS clang,
            AVG(eg_rating) AS eg,
            COUNT(*) as total_students
        FROM dip25_feedback
        WHERE classid = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $avg = $result->fetch_assoc();
        $total_students = $avg['total_students'] ?? 0;
    } else {
        $avg = [];
        $total_students = 0;
    }
    $stmt->close();
}

// -------------------- REMARKS VIEW --------------------
if (isset($_GET['subject']) && isset($_GET['classid'])) {
    $subject = $_GET['subject'];
    $classid = $_GET['classid'];
    
    // Validate subject name to prevent SQL injection
    $allowed_subjects = ['english', 'maths', 'physics', 'chemistry', 'bce', 'clang', 'eg'];
    if (!in_array($subject, $allowed_subjects)) {
        die("Invalid subject specified.");
    }
    
    $subject_remarks = $subject . "_remarks";
    
    // Query to get remarks - hide name and htno
    $sql = "SELECT $subject_remarks AS remarks, 
                   name, htno,
                   ROW_NUMBER() OVER (ORDER BY id) as student_num
            FROM dip25_feedback 
            WHERE classid=? AND $subject_remarks IS NOT NULL AND $subject_remarks != ''";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $remarks = $stmt->get_result();
    
    // Get count for this class
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM dip25_feedback WHERE classid=?");
    $countStmt->bind_param("s", $classid);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $total_students = $countResult['count'] ?? 0;
    $countStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Report</title>
<style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --secondary-color: #4f46e5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --bg-gray: #f3f4f6;
            --border-color: #e5e7eb;
            --border-dark: #d1d5db;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.05);
            --radius-sm: 4px;
            --radius: 8px;
            --radius-md: 10px;
            --radius-lg: 12px;
            --radius-xl: 20px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-light);
            padding: 16px;
            min-height: 100vh;
        }
        
        /* Typography */
        h1, h2, h3, h4 {
            font-weight: 600;
            line-height: 1.3;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        h1 {
            font-size: 1.75rem;
            text-align: center;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        h3 {
            font-size: 1.25rem;
        }
        
        h4 {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }
        
        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        p {
            margin-bottom: 1rem;
        }
        
        /* Layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        /* Class ID Grid with Count Badge */
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 2rem;
        }
        
        @media (min-width: 768px) {
            .class-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 16px;
            }
        }
        
        .class-btn {
            display: block;
            width: 100%;
            padding: 16px 8px;
            background: var(--bg-white);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        
        .class-btn:hover, .class-btn:focus {
            background: var(--primary-light);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .class-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .class-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--success-color);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: var(--radius-xl);
            min-width: 24px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .class-btn.active .class-count-badge {
            background: white;
            color: var(--primary-color);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--bg-white) 0%, var(--bg-gray) 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .stat-label {
            display: block;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .stat-bar {
            height: 6px;
            background: var(--border-color);
            border-radius: var(--radius-xl);
            margin-top: 12px;
            overflow: hidden;
        }
        
        .stat-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--radius-xl);
            width: 0%;
            transition: width 1s ease;
        }
        
        /* Ratings Table */
        .ratings-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background: var(--bg-white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .ratings-table th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 16px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .ratings-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .ratings-table tr:last-child td {
            border-bottom: none;
        }
        
        .ratings-table tr:hover {
            background-color: var(--bg-gray);
        }
        
        .ratings-table .subject-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .ratings-table .avg-rating {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-secondary {
            background: var(--bg-gray);
            color: var(--text-primary);
            border: 1px solid var(--border-dark);
        }
        
        .btn-secondary:hover, .btn-secondary:focus {
            background: var(--border-color);
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--success-color), #34d399);
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #0da271, #2cc58a);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        /* Back Button Container */
        .back-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 16px;
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-gray);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: var(--border-color);
            transform: translateX(-2px);
        }
        
        /* Remarks Table */
        .remarks-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin: 2rem 0;
        }
        
        .remarks-table th {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 16px;
            font-size: 0.9rem;
        }
        
        .remarks-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }
        
        .remarks-table tr:last-child td {
            border-bottom: none;
        }
        
        .remarks-table .student-info {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .remarks-table .student-htno {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 4px;
        }
        
        /* Header and Navigation */
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2.25rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        /* Class Summary Info */
        .class-summary {
            background: linear-gradient(135deg, var(--primary-light), #e0e7ff);
            border: 1px solid var(--primary-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .class-info {
            flex: 1;
            min-width: 250px;
        }
        
        .class-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        
        .class-stats {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .stat-label-small {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .card {
                padding: 20px;
            }
            
            .ratings-table, .remarks-table {
                display: block;
                overflow-x: auto;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .class-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }
            
            .back-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .class-summary {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        /* Loading Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Rating Color Coding */
        .rating-5 { color: #10b981; }
        .rating-4 { color: #34d399; }
        .rating-3 { color: #f59e0b; }
        .rating-2 { color: #f97316; }
        .rating-1 { color: #ef4444; }
        
        /* Selection Summary */
        .selection-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .total-classes {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .total-students {
            background: var(--success-color);
            color: white;
            padding: 4px 12px;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">DIP 25</div>
            <h1>Feedback Analytics Dashboard</h1>
            <p class="subtitle">View class-wise feedback reports and student remarks</p>
        </div>

        <!-- Back Navigation (for remarks view) -->
        <?php if (isset($_GET['subject'])) { ?>
        <div class="back-container">
            <a href="dip_hashed_report.php?classid=<?= htmlspecialchars($classid) ?>" class="back-btn">
                ← Back to Class Report
            </a>
            <div class="class-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_students ?></span>
                    <span class="stat-label-small">Students Submitted</span>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- Class ID Selection -->
        <div class="card">
            <div class="selection-summary">
                <div class="total-classes">
                    <?= count($classList) ?> class<?= count($classList) !== 1 ? 'es' : '' ?> available
                </div>
                <?php 
                $totalAllStudents = 0;
                foreach ($classList as $class) {
                    $totalAllStudents += $class['count'];
                }
                ?>
                <div class="total-students">
                    <?= $totalAllStudents ?> total submissions
                </div>
            </div>
            
            <h2>Select Class</h2>
            
            <div class="class-grid">
                <?php foreach ($classList as $class) { 
                    $c = htmlspecialchars($class['classid']);
                    $count = $class['count'];
                    $isActive = ($class['classid'] == $classid && !isset($_GET['subject']));
                ?>
                    <form method="post" class="class-btn-form">
                        <input type="hidden" name="classid" value="<?= $c ?>">
                        <button type="submit" name="get_report" class="class-btn <?= $isActive ? 'active' : '' ?>">
                            <?= $c ?>
                            <span class="class-count-badge"><?= $count ?></span>
                        </button>
                    </form>
                <?php } ?>
            </div>
            
            <?php if (empty($classList)) { ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h3>No Data Available</h3>
                    <p>No feedback has been submitted yet.</p>
                </div>
            <?php } ?>
        </div>

        <!-- Class Summary Info (when a class is selected) -->
        <?php if (!empty($classid) && !isset($_GET['subject'])) { ?>
        <div class="class-summary fade-in">
            <div class="class-info">
                <div class="class-title"><?= htmlspecialchars($classid) ?></div>
                <p>Viewing detailed feedback report for this class</p>
            </div>
            <div class="class-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_students ?? 0 ?></span>
                    <span class="stat-label-small">Students Submitted</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">7</span>
                    <span class="stat-label-small">Subjects</span>
                </div>
                <div class="stat-item">
                    <a href="dip_hashed_report.php" class="btn btn-secondary btn-small">
                        Change Class
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- SHOW AVERAGE RATINGS -->
        <?php if (!empty($avg) && isset($avg['english']) && $avg['english'] !== null && !isset($_GET['subject'])) { ?>

        <div class="card fade-in">
            <h2>Class Performance Overview</h2>
            <h4><?= htmlspecialchars($classid) ?></h4>
            
            <!-- Stats Summary -->
            <div class="stats-grid">
                <?php
                $subjects = [
                    "english" => ["English", "📚"],
                    "maths" => ["Maths", "➗"],
                    "physics" => ["Physics", "⚛️"],
                    "chemistry" => ["Chemistry", "🧪"],
                    "bce" => ["BCE", "🏛️"],
                    "clang" => ["C Language", "💻"],
                    "eg" => ["EG", "📐"]
                ];
                
                $totalAvg = 0;
                $validSubjects = 0;
                $subjectRatings = [];
                
                foreach ($subjects as $key => $data) {
                    if (isset($avg[$key]) && $avg[$key] !== null) {
                        $totalAvg += $avg[$key];
                        $validSubjects++;
                        $subjectRatings[$key] = $avg[$key];
                    }
                }
                
                $overallAvg = $validSubjects > 0 ? $totalAvg / $validSubjects : 0;
                
                // Find highest and lowest rated subjects correctly
                if (!empty($subjectRatings)) {
                    $maxRating = max($subjectRatings);
                    $minRating = min($subjectRatings);
                    
                    // Get all subjects with max rating (in case of ties)
                    $maxSubjects = array_keys($subjectRatings, $maxRating);
                    $minSubjects = array_keys($subjectRatings, $minRating);
                    
                    $maxLabel = $subjects[$maxSubjects[0]][0];
                    $minLabel = $subjects[$minSubjects[0]][0];
                    
                    // Handle multiple subjects with same rating
                    if (count($maxSubjects) > 1) {
                        $maxLabel = "";
                        foreach ($maxSubjects as $subj) {
                            $maxLabel .= $subjects[$subj][0] . ", ";
                        }
                        $maxLabel = rtrim($maxLabel, ", ");
                    }
                    
                    if (count($minSubjects) > 1) {
                        $minLabel = "";
                        foreach ($minSubjects as $subj) {
                            $minLabel .= $subjects[$subj][0] . ", ";
                        }
                        $minLabel = rtrim($minLabel, ", ");
                    }
                } else {
                    $maxRating = 0;
                    $minRating = 0;
                    $maxLabel = "N/A";
                    $minLabel = "N/A";
                }
                ?>
                
                <div class="stat-card">
                    <span class="stat-label">Overall Average</span>
                    <span class="stat-value"><?= number_format($overallAvg, 2) ?>/5</span>
                    <div class="stat-bar">
                        <div class="stat-fill" style="width: <?= ($overallAvg / 5 * 100) ?>%"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <span class="stat-label">Highest Rated</span>
                    <span class="stat-value"><?= number_format($maxRating, 2) ?></span>
                    <span class="stat-label"><?= $maxLabel ?></span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-label">Lowest Rated</span>
                    <span class="stat-value"><?= number_format($minRating, 2) ?></span>
                    <span class="stat-label"><?= $minLabel ?></span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-label">Total Responses</span>
                    <span class="stat-value"><?= $total_students ?? 0 ?></span>
                    <span class="stat-label">Students</span>
                </div>
            </div>

            <!-- Detailed Ratings Table -->
            <table class="ratings-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Average Rating</th>
                        <th>View Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $key => $data) { 
                        list($label, $icon) = $data;
                        $rating = isset($avg[$key]) && $avg[$key] !== null ? number_format($avg[$key], 2) : "N/A";
                        $ratingClass = 'rating-' . round($avg[$key] ?? 0);
                    ?>
                        <tr>
                            <td class="subject-name"><?= $icon ?> <?= $label ?></td>
                            <td>
                                <span class="avg-rating <?= $ratingClass ?>"><?= $rating ?></span>/5
                            </td>
                            <td>
                                <a href="?classid=<?= $classid ?>&subject=<?= $key ?>" class="btn-view">
                                    View Remarks (<?= $total_students ?? 0 ?>)
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php } elseif (!empty($classid) && isset($avg['english']) && $avg['english'] === null && !isset($_GET['subject'])) { ?>
        <div class="card fade-in">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Data for <?= htmlspecialchars($classid) ?></h3>
                <p>No feedback has been submitted for this class yet.</p>
            </div>
        </div>
        <?php } ?>

        <!-- SHOW REMARKS -->
        <?php if (isset($remarks) && $remarks && $remarks->num_rows > 0) { ?>

        <div class="card fade-in">
            <h2>Student Remarks for <?= ucfirst(htmlspecialchars($_GET['subject'])) ?></h2>
            <h4>Class: <?= htmlspecialchars($classid) ?></h4>
            
            <div style="margin-bottom: 1.5rem; padding: 12px; background: var(--bg-gray); border-radius: var(--radius);">
                <strong><?= $remarks->num_rows ?> student<?= $remarks->num_rows !== 1 ? 's' : '' ?> submitted remarks</strong>
            </div>
            
            <table class="remarks-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Student</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 0;
                    while ($r = $remarks->fetch_assoc()) { 
                        $counter++;
                    ?>
                        <tr>
                            
                            <td>
                                <!-- Hide name and HTNo - show only student number -->
                                <div class="student-info">Student #<?= $r['student_num'] ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">
                                    Anonymous Feedback
                                </div>
                            </td>
                            <td style="text-align: left;"><?= nl2br(htmlspecialchars($r['remarks'])) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="dip_hashed_report.php?classid=<?= $classid ?>" class="btn btn-primary">
                    ← Back to Class Report
                </a>
                <a href="dip_hashed_report.php" class="btn btn-secondary" style="margin-left: 12px;">
                    Select Different Class
                </a>
            </div>
        </div>

        <?php } elseif (isset($_GET['subject'])) { ?>
        <div class="card fade-in">
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <h3>No Remarks Available</h3>
                <p>No remarks have been submitted for <?= htmlspecialchars($_GET['subject']) ?> in this class.</p>
            </div>
        </div>
        <?php } ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.875rem;">
            <p>DIP 25 Feedback Analytics &copy; <?= date('Y') ?></p>
            <p>Total submissions: <?= $totalAllStudents ?> from <?= count($classList) ?> classes</p>
        </div>
    </div>

        <script>
        // Add animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat bars
            setTimeout(() => {
                document.querySelectorAll('.stat-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 300);
            
            // Add active class to clicked class button
            document.querySelectorAll('.class-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.class-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Preserve class selection when coming back from remarks view
            const urlParams = new URLSearchParams(window.location.search);
            const urlClassId = urlParams.get('classid');
            
            if (urlClassId) {
                // Find and activate the button for this class
                document.querySelectorAll('.class-btn').forEach(btn => {
                    const form = btn.closest('form');
                    const input = form.querySelector('input[name="classid"]');
                    if (input && input.value === urlClassId) {
                        btn.classList.add('active');
                    }
                });
            }
            
            // Add loading state to buttons
            const buttons = document.querySelectorAll('.class-btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<span style="font-size: 0.9rem;">Loading...</span>';
                    this.classList.add('loading');
                    
                    // Restore after 3 seconds if still on page
                    setTimeout(() => {
                        if (this.innerHTML.includes('Loading')) {
                            this.innerHTML = originalText;
                            this.classList.remove('loading');
                        }
                    }, 3000);
                });
            });
            
            // Highlight rows on hover for touch devices
            const tableRows = document.querySelectorAll('tr');
            tableRows.forEach(row => {
                row.addEventListener('touchstart', function() {
                    this.style.backgroundColor = 'var(--bg-gray)';
                });
                
                row.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 200);
                });
            });
            
            // Store the selected class in session storage
            document.querySelectorAll('.class-btn-form').forEach(form => {
                form.addEventListener('submit', function() {
                    const classId = this.querySelector('input[name="classid"]').value;
                    sessionStorage.setItem('selectedClass', classId);
                });
            });
            
            // Try to restore selection from session storage
            if (!urlClassId) {
                const savedClass = sessionStorage.getItem('selectedClass');
                if (savedClass) {
                    document.querySelectorAll('.class-btn').forEach(btn => {
                        const form = btn.closest('form');
                        const input = form.querySelector('input[name="classid"]');
                        if (input && input.value === savedClass) {
                            btn.classList.add('active');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>