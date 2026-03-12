<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "db.php";
date_default_timezone_set('Asia/Kolkata');   // ✅ FIXED & CORRECT LOCATION

$today = date("Y-m-d");

// ---------- CSS (Refined and Enhanced for Colorfulness) ----------
$css = '
<style>
    /* Inter Font for modern aesthetic */
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap");

    /* Global Styles & Reset */
    * {
        box-sizing: border-box;
    }
    body { 
        font-family: "Inter", sans-serif; 
        background-color: #f3f4f6; /* Very light grey/blue background */
        color: #1f2937; /* Darker text for readability */
        margin: 0; 
        padding: 30px 20px; 
        display: flex; 
        flex-direction: column;
        align-items: center; 
        min-height: 100vh;
    }
    a {
        color: #6366f1; /* Vibrant Indigo link */
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
        word-break: break-word;
    }
    a:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    /* Container - Main Card */
    .container { 
        background-color: #ffffff; 
        padding: 24px; 
        border-radius: 16px; /* Larger, smoother corners */
        width: 100%; 
        max-width: 640px; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Deeper, softer shadow */
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }

    /* Headings - Vibrant Purple Accent */
    h2, h3 { 
        color: #6366f1; /* Vibrant Indigo */
        margin-top: 0;
        margin-bottom: 20px; 
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e7ff; /* Lighter, accent divider */
        font-weight: 700;
        font-size: 1.3rem;
    }

    /* Messages/Alerts - More Colorful Feedback */
    .msg { 
        padding: 18px; 
        background-color: #dcfce7; /* Light Success Green */
        color: #15803d; /* Dark Success Green */
        border-radius: 10px; 
        border: 1px solid #a7f3d0;
        font-weight: 600;
        margin-bottom: 15px;
    }
    .error { 
        padding: 18px; 
        background-color: #fee2e2; /* Light Error Red */
        color: #b91c1c; /* Dark Error Red */
        border-radius: 10px; 
        border: 1px solid #fecaca;
        font-weight: 600;
        margin-bottom: 15px;
    }

    /* Form Elements */
    form {
        display: flex;
        flex-direction: column;
        gap: 20px; /* Increased spacing */
    }
    label {
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
        color: #374151;
        font-size: 0.95rem;
    }
    input[type="text"], textarea { 
        width: 100%; 
        padding: 12px; 
        border-radius: 10px; 
        border: 1px solid #d1d5db; 
        font-size: 15px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    input[type="text"]:focus, textarea:focus {
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2); /* Soft purple glow */
    }
    textarea {
        resize: vertical;
        min-height: 100px; /* Taller textarea */
    }
    
    /* Submit Button - Bright Teal/Cyan CTA */
    button { 
        width: 100%; 
        padding: 14px; 
        border-radius: 10px; 
        background-color: #0d9488; /* Deep Teal/Cyan */
        color: white; 
        border: none; 
        font-size: 16px; 
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.1s, box-shadow 0.1s;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3); /* Button shadow */
    }
    button:hover {
        background-color: #0f766e;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(13, 148, 136, 0.4);
    }
    
    /* Period Card - Highlighted Section */
    .period-card { 
        border: 2px solid #e0e7ff; /* Soft Blue Border */
        padding: 18px; 
        margin-bottom: 20px; 
        border-radius: 12px; 
        background-color: #f9fafb; /* Light background for contrast */
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        position: relative;
    }
    .period-card h4 {
        color: #374151;
        margin-top: 0;
        margin-bottom: 12px;
        font-size: 1.05rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
    }
    .period-card h4::before {
        content: "🕒"; /* Clock icon for period */
        margin-right: 8px;
        font-size: 1.05em;
    }
    .period-card p {
        margin: 0 0 10px 0;
        font-size: 0.95rem;
        line-height: 1.4;
    }
    .period-card b {
        font-weight: 700;
        color: #4b5563;
    }

    /* Star Rating - Warm Gold */
    .rating { 
        display: flex; 
        flex-direction: row-reverse; 
        justify-content: flex-end;
        margin-top: 5px;
        margin-bottom: 14px;
        gap: 5px;
        padding-left: 0;
    }
    .rating input { 
        display: none; 
    }
    .rating label { 
        font-size: 30px; /* Large, clickable stars */
        cursor: pointer; 
        color: #d1d5db; /* Light grey unselected */
        padding: 0 1px;
        transition: color 0.3s ease, text-shadow 0.3s ease;
        line-height: 1;
        display: inline-block;
        font-weight: normal;
        margin: 0;
    }
    .rating input:checked ~ label, 
    .rating label:hover, 
    .rating label:hover ~ label { 
        color: #f59e0b; /* Deep Gold/Amber color */
        text-shadow: 0 0 5px rgba(245, 158, 11, 0.5); /* Subtle glow effect */
    }

    /* Mobile Optimizations */
    @media (max-width: 600px) {
        body {
            padding: 16px 10px;
        }
        .container {
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }
        h2, h3 {
            font-size: 1.15rem;
        }
        .period-card {
            padding: 14px;
            margin-bottom: 16px;
        }
        .rating label {
            font-size: 26px;
        }
        button {
            font-size: 15px;
            padding: 12px;
        }
    }
</style>
';


// ---------- STEP 1: SHOW HTNO ENTRY PAGE ----------
if (!isset($_POST['action'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Feedback</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $css ?>
</head>
<body>
<div class="container">
    <h2>Enter HTNO to Start Feedback</h2>
    <form method="POST">
        <label for="htno-input">HTNO:</label>
        <input type="text" name="htno" id="htno-input" required placeholder="Enter Your HTNO">
        <input type="hidden" name="action" value="check_htno">
        <button type="submit">Start Feedback</button>
    </form>
</div>
</body>
</html>
<?php 
    exit(); 
}


// ---------- STEP 2: VERIFY HTNO & CHECK TODAY’S FEEDBACK ----------
if ($_POST['action'] == "check_htno") {

    $htno = trim($_POST['htno']);

    // validate student
    $stmt = $conn->prepare("SELECT classid FROM STUDENTS WHERE htno=? LIMIT 1");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        echo "<!DOCTYPE html><html><head><title>Student Feedback</title><meta name='viewport' content='width=device-width, initial-scale=1.0'>{$css}</head><body>";
        echo "<div class='container error'>❌ Invalid HTNO. Please check and try again.</div>";
        echo "<div class='container' style='text-align:center;'><a href=\"periodfeedback.php\">⬅ Back</a></div>";
        echo "</body></html>";
        exit();
    }

    $classid = $res->fetch_assoc()['classid'];

    // ✅ FIX FOR AMBIGUOUS COLUMN (using aliases)
    $stmt_fb = $conn->prepare("
        SELECT pf.id AS feedback_id, p.id AS period_id, p.period_no,
               p.subject_name, p.faculty_name, p.topic_covered,
               pf.rating, pf.review
        FROM period_feedback pf
        INNER JOIN periods p ON pf.period_id = p.id
        WHERE pf.htno=? AND p.date=?
        ORDER BY p.period_no ASC
    ");
    $stmt_fb->bind_param("ss", $htno, $today);
    $stmt_fb->execute();
    $submitted = $stmt_fb->get_result();

    // already submitted today → message only (no feedback shown)
    if ($submitted->num_rows > 0) {
        echo "<!DOCTYPE html><html><head><title>Student Feedback</title><meta name='viewport' content='width=device-width, initial-scale=1.0'>{$css}</head><body>";
        echo "<div class='container msg'>✅ Feedback for today has already been submitted by HTNO: " . htmlspecialchars($htno) . ".</div>";
        echo "<div class='container' style='text-align:center;'><a href='periodfeedback.php'>⬅ Start New Feedback</a></div>";
        echo "</body></html>";
        exit();
    }

    // fetch today's periods for the class
    $stmt_p = $conn->prepare("SELECT * FROM periods WHERE date=? AND classid=? ORDER BY period_no ASC");
    $stmt_p->bind_param("ss", $today, $classid);
    $stmt_p->execute();
    $periods = $stmt_p->get_result();

    if ($periods->num_rows < 4) {
        echo "<!DOCTYPE html><html><head><title>Student Feedback</title><meta name='viewport' content='width=device-width, initial-scale=1.0'>{$css}</head><body>";
        echo "<div class='container error'>⚠ Feedback is not yet open. Only {$periods->num_rows} periods updated today. Feedback requires 4 periods.</div>";
        echo "<div class='container' style='text-align:center;'><a href='periodfeedback.php'>⬅ Back</a></div>";
        echo "</body></html>";
        exit();
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Give Feedback</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $css ?>
</head>
<body>
<div class="container">
    <h3>Daily Feedback for HTNO: <?= htmlspecialchars($htno) ?></h3>

    <form method="POST">
        <input type="hidden" name="action" value="submit_feedback">
        <input type="hidden" name="htno" value="<?= htmlspecialchars($htno) ?>">

        <?php while ($p = $periods->fetch_assoc()) { 
            $period_id = htmlspecialchars($p["id"]);
        ?>
        <div class="period-card">
            <h4>Period <?= htmlspecialchars($p["period_no"]) ?></h4>
            <p>
                <b>Subject:</b> <?= htmlspecialchars($p["subject_name"]) ?><br>
                <b>Faculty:</b> <?= htmlspecialchars($p["faculty_name"]) ?><br>
                <b>Topic:</b> <?= htmlspecialchars($p["topic_covered"]) ?><br>
            </p>

            <label>How would you rate today’s class? (Required)</label>
            <div class="rating">
                <?php for ($i = 5; $i >= 1; $i--) { ?>
                    <input 
                        type="radio" 
                        id="star<?= $period_id . "-" . $i ?>" 
                        name="rating[<?= $period_id ?>]" 
                        value="<?= $i ?>" 
                        required
                    >
                    <label for="star<?= $period_id . "-" . $i ?>">★</label>
                <?php } ?>
            </div>

            <label for="review-<?= $period_id ?>">What feedback do you want to give about today’s class?</label>
            <textarea 
                name="review[<?= $period_id ?>]" 
                id="review-<?= $period_id ?>" 
                placeholder="Write your comments..."></textarea>
        </div>
        <?php } ?>

        <button type="submit">Submit All Feedback</button>
    </form>
</div>
</body>
</html>
<?php 
    exit(); 
}


// ---------- STEP 3: SAVE FEEDBACK ----------
if ($_POST['action'] == "submit_feedback") {

    $htno = $_POST['htno'];

    $stmt_ins = $conn->prepare("
        INSERT INTO period_feedback (period_id, htno, rating, review)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($_POST['rating'] as $period_id => $rating) {
        $review = $_POST['review'][$period_id] ?? "";
        
        // Ensure data is correctly typed for binding
        $safe_period_id = (int)$period_id;
        $safe_rating = (int)$rating;
        
        $stmt_ins->bind_param("isis", $safe_period_id, $htno, $safe_rating, $review);
        $stmt_ins->execute();
    }

    echo "<!DOCTYPE html><html><head><title>Student Feedback</title><meta name='viewport' content='width=device-width, initial-scale=1.0'>{$css}</head><body>";
    echo "<div class='container msg'>✅ Thank you! Your feedback has been submitted successfully.</div>";
    echo "<div class='container' style='text-align:center;'><a href='periodfeedback.php'>⬅ Give Feedback for Another Student</a></div>";
    echo "</body></html>";
}
?>
