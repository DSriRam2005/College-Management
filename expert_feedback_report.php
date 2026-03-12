<?php
include "db.php";

$selected_date = $_POST['selected_date'] ?? date("Y-m-d");

$sql = "
SELECT 
    cc.id AS class_id,
    cc.date,
    cc.prog,
    cc.year,
    cc.classids,
    cc.TYPE,
    cc.classtype,
    cc.subject,
    cc.topic,
    cc.venue,
    cc.start_time,
    cc.end_time,
    cc.faculty_coordinator,
    ex.expert_name,

    COUNT(cf.ID) AS total_students,
    ROUND(AVG(cf.Q1_RATING), 2) AS avg_q1,
    ROUND(AVG(cf.Q2_RATING), 2) AS avg_q2,
    ROUND(AVG(cf.Q3_RATING), 2) AS avg_q3,
    ROUND((AVG(cf.Q1_RATING + cf.Q2_RATING + cf.Q3_RATING) / 3), 2) AS overall_avg_rating

FROM CLASS_CALENDAR cc
LEFT JOIN CLASS_FEEDBACK cf 
    ON cc.id = cf.CLASS_CALENDAR_ID
LEFT JOIN EXPERTS ex
    ON cc.expert_id = ex.expert_id

WHERE cc.date = '$selected_date'
GROUP BY cc.id
ORDER BY cc.start_time ASC
";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Feedback Report</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header and Form Styles */
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }

        .date-filter {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }

        .date-filter input[type="date"] {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            min-width: 200px;
            transition: all 0.3s ease;
        }

        .date-filter input[type="date"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .date-filter button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .date-filter button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .data-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .data-table td {
            padding: 15px;
            color: #444;
            font-size: 0.9rem;
            vertical-align: top;
        }

        .data-table td:last-child {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .rating-cell {
            font-weight: bold;
            color: #2c3e50;
        }

        /* Status indicators for ratings */
        .rating-high { color: #27ae60; }
        .rating-medium { color: #f39c12; }
        .rating-low { color: #e74c3c; }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.4rem;
            }
            
            .date-filter {
                flex-direction: column;
                gap: 10px;
            }
            
            .date-filter input[type="date"] {
                width: 100%;
                min-width: unset;
            }
            
            .date-filter button {
                width: 100%;
            }
            
            .table-container {
                margin: 0 -10px;
                border-radius: 0;
            }
            
            .data-table {
                font-size: 0.85rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }
            
            .data-table th {
                font-size: 0.8rem;
                padding: 12px 8px;
            }
        }

        /* Desktop-specific enhancements */
        @media (min-width: 1200px) {
            .data-table {
                min-width: 1200px;
            }
            
            .data-table th,
            .data-table td {
                padding: 20px;
                font-size: 0.95rem;
            }
            
            .data-table th {
                font-size: 1rem;
            }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1rem;
        }

        /* Summary stats */
        .summary-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            min-width: 150px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .summary-stats {
                gap: 10px;
            }
            
            .stat-card {
                flex: 1;
                min-width: 120px;
                padding: 12px 15px;
            }
            
            .stat-card .value {
                font-size: 1.5rem;
            }
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .header, .table-container {
                box-shadow: none;
            }
            
            .date-filter button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Class Feedback Report</h1>
            <form method="POST" class="date-filter">
                <input type="date" name="selected_date" value="<?= htmlspecialchars($selected_date) ?>">
                <button type="submit">View Report</button>
            </form>
        </div>

        <?php
        // Calculate summary statistics
        $total_classes = 0;
        $total_students = 0;
        $total_ratings = 0;
        $rating_sum = 0;
        
        $result = mysqli_query($conn, $sql);
        $rows = [];
        while($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
            $total_classes++;
            $total_students += $row['total_students'];
            if ($row['overall_avg_rating']) {
                $total_ratings++;
                $rating_sum += $row['overall_avg_rating'];
            }
        }
        
        $average_rating = $total_ratings > 0 ? round($rating_sum / $total_ratings, 2) : 0;
        ?>

        <?php if (!empty($rows)): ?>
        <div class="summary-stats">
            <div class="stat-card">
                <div class="value"><?= $total_classes ?></div>
                <div class="label">Total Classes</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $total_students ?></div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $average_rating ?></div>
                <div class="label">Avg Rating</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (!empty($rows)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Class IDs</th>
                        <th>Type</th>
                        <th>Class Type</th>
                        <th>Subject</th>
                        <th>Topic</th>
                        <th>Venue</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Faculty</th>
                        <th>Expert</th>
                        <th>Students</th>
                        <th>Avg Q1</th>
                        <th>Avg Q2</th>
                        <th>Avg Q3</th>
                        <th>Overall</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): 
                        // Determine rating color
                        $overall = $row['overall_avg_rating'];
                        $rating_class = '';
                        if ($overall >= 4) $rating_class = 'rating-high';
                        elseif ($overall >= 3) $rating_class = 'rating-medium';
                        else $rating_class = 'rating-low';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['prog']) ?></td>
                        <td><?= htmlspecialchars($row['year']) ?></td>
                        <td><?= htmlspecialchars($row['classids']) ?></td>
                        <td><?= htmlspecialchars($row['TYPE']) ?></td>
                        <td><?= htmlspecialchars($row['classtype']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['topic']) ?></td>
                        <td><?= htmlspecialchars($row['venue']) ?></td>
                        <td><?= htmlspecialchars($row['start_time']) ?></td>
                        <td><?= htmlspecialchars($row['end_time']) ?></td>
                        <td><?= htmlspecialchars($row['faculty_coordinator']) ?></td>
                        <td><?= htmlspecialchars($row['expert_name']) ?></td>
                        <td><?= htmlspecialchars($row['total_students']) ?></td>
                        <td class="rating-cell"><?= htmlspecialchars($row['avg_q1']) ?></td>
                        <td class="rating-cell"><?= htmlspecialchars($row['avg_q2']) ?></td>
                        <td class="rating-cell"><?= htmlspecialchars($row['avg_q3']) ?></td>
                        <td class="rating-cell <?= $rating_class ?>"><?= htmlspecialchars($row['overall_avg_rating']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                📭 No classes found for the selected date
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>