<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Kolkata');
include 'db.php';

// Show DB errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

// classid check
if (!isset($_SESSION['classid']) || empty($_SESSION['classid'])) {
    die("<b>ERROR:</b> classid not found in session.");
}

$classid = $_SESSION['classid'];
$today   = date('Y-m-d');

// ------------------------------
// COUNT PERIODS SUBMITTED TODAY
// ------------------------------
$stmt_cnt = $conn->prepare("SELECT COUNT(*) AS cnt FROM periods WHERE date=? AND classid=?");
$stmt_cnt->bind_param("ss", $today, $classid);
$stmt_cnt->execute();
$countRes = $stmt_cnt->get_result()->fetch_assoc();
$submittedPeriods = intval($countRes['cnt']);
$stmt_cnt->close();

$feedbackMessage = "";

// ------------------------------
// INSERT PERIOD
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $period_no     = $_POST['period_no'];
    $class         = $_POST['class'];
    $classtype     = $_POST['classtype'];
    $faculty_name  = $_POST['faculty_name'];
    $subject_name  = $_POST['subject_name'];
    $topic_covered = $_POST['topic_covered'];

    // ------------------------------
    // CLASS PIC UPLOAD
    // ------------------------------
    $classpic = "";

// ---- MANDATORY CLASS PIC UPLOAD ----
if (empty($_FILES['classpic']['name'])) {
    echo "<script>alert('❌ Class Picture is mandatory! Please upload a photo.');</script>";
    exit();
}

$uploadDir = "uploads/classpic/";
if (!is_dir($uploadDir)) { 
    mkdir($uploadDir, 0777, true); 
}

$fileTmp  = $_FILES['classpic']['tmp_name'];
$origName = basename($_FILES['classpic']['name']);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

$allowed = ['jpg','jpeg','png','webp','gif'];

if (!in_array($ext, $allowed)) {
    echo "<script>alert('❌ Invalid image. Only JPG / JPEG / PNG / WEBP / GIF allowed.');</script>";
    exit();
}

$classpic = time() . "_" . rand(1000,9999) . "." . $ext;
move_uploaded_file($fileTmp, $uploadDir . $classpic);


    // ------------------------------
    // CHECK DUPLICATE ENTRY
    // ------------------------------
    $stmt_check = $conn->prepare(
        "SELECT id FROM periods WHERE date=? AND classid=? AND period_no=?"
    );
    $stmt_check->bind_param("ssi", $today, $classid, $period_no);
    $stmt_check->execute();
    $dup = $stmt_check->get_result();

    if ($dup->num_rows > 0) {

        echo "<script>alert('❌ DUPLICATE ENTRY: This period is already updated for today.');</script>";

    } else {

        try {
            $stmt_insert = $conn->prepare("
                INSERT INTO periods 
                (date, classid, period_no, class, classtype, faculty_name, subject_name, topic_covered, classpic)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt_insert->bind_param(
                "ssissssss",
                $today, $classid, $period_no, $class, $classtype,
                $faculty_name, $subject_name, $topic_covered, $classpic
            );

            $stmt_insert->execute();

            echo "<script>alert('✅ Period saved successfully');</script>";

            $submittedPeriods++;

            if ($submittedPeriods >= 4) {
                $feedbackMessage = "
                <div class='feedback-box'>
                    ✅ <b>All 4 periods submitted for today.</b><br><br>
                    Give feedback of today's class on this link:<br>
                    <a href='https://kiet.ct.ws/periodfeedback.php' target='_blank'>
                        https://kiet.ct.ws/periodfeedback.php
                    </a>
                </div>";
            }

        } catch (mysqli_sql_exception $e) {
            echo "<script>alert('DB Error: {$e->getMessage()}');</script>";
        }
    }
}

// ------------------------------
// FETCH SUBJECTS OF THAT CLASS
// ------------------------------
$stmt_sub = $conn->prepare("
    SELECT faculty_name, subject_name
    FROM subjects
    WHERE classid=?
    ORDER BY subject_name ASC
");
$stmt_sub->bind_param("s", $classid);
$stmt_sub->execute();
$subjects = $stmt_sub->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>SPOC - Update Period</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #e0f2fe, #f1f5f9);
        margin: 0;
        padding: 16px;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .wrapper {
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
    }

    .main-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 20px 24px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    }

    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .title {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
    }

    .subtitle {
        font-size: 13px;
        color: #64748b;
    }

    .chip {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .chip span.count {
        background: #1d4ed8;
        color: #ffffff;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
    }

    .grid {
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 20px;
        margin-top: 12px;
    }

    @media (max-width: 900px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }

    form {
        margin-top: 8px;
    }

    label {
        font-weight: 500;
        margin-bottom: 4px;
        display: block;
        font-size: 13px;
        color: #0f172a;
    }

    .field {
        margin-bottom: 12px;
    }

    input, select, textarea {
        width: 100%;
        padding: 9px 10px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        background: #f8fafc;
    }

    input:focus, select:focus, textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.25);
        background: #ffffff;
    }

    textarea {
        min-height: 80px;
        resize: vertical;
    }

    button[type="submit"] {
        margin-top: 8px;
        padding: 11px;
        width: 100%;
        border-radius: 999px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        letter-spacing: 0.02em;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.45);
        transition: transform 0.1s ease, box-shadow 0.1s ease, filter 0.1s ease;
    }

    button[type="submit"]:hover {
        filter: brightness(1.03);
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.55);
        transform: translateY(-1px);
    }

    button[type="submit"]:active {
        transform: translateY(0);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }

    .feedback-box {
        padding: 12px 14px;
        background: #ecfdf5;
        border-radius: 10px;
        border-left: 5px solid #16a34a;
        font-size: 13px;
        color: #166534;
        margin-bottom: 10px;
    }

    .feedback-box a {
        color: #15803d;
        word-break: break-all;
        text-decoration: none;
        font-weight: 500;
    }

    .feedback-box a:hover {
        text-decoration: underline;
    }

    .periods-panel {
        background: #f8fafc;
        border-radius: 12px;
        padding: 12px 12px 10px;
        border: 1px solid #e2e8f0;
        max-height: 520px;
        overflow-y: auto;
    }

    .periods-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 8px;
    }

    .periods-title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
    }

    .periods-empty {
        padding: 9px;
        border-radius: 8px;
        background: #fef2f2;
        border-left: 4px solid #dc2626;
        font-size: 13px;
        color: #b91c1c;
    }

    .period-card {
        padding: 10px 9px 9px;
        margin-bottom: 10px;
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        font-size: 12px;
        color: #0f172a;
    }

    .period-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .period-badge {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 500;
    }

    .period-tag {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        background: #ede9fe;
        color: #5b21b6;
        font-weight: 500;
    }

    .period-body-row {
        margin-bottom: 5px;
        line-height: 1.35;
    }

    .period-label {
        font-weight: 600;
    }

    .period-topic {
        margin-top: 4px;
    }

    .period-photo {
        margin-top: 6px;
    }

    .period-photo img {
        width: 100%;
        max-width: 260px;
        border-radius: 8px;
        display: block;
    }

    .small-meta {
        font-size: 11px;
        color: #64748b;
    }

    @media (max-width: 600px) {
        .main-card {
            padding: 16px 14px 18px;
            border-radius: 14px;
        }
        .header-row {
            align-items: flex-start;
        }
    }
</style>
</head>

<body>
<div class="wrapper">
    <div class="main-card">
        <div class="header-row">
            <div>
                <div class="title">Daily Period Update</div>
                <div class="subtitle">Update today’s periods and keep track of what was covered.</div>
            </div>
            <div class="chip">
                <span class="count"><?php echo $submittedPeriods; ?>/4</span>
                Today’s periods
            </div>
        </div>

        <?php echo $feedbackMessage; ?>

        <div class="grid">
            <!-- LEFT: FORM -->
            <div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="field">
                        <label>Date</label>
                        <input type="date" value="<?php echo $today; ?>" readonly>
                    </div>

                    <div class="field">
                        <label>Period Number</label>
                        <select name="period_no" required>
                            <option value="">Select Period</option>
                            <option value="1">Period 1</option>
                            <option value="2">Period 2</option>
                            <option value="3">Period 3</option>
                            <option value="4">Period 4</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Class Category</label>
                        <select name="class" id="class" required>
                            <option value="regularclass">Regular Class</option>
                            <option value="expertclass">Expert Class</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Type of Class</label>
                        <select name="classtype" required>
                            <option value="regularclass">Lecture</option>
                            <option value="test">Test</option>
                            <option value="revision">Revision</option>
                            <option value="others">Others</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Subject</label>
                        <select name="subject_name" id="subject_name" required>
                            <option value="">Select Subject</option>
                            <?php while ($row = $subjects->fetch_assoc()) { ?>
                                <option value="<?php echo htmlspecialchars($row['subject_name']); ?>"
                                        data-faculty="<?php echo htmlspecialchars($row['faculty_name']); ?>">
                                    <?php echo htmlspecialchars($row['subject_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Faculty Name</label>
                        <input type="text" name="faculty_name" id="faculty_name" readonly placeholder="Auto-filled">
                    </div>

                    <div class="field">
                        <label>Topic Covered</label>
                        <textarea name="topic_covered" required></textarea>
                    </div>

                 <div class="field">
    <label>Class Picture <span style="color:red;">*</span></label>
    <input type="file" name="classpic" accept="image/*" required>
    <div class="small-meta" style="color:#dc2626; font-weight:500;">
        Photo is mandatory (upload a clear classroom picture).
    </div>
</div>


                    <button type="submit">Submit Period</button>
                </form>
            </div>

            <!-- RIGHT: TODAY'S PERIODS -->
            <div>
                <div class="periods-panel">
                    <div class="periods-header">
                        <div class="periods-title">Today's Periods (<?php echo $today; ?>)</div>
                    </div>

                    <?php
                    $stmt_list = $conn->prepare("
                        SELECT period_no, class, classtype, faculty_name, subject_name, topic_covered, classpic
                        FROM periods
                        WHERE date=? AND classid=?
                        ORDER BY period_no ASC
                    ");
                    $stmt_list->bind_param("ss", $today, $classid);
                    $stmt_list->execute();
                    $periods_today = $stmt_list->get_result();

                    if ($periods_today->num_rows === 0) {
                        echo "<div class='periods-empty'>
                                No periods submitted today.
                              </div>";
                    } else {
                        while ($p = $periods_today->fetch_assoc()) {
                            echo "<div class='period-card'>";

                            echo "<div class='period-top-row'>
                                    <div class='period-badge'>Period {$p['period_no']}</div>
                                    <div class='period-tag'>".htmlspecialchars($p['classtype'])."</div>
                                  </div>";

                            echo "<div class='period-body-row'>
                                    <div><span class='period-label'>Subject:</span> ".htmlspecialchars($p['subject_name'])."</div>
                                    <div><span class='period-label'>Faculty:</span> ".htmlspecialchars($p['faculty_name'])."</div>
                                  </div>";

                            echo "<div class='period-topic'>
                                    <span class='period-label'>Topic:</span> ".nl2br(htmlspecialchars($p['topic_covered']))."
                                  </div>";

                            if (!empty($p['classpic'])) {
                                echo "<div class='period-photo'>
                                        <span class='period-label'>Photo:</span><br>
                                        <img src='uploads/classpic/".htmlspecialchars($p['classpic'])."'>
                                      </div>";
                            }

                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const subjectSelect = document.getElementById("subject_name");
const facultyInput  = document.getElementById("faculty_name");
const classSelect   = document.getElementById("class");

// Auto-fill faculty name
subjectSelect.addEventListener("change", () => {
    const opt = subjectSelect.selectedOptions[0];
    facultyInput.value = opt ? (opt.dataset.faculty || "") : "";
});

// Expert class → enable manual entry
classSelect.addEventListener("change", () => {
    if (classSelect.value === "expertclass") {
        facultyInput.readOnly = false;
        facultyInput.value = "";
        facultyInput.placeholder = "Enter Expert Faculty Name";
    } else {
        facultyInput.readOnly = true;
        facultyInput.placeholder = "Auto-filled";
        const opt = subjectSelect.selectedOptions[0];
        facultyInput.value = opt ? (opt.dataset.faculty || "") : "";
    }
});
</script>

</body>
</html>
