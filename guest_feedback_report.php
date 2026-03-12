<?php
session_start();
include "db.php";

$sql = "
    SELECT 
        session_name,
        ROUND(AVG(rating),2) AS avg_rating,
        COUNT(DISTINCT htno) AS student_count
    FROM guest_feedback
    GROUP BY session_name
    ORDER BY session_name ASC
";

$result = $conn->query($sql);

function stars($n) {
    $n = floatval($n);
    $full = floor($n);
    $half = ($n - $full) >= 0.5;
    $s = "";
    for ($i=1; $i<=5; $i++) {
        if ($i <= $full) {
            $s .= "★";
        } elseif ($half && $i == $full + 1) {
            $s .= "⯨";
        } else {
            $s .= "☆";
        }
    }
    return $s;
}

// Get overall statistics
$stats_sql = "
    SELECT 
        COUNT(DISTINCT htno) as total_students,
        COUNT(*) as total_feedbacks,
        ROUND(AVG(rating),2) as overall_rating
    FROM guest_feedback
";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Summary Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #34495e;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--dark);
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th {
            background: var(--primary);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .session-link {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .session-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .stars {
            color: gold;
            font-size: 1.4rem;
            letter-spacing: 2px;
        }
        
        .rating-value {
            display: inline-block;
            background: var(--info);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 10px;
            font-weight: 600;
        }
        
        .student-count {
            display: inline-block;
            background: var(--light);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .export-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-btn:hover {
            background: #219653;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Guest Faculty Feedback Summary</h1>
            <p>Comprehensive overview of all guest sessions and student feedback</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--secondary);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-value"><?= $result->num_rows ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= $stats['total_students'] ?></div>
                <div class="stat-label">Unique Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning);">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-value"><?= $stats['total_feedbacks'] ?></div>
                <div class="stat-label">Total Feedbacks</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: gold;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value"><?= $stats['overall_rating'] ?>/5</div>
                <div class="stat-label">Overall Rating</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-table"></i> Session Performance Details</h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Session & Faculty</th>
                        <th>Average Rating</th>
                        <th>Students Attended</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result->data_seek(0); // Reset pointer
                    while ($row = $result->fetch_assoc()): 
                        $rating = floatval($row['avg_rating']);
                        $performance = "";
                        $badge_class = "";
                        
                        if ($rating >= 4.0) {
                            $performance = "Excellent";
                            $badge_class = "badge-success";
                        } elseif ($rating >= 3.0) {
                            $performance = "Good";
                            $badge_class = "badge-info";
                        } else {
                            $performance = "Needs Improvement";
                            $badge_class = "badge-warning";
                        }
                    ?>
                    <tr>
                        <td>
                            <a href="session_class_report.php?session=<?= urlencode($row['session_name']) ?>" class="session-link">
                                <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($row['session_name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="stars"><?= stars($row['avg_rating']) ?></span>
                            <span class="rating-value"><?= $row['avg_rating'] ?>/5</span>
                        </td>
                        <td>
                            <span class="student-count">
                                <i class="fas fa-user-graduate"></i> <?= $row['student_count'] ?> students
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $badge_class ?>">
                                <i class="fas fa-chart-line"></i> <?= $performance ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Guest Faculty Feedback System &copy; 2025 | Generated on <?= date('F j, Y, g:i A') ?></p>
        </div>
    </div>

    <script>
        function exportTable() {
            // Simple table export simulation
            alert('Export functionality would be implemented here. This could export to PDF, Excel, or CSV.');
            // In a real implementation, you would redirect to an export script or use JavaScript libraries
        }
        
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                // Add staggered animation
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>