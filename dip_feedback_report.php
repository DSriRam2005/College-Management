<?php
require_once "db.php";

$avg = [];
$remarks = [];
$classid = "";
$other_remarks = [];

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

// -------------------- CLASS ID GROUPING LOGIC --------------------
$classGroups = [
    'KIET' => [
        '25KTCMEA' => 'KIET SEC1',
        '25KTCMEB' => 'KIET SEC2',
        '25KTCMEC' => 'KIET SEC3'
    ],
    'KIEW' => [
        '25KWCMEA' => 'KIEW SEC1',
        '25KWCMEB' => 'KIEW SEC2'
    ]
];

// Group actual available classes from database
$availableGroups = [];

foreach ($classGroups as $blockName => $classes) {
    foreach ($classes as $classId => $displayName) {
        // Check if this class exists in the database
        foreach ($classList as $dbClass) {
            if ($dbClass['classid'] === $classId) {
                if (!isset($availableGroups[$blockName])) {
                    $availableGroups[$blockName] = [
                        'name' => $blockName,
                        'classes' => [],
                        'total_students' => 0
                    ];
                }
                $availableGroups[$blockName]['classes'][$classId] = [
                    'display_name' => $displayName,
                    'count' => $dbClass['count']
                ];
                $availableGroups[$blockName]['total_students'] += $dbClass['count'];
                break;
            }
        }
    }
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
    $result = $stmt->get_result()->fetch_assoc();
    $avg = $result;
    $total_students = $result['total_students'] ?? 0;
    $stmt->close();
}

// -------------------- REMARKS VIEW --------------------
if (isset($_GET['subject']) && isset($_GET['classid'])) {
    $subject = $_GET['subject'];
    $classid = $_GET['classid'];
    
    if ($subject === 'other') {
        // Fetch other remarks
        $sql = "SELECT name, htno, other_remark AS remarks 
                FROM dip25_feedback 
                WHERE classid=? AND other_remark IS NOT NULL AND other_remark != ''";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $classid);
        $stmt->execute();
        $remarks = $stmt->get_result();
    } else {
        // Fetch subject-specific remarks
        $subject_col = $subject . "_remarks";
        $sql = "SELECT name, htno, $subject_col AS remarks 
                FROM dip25_feedback 
                WHERE classid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $classid);
        $stmt->execute();
        $remarks = $stmt->get_result();
    }
    
    // Get count for this class
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM dip25_feedback WHERE classid=?");
    $countStmt->bind_param("s", $classid);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $total_students = $countResult['count'] ?? 0;
    $countStmt->close();
}

// Function to get display name for a class ID
function getDisplayName($classId) {
    global $classGroups;
    foreach ($classGroups as $block => $classes) {
        if (isset($classes[$classId])) {
            return $classes[$classId];
        }
    }
    return $classId; // Fallback to original if not found
}

// Function to get block name for a class ID
function getBlockName($classId) {
    global $classGroups;
    foreach ($classGroups as $block => $classes) {
        if (isset($classes[$classId])) {
            return $block;
        }
    }
    return 'Other'; // Fallback if not found
}

// Function to get count of students with other remarks
function getOtherRemarksCount($conn, $classid) {
    $sql = "SELECT COUNT(*) as count FROM dip25_feedback 
            WHERE classid=? AND other_remark IS NOT NULL AND other_remark != ''";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $classid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'] ?? 0;
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
            --kiet-color: #10b981;
            --kiew-color: #8b5cf6;
            --other-color: #f59e0b;
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
        
        /* Block Sections */
        .block-section {
            margin-bottom: 32px;
        }
        
        .block-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid;
        }
        
        .kiet-block .block-header {
            border-color: var(--kiet-color);
        }
        
        .kiew-block .block-header {
            border-color: var(--kiew-color);
        }
        
        .block-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .kiet-block .block-title {
            color: var(--kiet-color);
        }
        
        .kiew-block .block-title {
            color: var(--kiew-color);
        }
        
        .block-badge {
            padding: 4px 12px;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        
        .kiet-block .block-badge {
            background: var(--kiet-color);
        }
        
        .kiew-block .block-badge {
            background: var(--kiew-color);
        }
        
        .block-stats {
            margin-left: auto;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .block-stats strong {
            color: var(--text-primary);
        }
        
        /* Class ID Grid with Count Badge */
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
        }
        
        @media (min-width: 768px) {
            .class-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
            }
        }
        
        .class-btn {
            display: block;
            width: 100%;
            padding: 16px 12px;
            background: var(--bg-white);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
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
        
        .kiet-block .class-btn:hover,
        .kiet-block .class-btn:focus {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--kiet-color);
            color: var(--kiet-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .kiew-block .class-btn:hover,
        .kiew-block .class-btn:focus {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--kiew-color);
            color: var(--kiew-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .class-btn.active {
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .kiet-block .class-btn.active {
            background: var(--kiet-color);
            border-color: var(--kiet-color);
        }
        
        .kiew-block .class-btn.active {
            background: var(--kiew-color);
            border-color: var(--kiew-color);
        }
        
        .class-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--text-secondary);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: var(--radius-xl);
            min-width: 24px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .kiet-block .class-btn.active .class-count-badge {
            background: white;
            color: var(--kiet-color);
        }
        
        .kiew-block .class-btn.active .class-count-badge {
            background: white;
            color: var(--kiew-color);
        }
        
        .class-display-name {
            display: block;
            font-size: 1.125rem;
            margin-bottom: 4px;
        }
        
        .class-original-id {
            display: block;
            font-size: 0.8rem;
            opacity: 0.7;
            font-weight: normal;
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
        
        .btn-other {
            background: linear-gradient(135deg, var(--other-color), #fbbf24);
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-other:hover {
            background: linear-gradient(135deg, #d97706, #f59e0b);
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
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .kiet-summary {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border: 1px solid var(--kiet-color);
        }
        
        .kiew-summary {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.05));
            border: 1px solid var(--kiew-color);
        }
        
        .class-info {
            flex: 1;
            min-width: 250px;
        }
        
        .class-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .kiet-summary .class-title {
            color: var(--kiet-color);
        }
        
        .kiew-summary .class-title {
            color: var(--kiew-color);
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
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
            
            .block-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .block-stats {
                margin-left: 0;
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4px 12px;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        /* Class ID Tag */
        .class-id-tag {
            display: inline-block;
            padding: 2px 8px;
            background: var(--bg-gray);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-family: monospace;
            margin-right: 8px;
            color: var(--text-secondary);
        }
        
        /* Other Remarks Section */
        .other-remarks-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px dashed var(--border-color);
        }
        
        .other-remarks-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 10px;
        }
        
        .other-remarks-badge {
            background: var(--other-color);
            color: white;
            padding: 4px 12px;
            border-radius: var(--radius-xl);
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .other-remarks-count {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">DIP 25</div>
            <h1>Feedback Analytics Dashboard</h1>
            <p class="subtitle">View class-wise feedback reports grouped by institution block</p>
        </div>

        <!-- Back Navigation (for remarks view) -->
        <?php if (isset($_GET['subject'])) { 
            $blockName = getBlockName($classid);
            $displayName = getDisplayName($classid);
            $subjectName = ($_GET['subject'] === 'other') ? 'Other Remarks' : ucfirst($_GET['subject']);
        ?>
        <div class="back-container">
            <a href="dip_feedback_report.php?classid=<?= $classid ?>" class="back-btn">
                ← Back to <?= htmlspecialchars($displayName) ?> Report
            </a>
            <div class="class-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_students ?></span>
                    <span class="stat-label-small">Students Submitted</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label-small">Block:</span>
                    <span class="stat-number" style="font-size: 1rem; color: <?= $blockName == 'KIET' ? 'var(--kiet-color)' : 'var(--kiew-color)' ?>;">
                        <?= $blockName ?>
                    </span>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- Class ID Selection -->
        <div class="card">
            <div class="selection-summary">
                <div class="total-classes">
                    <?php 
                    $totalClasses = 0;
                    $totalStudents = 0;
                    foreach ($availableGroups as $block) {
                        $totalClasses += count($block['classes']);
                        $totalStudents += $block['total_students'];
                    }
                    ?>
                    <?= $totalClasses ?> class<?= $totalClasses !== 1 ? 'es' : '' ?> across <?= count($availableGroups) ?> block<?= count($availableGroups) !== 1 ? 's' : '' ?>
                </div>
                <div class="total-students">
                    <?= $totalStudents ?> total submissions
                </div>
            </div>
            
            <h2>Select Class by Institution Block</h2>
            
            <?php if (empty($availableGroups)) { ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <h3>No Data Available</h3>
                    <p>No feedback has been submitted yet.</p>
                </div>
            <?php } ?>
            
            <!-- KIET Block -->
            <?php if (isset($availableGroups['KIET'])) { ?>
            <div class="block-section kiet-block">
                <div class="block-header">
                    <div class="block-title">
                        <span class="block-badge">KIET</span>
                        KIET - Kakinada Institute of Engineering & Technology
                    </div>
                    <div class="block-stats">
                        <strong><?= $availableGroups['KIET']['total_students'] ?></strong> students across 
                        <strong><?= count($availableGroups['KIET']['classes']) ?></strong> section<?= count($availableGroups['KIET']['classes']) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                
                <div class="class-grid">
                    <?php foreach ($availableGroups['KIET']['classes'] as $classId => $classInfo) { 
                        $isActive = ($classId == $classid && !isset($_GET['subject']));
                    ?>
                        <form method="post" class="class-btn-form">
                            <input type="hidden" name="classid" value="<?= htmlspecialchars($classId) ?>">
                            <button type="submit" name="get_report" class="class-btn <?= $isActive ? 'active' : '' ?>">
                                <span class="class-display-name"><?= htmlspecialchars($classInfo['display_name']) ?></span>
                                <span class="class-original-id"><?= htmlspecialchars($classId) ?></span>
                                <span class="class-count-badge"><?= $classInfo['count'] ?></span>
                            </button>
                        </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
            
            <!-- KIEW Block -->
            <?php if (isset($availableGroups['KIEW'])) { ?>
            <div class="block-section kiew-block">
                <div class="block-header">
                    <div class="block-title">
                        <span class="block-badge">KIEW</span>
                        KIEW - Kakinada Institute of Engineering & Technology for Women
                    </div>
                    <div class="block-stats">
                        <strong><?= $availableGroups['KIEW']['total_students'] ?></strong> students across 
                        <strong><?= count($availableGroups['KIEW']['classes']) ?></strong> section<?= count($availableGroups['KIEW']['classes']) !== 1 ? 's' : '' ?>
                    </div>
                </div>
                
                <div class="class-grid">
                    <?php foreach ($availableGroups['KIEW']['classes'] as $classId => $classInfo) { 
                        $isActive = ($classId == $classid && !isset($_GET['subject']));
                    ?>
                        <form method="post" class="class-btn-form">
                            <input type="hidden" name="classid" value="<?= htmlspecialchars($classId) ?>">
                            <button type="submit" name="get_report" class="class-btn <?= $isActive ? 'active' : '' ?>">
                                <span class="class-display-name"><?= htmlspecialchars($classInfo['display_name']) ?></span>
                                <span class="class-original-id"><?= htmlspecialchars($classId) ?></span>
                                <span class="class-count-badge"><?= $classInfo['count'] ?></span>
                            </button>
                        </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
            
            <!-- Show any other classes not in predefined groups -->
            <?php 
            $otherClasses = [];
            foreach ($classList as $dbClass) {
                $found = false;
                foreach ($classGroups as $classes) {
                    if (isset($classes[$dbClass['classid']])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $otherClasses[] = $dbClass;
                }
            }
            
            if (!empty($otherClasses)) { ?>
            <div class="block-section" style="margin-top: 40px; border-top: 1px dashed var(--border-color); padding-top: 30px;">
                <div class="block-header" style="border-color: var(--text-secondary);">
                    <div class="block-title" style="color: var(--text-secondary);">
                        <span class="block-badge" style="background: var(--text-secondary);">Other</span>
                        Other Classes
                    </div>
                    <div class="block-stats">
                        <strong><?= count($otherClasses) ?></strong> additional class<?= count($otherClasses) !== 1 ? 'es' : '' ?>
                    </div>
                </div>
                
                <div class="class-grid">
                    <?php foreach ($otherClasses as $class) { 
                        $isActive = ($class['classid'] == $classid && !isset($_GET['subject']));
                    ?>
                        <form method="post" class="class-btn-form">
                            <input type="hidden" name="classid" value="<?= htmlspecialchars($class['classid']) ?>">
                            <button type="submit" name="get_report" class="class-btn <?= $isActive ? 'active' : '' ?>">
                                <span class="class-display-name"><?= htmlspecialchars($class['classid']) ?></span>
                                <span class="class-count-badge"><?= $class['count'] ?></span>
                            </button>
                        </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>

        <!-- Class Summary Info (when a class is selected) -->
        <?php if (!empty($classid) && !isset($_GET['subject'])) { 
            $blockName = getBlockName($classid);
            $displayName = getDisplayName($classid);
            $summaryClass = strtolower($blockName) . '-summary';
        ?>
        <div class="class-summary <?= $summaryClass ?> fade-in">
            <div class="class-info">
                <div class="class-title">
                    <?= htmlspecialchars($displayName) ?>
                    <span style="font-size: 0.9rem; font-weight: normal; opacity: 0.7;">
                        (<?= htmlspecialchars($classid) ?>)
                    </span>
                </div>
                <p>
                    <span class="class-id-tag"><?= $blockName ?></span>
                    Viewing detailed feedback report for this class
                </p>
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
                    <span class="stat-label-small">Institution</span>
                    <span class="stat-number" style="font-size: 1rem; color: <?= $blockName == 'KIET' ? 'var(--kiet-color)' : 'var(--kiew-color)' ?>;">
                        <?= $blockName ?>
                    </span>
                </div>
                <div class="stat-item">
                    <a href="dip_feedback_report.php" class="btn btn-secondary btn-small">
                        Change Class
                    </a>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- SHOW AVERAGE RATINGS -->
        <?php if (!empty($avg) && $avg['english'] !== null && !isset($_GET['subject'])) { 
            $blockName = getBlockName($classid);
            $displayName = getDisplayName($classid);
            $otherRemarksCount = getOtherRemarksCount($conn, $classid);
        ?>

        <div class="card fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="margin-bottom: 0;">Class Performance Overview</h2>
                <span style="font-size: 0.9rem; color: <?= $blockName == 'KIET' ? 'var(--kiet-color)' : 'var(--kiew-color)' ?>; font-weight: 600;">
                    <?= $blockName ?> Block
                </span>
            </div>
            <h4><?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($classid) ?>)</h4>
            
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
                $subjectRatings = []; // Array to store only subject ratings
                
                foreach ($subjects as $key => $data) {
                    $totalAvg += $avg[$key];
                    $subjectRatings[$key] = $avg[$key]; // Store only subject ratings
                }
                $overallAvg = $totalAvg / count($subjects);
                
                // Find highest and lowest rated subjects from subjectRatings only
                $maxRating = max($subjectRatings);
                $minRating = min($subjectRatings);
                
                // Get subjects with max and min ratings
                $maxSubjects = array_keys($subjectRatings, $maxRating);
                $minSubjects = array_keys($subjectRatings, $minRating);
                
                // Handle multiple subjects with same max rating
                $maxLabels = [];
                foreach ($maxSubjects as $subjectKey) {
                    $maxLabels[] = $subjects[$subjectKey][0];
                }
                $maxLabel = implode(", ", $maxLabels);
                
                // Handle multiple subjects with same min rating
                $minLabels = [];
                foreach ($minSubjects as $subjectKey) {
                    $minLabels[] = $subjects[$subjectKey][0];
                }
                $minLabel = implode(", ", $minLabels);
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
                        $rating = number_format($avg[$key], 2);
                        $ratingClass = 'rating-' . round($avg[$key]);
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
            
            <!-- Other Remarks Section -->
            <div class="other-remarks-section">
                <div class="other-remarks-header">
                    <span class="other-remarks-badge">Other Remarks</span>
                    <h3 style="margin: 0;">General/Additional Feedback</h3>
                    <span class="other-remarks-count">
                        <?= $otherRemarksCount ?> student<?= $otherRemarksCount !== 1 ? 's' : '' ?> provided additional remarks
                    </span>
                </div>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    View general feedback, suggestions, or additional comments provided by students.
                </p>
                <div style="text-align: center;">
                    <a href="?classid=<?= $classid ?>&subject=other" class="btn-other">
                        📝 View Other Remarks (<?= $otherRemarksCount ?>)
                    </a>
                </div>
            </div>
        </div>

        <?php } elseif (!empty($classid) && $avg['english'] === null && !isset($_GET['subject'])) { 
            $displayName = getDisplayName($classid);
        ?>
        <div class="card fade-in">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3>No Data for <?= htmlspecialchars($displayName) ?></h3>
                <p>No feedback has been submitted for this class yet.</p>
            </div>
        </div>
        <?php } ?>

        <!-- SHOW REMARKS -->
        <?php if (!empty($remarks)) { 
            $blockName = getBlockName($classid);
            $displayName = getDisplayName($classid);
            $subject = $_GET['subject'] ?? '';
            $subjectName = ($subject === 'other') ? 'Other Remarks' : ucfirst($subject);
        ?>

        <div class="card fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="margin-bottom: 0;">
                    <?= $subjectName ?>
                    <?php if ($subject === 'other') { ?>
                        <span style="font-size: 0.9rem; color: var(--other-color); font-weight: 600; margin-left: 10px;">
                            General Feedback
                        </span>
                    <?php } ?>
                </h2>
                <span style="font-size: 0.9rem; color: <?= $blockName == 'KIET' ? 'var(--kiet-color)' : 'var(--kiew-color)' ?>; font-weight: 600;">
                    <?= $blockName ?> Block
                </span>
            </div>
            <h4>Class: <?= htmlspecialchars($displayName) ?> (<?= htmlspecialchars($classid) ?>)</h4>
            
            <div style="margin-bottom: 1.5rem; padding: 12px; background: <?= $subject === 'other' ? 'rgba(245, 158, 11, 0.1)' : 'var(--bg-gray)' ?>; border-radius: var(--radius); border-left: 4px solid <?= $subject === 'other' ? 'var(--other-color)' : 'var(--primary-color)' ?>;">
                <strong>
                    <?php 
                    $remarks_count = $remarks->num_rows;
                    echo $remarks_count . ' student' . ($remarks_count !== 1 ? 's' : '') . ' provided ' . ($subject === 'other' ? 'additional remarks' : 'feedback');
                    ?>
                </strong>
                <?php if ($subject !== 'other') { ?>
                    <span style="margin-left: 10px; color: var(--text-secondary);">
                        (out of <?= $total_students ?> total students)
                    </span>
                <?php } ?>
            </div>
            
            <table class="remarks-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Student</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 0;
                    while ($r = $remarks->fetch_assoc()) { 
                        $counter++;
                        $remarks_text = $r['remarks'] ?? '';
                    ?>
                        <tr>
                            <td>
                                <div class="student-info"><?= htmlspecialchars($r['name']) ?></div>
                                <div class="student-htno">HTNO: <?= htmlspecialchars($r['htno']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-light); margin-top: 4px;">
                                    Student #<?= $counter ?>
                                </div>
                            </td>
                            <td style="text-align: left;">
                                <?php if (empty(trim($remarks_text))) { ?>
                                    <span style="color: var(--text-light); font-style: italic;">No remarks provided</span>
                                <?php } else { ?>
                                    <?= nl2br(htmlspecialchars($remarks_text)) ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="dip_feedback_report.php?classid=<?= $classid ?>" class="btn btn-primary">
                    ← Back to Class Report
                </a>
                <a href="dip_feedback_report.php" class="btn btn-secondary" style="margin-left: 12px;">
                    Select Different Class
                </a>
            </div>
        </div>

        <?php } ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border-color); color: var(--text-secondary); font-size: 0.875rem;">
            <p>DIP 25 Feedback Analytics &copy; <?= date('Y') ?></p>
            <p>Total submissions: <?= $totalStudents ?> from <?= $totalClasses ?> classes across <?= count($availableGroups) ?> institution blocks</p>
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