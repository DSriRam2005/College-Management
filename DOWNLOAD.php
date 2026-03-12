<?php
include 'db.php';

if(isset($_POST['download_csv'])) {
    $test_id = $_POST['test_id'];
    $section_id = $_POST['section_id'];

    // Fetch test & section names (optional for filename)
    $testName = $conn->query("SELECT test_name FROM tests WHERE test_id='$test_id'")->fetch_assoc()['test_name'];
    $sectionName = $conn->query("SELECT section_name FROM test_sections WHERE section_id='$section_id'")->fetch_assoc()['section_name'];

    $filename = "correct_answers_{$testName}_{$sectionName}.csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename='.$filename);

    $output = fopen('php://output', 'w');
    // CSV header
    fputcsv($output, ['test_id','section_id','question_no','correct_option','marks']);

    // Fetch existing correct answers for this test & section
    $res = $conn->query("SELECT question_no, correct_option, marks FROM correct_answers WHERE test_id='$test_id' AND section_id='$section_id' ORDER BY question_no ASC");

    if($res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            fputcsv($output, [$test_id, $section_id, $row['question_no'], $row['correct_option'], $row['marks']]);
        }
    } else {
        // If no answers exist yet, create a sample 10-question CSV
        for($i=1;$i<=10;$i++){
            fputcsv($output, [$test_id,$section_id,$i,'','']);
        }
    }

    fclose($output);
    exit();
}

// Fetch tests and sections for form
$tests = $conn->query("SELECT test_id, test_name FROM tests");
$sections = [];
if(isset($_POST['test_id'])) {
    $tid = $_POST['test_id'];
    $sections = $conn->query("SELECT section_id, section_name FROM test_sections WHERE test_id='$tid'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download Sample CSV - Correct Answers</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3>📄 Download Sample Correct Answers CSV</h3>
    <form method="post">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Select Test</label>
                <select name="test_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Test --</option>
                    <?php while($row = $tests->fetch_assoc()){ ?>
                        <option value="<?= $row['test_id']?>" <?= (isset($_POST['test_id']) && $_POST['test_id']==$row['test_id'])?'selected':'';?>>
                            <?= htmlspecialchars($row['test_name']);?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Select Section</label>
                <select name="section_id" class="form-select" required>
                    <option value="">-- Select Section --</option>
                    <?php if(!empty($sections)) { while($sec=$sections->fetch_assoc()){ ?>
                        <option value="<?= $sec['section_id'];?>" <?= (isset($_POST['section_id']) && $_POST['section_id']==$sec['section_id'])?'selected':'';?>>
                            <?= htmlspecialchars($sec['section_name']);?>
                        </option>
                    <?php }} ?>
                </select>
            </div>
        </div>
        <button type="submit" name="download_csv" class="btn btn-success">📥 Download CSV</button>
    </form>
</div>
</body>
</html>
