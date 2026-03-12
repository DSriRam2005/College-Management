<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Default test ID (you can make it dynamic)
$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 4;
// ✅ Fetch Test Name
$test_name = '';
$test_query = $conn->query("SELECT test_name FROM tests WHERE test_id = '$test_id' LIMIT 1");
if ($test_query && $test_query->num_rows > 0) {
    $test_name = $test_query->fetch_assoc()['test_name'];
}

$htno = '';
$student_name = '';
$result = null;
$total_obtained = 0;
$total_possible = 0;

// On search
if (isset($_POST['search'])) {
    $htno = trim($_POST['htno']);
    
    if (!empty($htno)) {
        // ✅ Fetch student name
        $stu = $conn->query("SELECT name FROM STUDENTS WHERE htno='$htno' LIMIT 1");
        if ($stu && $stu->num_rows > 0) {
            $student_name = $stu->fetch_assoc()['name'];
        }

        // ✅ Fetch test key
        $sql = "
            SELECT 
                sa.question_no,
                sa.selected_option,
                ca.correct_option,
                CASE 
                    WHEN sa.selected_option = ca.correct_option THEN ca.marks 
                    ELSE 0 
                END AS obtained_marks,
                ca.marks AS question_marks
            FROM student_answers sa
            INNER JOIN correct_answers ca 
                ON sa.test_id = ca.test_id 
                AND sa.section_id = ca.section_id 
                AND sa.question_no = ca.question_no
            WHERE sa.htno = '$htno' AND sa.test_id = '$test_id'
            ORDER BY sa.section_id, sa.question_no
        ";
        $result = $conn->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Test Key</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: linear-gradient(120deg, #e0f7fa, #e1bee7);
        font-family: "Poppins", sans-serif;
    }
    .container {
        margin-top: 50px;
        max-width: 900px;
    }
    .card {
        border-radius: 15px;
        box-shadow: 0px 4px 15px rgba(0,0,0,0.1);
    }
    .table thead th {
        background-color: #512da8 !important;
        color: white;
        text-align: center;
    }
    .correct {
        background-color: #c8e6c9;
        color: #2e7d32;
        font-weight: 600;
        border-radius: 8px;
        padding: 4px 8px;
        display: inline-block;
    }
    .wrong {
        background-color: #ffcdd2;
        color: #c62828;
        font-weight: 600;
        border-radius: 8px;
        padding: 4px 8px;
        display: inline-block;
    }
    .badge-score {
        font-size: 1.1rem;
        padding: 8px 12px;
        border-radius: 10px;
        font-weight: bold;
    }
    .badge-green { background-color: #4caf50; color: white; }
    .badge-orange { background-color: #ff9800; color: white; }
    .badge-red { background-color: #f44336; color: white; }
</style>
</head>

<body>
<div class="container">
    <div class="card p-4">
        <h2 class="text-center mb-4 text-primary fw-bold">📘 Student Test Key</h2>

        <!-- Search Form -->
        <form method="post" class="text-center mb-4">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <input type="text" name="htno" class="form-control" placeholder="Enter Hall Ticket No." value="<?php echo htmlspecialchars($htno); ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" class="btn btn-success w-100">View Key</button>
                </div>
            </div>
        </form>

        <?php if (isset($_POST['search'])): ?>
            <div class="border-top pt-3">
                <?php if (!empty($student_name)) { ?>
                    <h5><strong>Name:</strong> <?php echo htmlspecialchars($student_name); ?></h5>
                <?php } ?>
                <h6><strong>Hall Ticket:</strong> <?php echo htmlspecialchars($htno); ?></h6>
                <h6><strong>Test Name:</strong> <?php echo htmlspecialchars($test_name ?: "Unknown Test"); ?></h6>

                <hr>

                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Q.No</th>
                            <th>Selected</th>
                            <th>Correct</th>
                            <th>Marks</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $is_correct = ($row['selected_option'] == $row['correct_option']);
                            $total_obtained += $row['obtained_marks'];
                            $total_possible += $row['question_marks'];
                            echo "<tr>";
                            echo "<td>{$row['question_no']}</td>";
                            echo "<td><span class='".($is_correct ? "correct" : "wrong")."'>{$row['selected_option']}</span></td>";
                            echo "<td>{$row['correct_option']}</td>";
                            echo "<td>{$row['obtained_marks']}</td>";
                            echo "<td>{$row['question_marks']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-danger'>❌ No answers found for this student.</td></tr>";
                    }
                    ?>
                    </tbody>

                    <?php if ($total_possible > 0) { 
                        $percent = ($total_obtained / $total_possible) * 100;
                        if ($percent >= 74.6) $badge = "badge-green";
                        elseif ($percent >= 64.6) $badge = "badge-orange";
                        else $badge = "badge-red";
                    ?>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">Total Marks:</th>
                            <th><?php echo $total_obtained; ?></th>
                            <th><?php echo $total_possible; ?></th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Percentage:</th>
                            <th colspan="2"><span class="badge-score <?php echo $badge; ?>">
                                <?php echo number_format($percent, 2); ?>%
                            </span></th>
                        </tr>
                    </tfoot>
                    <?php } ?>
                </table>

                <?php if ($total_possible > 0): ?>
                    <div class="text-center mt-3">
                        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Result</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
