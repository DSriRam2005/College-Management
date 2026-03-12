<?php
session_start();
include 'db.php';

// ✅ Restrict PR users only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'PR') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$fullname = $_SESSION['full_name'] ?? $username;

// ✅ Fetch test counts + marks entered per class/year
$query = "
    SELECT 
        ct.year, 
        ct.classid, 
        COUNT(ct.test_id) AS test_count,
        SUM(
            CASE 
                WHEN (SELECT COUNT(*) FROM class_test_marks m WHERE m.test_id = ct.test_id) > 0 
                THEN 1 ELSE 0 
            END
        ) AS marks_entered
    FROM class_test ct
    GROUP BY ct.year, ct.classid
    ORDER BY ct.year, ct.classid
";
$result = mysqli_query($conn, $query);

$data = [];
$total_tests = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['year']][] = [
        'classid' => $row['classid'],
        'count' => $row['test_count'],
        'marks_entered' => $row['marks_entered']
    ];
    $total_tests += $row['test_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Test Count - PR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; padding: 30px; font-family: "Segoe UI", sans-serif; }
        h3 { color: #0d6efd; font-weight: 600; text-align: center; margin-bottom: 30px; }

        .year-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            padding: 30px;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .year-card:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0,0,0,0.25);
        }

        .hidden { display: none; }
        .year-block {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            padding: 20px;
            margin-top: 20px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; text-align: center; padding: 8px; }
        th { background-color: #0d6efd; color: white; }
        .btn-back {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            margin-bottom: 10px;
        }
        .btn-back:hover { background: #b02a37; }
    </style>
</head>
<body>

<div class="container">
    <h3>📘 Class Test Summary (PR View)</h3>

    <!-- ✅ Year Cards -->
    <div class="row g-4" id="yearCards">
        <?php foreach ($data as $year => $classes) { ?>
            <div class="col-md-4">
                <div class="year-card" onclick="showYear('<?php echo $year; ?>')">
                    <h4>Year: <?php echo $year; ?></h4>
                    <p class="text-success">Total Tests: <strong><?php echo array_sum(array_column($classes, 'count')); ?></strong></p>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- ✅ Year Tables -->
    <?php foreach ($data as $year => $classes) { ?>
        <div id="table-<?php echo $year; ?>" class="year-block hidden">
            <button class="btn-back" onclick="backToCards()">← Back</button>
            <h5>Year: <?php echo htmlspecialchars($year); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Class ID</th>
                            <th>Total Tests</th>
                            <th>Marks Entered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $year_total = 0; $marks_done = 0;
                        foreach ($classes as $class) { 
                            echo "<tr>
                                    <td>{$class['classid']}</td>
                                    <td>{$class['count']}</td>
                                    <td>{$class['marks_entered']}</td>
                                  </tr>";
                            $year_total += $class['count'];
                            $marks_done += $class['marks_entered'];
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-end text-success fw-bold">Total Tests: <?php echo $year_total; ?> | Marks Entered: <?php echo $marks_done; ?></p>
        </div>
    <?php } ?>

</div>

<script>
function showYear(year) {
    document.getElementById('yearCards').classList.add('hidden');
    document.querySelectorAll('[id^="table-"]').forEach(el => el.classList.add('hidden'));
    document.getElementById('table-' + year).classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function backToCards() {
    document.getElementById('yearCards').classList.remove('hidden');
    document.querySelectorAll('[id^="table-"]').forEach(el => el.classList.add('hidden'));
}
</script>

</body>
</html>
