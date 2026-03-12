<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';

// ==============================
// Handle Test Creation
// ==============================
if (isset($_POST['create_test'])) {
    $test_name = $_POST['test_name'];
    $total_marks = $_POST['total_marks'];
    $sections = $_POST['sections'];
    $marks = $_POST['marks'];

    // Handle file upload (PDF)
    $pdf_path = null;
    if (isset($_FILES['question_paper']) && $_FILES['question_paper']['error'] == 0) {
        $upload_dir = "uploads/question_papers/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = pathinfo($_FILES['question_paper']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) !== 'pdf') {
            $message = "❌ Only PDF files are allowed.";
        } else {
            $file_name = "Test_" . time() . "_" . basename($_FILES['question_paper']['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['question_paper']['tmp_name'], $file_path)) {
                $pdf_path = $file_path;
            } else {
                $message = "❌ Failed to upload the question paper.";
            }
        }
    }

    if (!$message) {
        // Insert test into DB
        $stmt = $conn->prepare("INSERT INTO tests (test_name, total_marks, question_paper_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $test_name, $total_marks, $pdf_path);
        $stmt->execute();
        $test_id = $stmt->insert_id;

        // Insert sections
        for ($i = 0; $i < count($sections); $i++) {
            if (trim($sections[$i]) != '') {
                $stmt2 = $conn->prepare("INSERT INTO test_sections (test_id, section_name, section_marks) VALUES (?, ?, ?)");
                $stmt2->bind_param("isi", $test_id, $sections[$i], $marks[$i]);
                $stmt2->execute();
            }
        }

        $message = "✅ Test created successfully! Test ID: $test_id<br>
                    🔗 <a href='attend_test.php?test_id=$test_id' target='_blank'>Click here to open test link</a>";
    }
}

// ==============================
// Fetch All Tests
// ==============================
$tests = [];
$result = $conn->query("SELECT * FROM tests ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $test_id = $row['test_id'];
    $sections_result = $conn->query("SELECT * FROM test_sections WHERE test_id = $test_id");
    $sections_list = [];
    while ($sec = $sections_result->fetch_assoc()) {
        $sections_list[] = $sec;
    }
    $row['sections'] = $sections_list;
    $tests[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Test - TPO Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
/* ===== Modern TPO Panel CSS ===== */
:root{
  --bg: #f4f7fb;
  --card: #ffffff;
  --muted: #6b7280;
  --accent: #004aad;
  --accent-2: #0066ff;
  --success: #16a34a;
  --danger: #ef4444;
  --radius: 10px;
  --shadow: 0 6px 18px rgba(20,30,60,0.08);
}

body {
  font-family: Inter, Arial, sans-serif;
  background: var(--bg);
  margin: 0;
  padding: 30px;
  color: #0f172a;
}

/* Headings */
h2 { color: var(--accent); margin-bottom: 10px; }
h3 { color: #07204a; }

/* Form card */
form, .link-box {
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 18px;
  margin-bottom: 20px;
}

/* Inputs */
input[type=text], input[type=number], input[type=file] {
  width: 100%;
  padding: 8px 10px;
  margin-top: 5px;
  border: 1px solid #d2d6de;
  border-radius: 6px;
  font-size: 15px;
}

input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 5px rgba(0,74,173,0.2);
}

/* Table */
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #e3e7ef; padding: 8px; text-align: center; }
th { background: #eef3ff; color: #0b2b6b; }

/* Buttons */
button, input[type=submit] {
  background: var(--accent);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-weight: 600;
  cursor: pointer;
}
button:hover, input[type=submit]:hover { background: var(--accent-2); }

.btn-ghost {
  background: #f4f6ff;
  color: var(--accent);
  border: 1px solid #cdd4f0;
}

.message, .error {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 18px;
}
.message { background: #ecfdf5; border-left: 6px solid var(--success); }
.error { background: #fef2f2; border-left: 6px solid var(--danger); }

.copy-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:6px; }
.copy-row .link-text { font-family: monospace; word-break: break-all; }
#copyStatus { position:absolute; left:-9999px; }
    </style>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function () {
            const link = btn.getAttribute('data-link');
            if (!link) return alert("No link to copy");

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link)
                    .then(() => showCopied(btn))
                    .catch(() => fallbackCopy(link, btn));
            } else fallbackCopy(link, btn);
        });
    });
});

function fallbackCopy(link, btn) {
    const temp = document.createElement('textarea');
    temp.value = link;
    temp.style.position = 'fixed';
    temp.style.left = '-9999px';
    document.body.appendChild(temp);
    temp.select();
    try {
        document.execCommand('copy');
        showCopied(btn);
    } catch (err) {
        window.prompt('Copy the link below:', link);
    }
    document.body.removeChild(temp);
}

function showCopied(btn) {
    const original = btn.innerHTML;
    btn.innerHTML = 'Copied!';
    btn.disabled = true;
    const live = document.getElementById('copyStatus');
    if (live) live.textContent = 'Link copied';
    setTimeout(() => {
        btn.innerHTML = original;
        btn.disabled = false;
        if (live) live.textContent = '';
    }, 1500);
}
    </script>
</head>
<body>

<h2>📘 Create New Test</h2>
<?php if($message != ''): ?>
<div class="<?php echo (strpos($message,'❌')!==false)?'error':'message'; ?>">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Test Name:</label>
    <input type="text" name="test_name" required>

    <label>Total Marks:</label>
    <input type="number" name="total_marks" required>

    <label>Upload Question Paper (PDF):</label>
    <input type="file" name="question_paper" accept="application/pdf" required>

    <h3>📑 Sections</h3>
    <table id="sections_table">
        <tr><th>Section Name</th><th>Marks</th><th>Action</th></tr>
        <tr>
            <td><input type="text" name="sections[]" required></td>
            <td><input type="number" name="marks[]" required></td>
            <td><button type="button" onclick="this.closest('tr').remove()">Remove</button></td>
        </tr>
    </table>
    <button type="button" onclick="addSectionRow()">Add Section</button><br><br>
    <input type="submit" name="create_test" value="Create Test">
</form>

<hr>

<h2>🧾 Existing Tests</h2>
<?php if(count($tests) > 0): ?>
    <?php foreach($tests as $test): ?>
        <div class="link-box">
            <h3><?php echo htmlspecialchars($test['test_name']); ?> 
            (ID: <?php echo $test['test_id']; ?>, Marks: <?php echo $test['total_marks']; ?>)</h3>

            <?php if(!empty($test['question_paper_path'])): ?>
                📄 <a href="<?php echo $test['question_paper_path']; ?>" target="_blank">View Question Paper</a><br>
            <?php endif; ?>

            <?php
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $link = $scheme . '://' . $host . '/attend_test.php?test_id=' . $test['test_id'];
            ?>
            <div class="copy-row">
                <div class="link-text">🔗 <a href="<?php echo $link; ?>" target="_blank"><?php echo $link; ?></a></div>
                <button type="button" class="btn-ghost copy-btn" data-link="<?php echo $link; ?>">Copy Link</button>
                <a href="<?php echo $link; ?>" target="_blank" class="small">Open</a>
            </div>

            <?php if(count($test['sections']) > 0): ?>
                <table>
                    <tr><th>Section ID</th><th>Name</th><th>Marks</th></tr>
                    <?php foreach($test['sections'] as $section): ?>
                        <tr>
                            <td><?php echo $section['section_id']; ?></td>
                            <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                            <td><?php echo $section['section_marks']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No sections added.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No tests created yet.</p>
<?php endif; ?>

<div id="copyStatus" aria-live="polite" aria-atomic="true"></div>

<script>
function addSectionRow() {
    var table = document.getElementById('sections_table');
    var row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" name="sections[]" required></td>
        <td><input type="number" name="marks[]" required></td>
        <td><button type="button" onclick="this.closest('tr').remove()">Remove</button></td>
    `;
}
</script>

</body>
</html>
