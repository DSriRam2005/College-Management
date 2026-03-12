<?php
include 'db.php';
$message = "";

// ✅ Handle CSV Upload
if (isset($_POST['upload_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($filename, "r");
        $row = 0;
        $inserted = 0;
        $updated = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row == 1) continue; // skip header

            $test_id = $data[0];
            $section_id = $data[1];
            $question_no = $data[2];
            $correct_option = strtoupper(trim($data[3]));
            $marks = $data[4];

            if (in_array($correct_option, ['A','B','C','D'])) {
                $sql = "INSERT INTO correct_answers (test_id, section_id, question_no, correct_option, marks)
                        VALUES ('$test_id','$section_id','$question_no','$correct_option','$marks')
                        ON DUPLICATE KEY UPDATE correct_option='$correct_option', marks='$marks'";
                if ($conn->query($sql)) $inserted++;
            }
        }
        fclose($handle);
        $message = "<div class='alert alert-success'>✅ Uploaded successfully! Processed $inserted rows.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⚠️ Please select a valid CSV file.</div>";
    }
}

// ✅ Handle Manual Entry (same as before)
if (isset($_POST['save_answers'])) {
    $test_id = $_POST['test_id'];
    $section_id = $_POST['section_id'];
    $question_count = $_POST['question_count'];

    for ($i = 1; $i <= $question_count; $i++) {
        $option = $_POST['option'][$i] ?? '';
        $marks = $_POST['marks'][$i] ?? 0;

        if (!empty($option)) {
            $sql = "INSERT INTO correct_answers (test_id, section_id, question_no, correct_option, marks)
                    VALUES ('$test_id', '$section_id', '$i', '$option', '$marks')
                    ON DUPLICATE KEY UPDATE correct_option='$option', marks='$marks'";
            $conn->query($sql);
        }
    }
    $message = "<div class='alert alert-success'>✅ Correct answers and marks saved successfully!</div>";
}

// ✅ Fetch tests & sections
$tests = $conn->query("SELECT test_id, test_name FROM tests");
$sections = [];
if (isset($_POST['test_id'])) {
    $tid = $_POST['test_id'];
    $sections = $conn->query("SELECT section_id, section_name FROM test_sections WHERE test_id='$tid'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enter or Upload Correct Answers</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
body { background: #f7f7f7; }
.container { max-width: 950px; margin-top: 40px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.toggle-btn { margin: 10px; }
.hidden { display: none; }
.table input, .table select { width: 100%; }
</style>
<script>
function toggleSection(section) {
    document.getElementById('manual').classList.add('hidden');
    document.getElementById('bulk').classList.add('hidden');
    document.getElementById(section).classList.remove('hidden');
}
</script>
</head>
<body>
<div class="container">
    <h3>📝 Enter or Upload Correct Answers</h3>
    <?= $message; ?>

    <div class="text-center mb-3">
        <button type="button" class="btn btn-primary toggle-btn" onclick="toggleSection('manual')">✏️ Manual Entry</button>
        <button type="button" class="btn btn-secondary toggle-btn" onclick="toggleSection('bulk')">📤 Bulk CSV Upload</button>
    </div>

    <!-- ✅ Manual Entry Section -->
    <div id="manual">
        <form method="post" class="mb-3">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Select Test</label>
                    <select name="test_id" class="form-select" required onchange="this.form.submit()">
                        <option value="">-- Select Test --</option>
                        <?php while($row = $tests->fetch_assoc()) { ?>
                            <option value="<?= $row['test_id'] ?>" <?= (isset($_POST['test_id']) && $_POST['test_id']==$row['test_id'])?'selected':''; ?>>
                                <?= htmlspecialchars($row['test_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Select Section</label>
                    <select name="section_id" class="form-select" required>
                        <option value="">-- Select Section --</option>
                        <?php if(!empty($sections)) { while($sec = $sections->fetch_assoc()) { ?>
                            <option value="<?= $sec['section_id']; ?>" <?= (isset($_POST['section_id']) && $_POST['section_id']==$sec['section_id'])?'selected':''; ?>>
                                <?= htmlspecialchars($sec['section_name']); ?>
                            </option>
                        <?php } } ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Number of Questions</label>
                <input type="number" name="question_count" class="form-control" min="1" max="200" value="<?= $_POST['question_count'] ?? 10; ?>" required>
            </div>

            <button type="submit" name="load_questions" class="btn btn-primary">Load Questions</button>
        </form>

        <?php if(isset($_POST['load_questions']) && !empty($_POST['section_id'])): ?>
            <form method="post">
                <input type="hidden" name="test_id" value="<?= $_POST['test_id']; ?>">
                <input type="hidden" name="section_id" value="<?= $_POST['section_id']; ?>">
                <input type="hidden" name="question_count" value="<?= $_POST['question_count']; ?>">

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Question No</th>
                            <th>Correct Option</th>
                            <th>Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $test_id = $_POST['test_id'];
                        $section_id = $_POST['section_id'];
                        $res = $conn->query("SELECT question_no, correct_option, marks FROM correct_answers WHERE test_id='$test_id' AND section_id='$section_id'");
                        $existing = [];
                        while($row = $res->fetch_assoc()) {
                            $existing[$row['question_no']] = ['option'=>$row['correct_option'], 'marks'=>$row['marks']];
                        }

                        for ($i = 1; $i <= $_POST['question_count']; $i++) {
                            $selected = $existing[$i]['option'] ?? '';
                            $marks = $existing[$i]['marks'] ?? '';
                            echo "<tr>
                                    <td>Q{$i}</td>
                                    <td>
                                        <select name='option[$i]' class='form-select'>
                                            <option value=''>-- Select --</option>
                                            <option value='A' ".($selected=='A'?'selected':'').">A</option>
                                            <option value='B' ".($selected=='B'?'selected':'').">B</option>
                                            <option value='C' ".($selected=='C'?'selected':'').">C</option>
                                            <option value='D' ".($selected=='D'?'selected':'').">D</option>
                                        </select>
                                    </td>
                                    <td><input type='number' step='0.5' min='0' name='marks[$i]' class='form-control' value='$marks'></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <button type="submit" name="save_answers" class="btn btn-success">💾 Save Answers & Marks</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- ✅ Bulk Upload Section -->
    <div id="bulk" class="hidden">
        <form method="post" enctype="multipart/form-data" class="mt-3">
            <label class="form-label">Upload CSV File</label>
            <input type="file" name="csv_file" accept=".csv" class="form-control mb-3" required>
            <button type="submit" name="upload_csv" class="btn btn-primary">📤 Upload CSV</button>
            <a href="correct_answers_sample.csv" class="btn btn-outline-secondary ms-2">📄 Download Sample CSV</a>
        </form>
    </div>
</div>
</body>
</html>
