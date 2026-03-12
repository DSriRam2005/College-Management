<?php
// ENABLE ERROR DISPLAY (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "db.php"; // your DB connection

$msg = "";

// ------------------ STEP 1: WHEN HTNO IS SUBMITTED ------------------
if (isset($_POST['get_details'])) {

    $htno = trim($_POST['htno']);

    // Check duplicate
    $chk = $conn->prepare("SELECT id FROM dip25_feedback WHERE htno = ?");
    $chk->bind_param("s", $htno);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $msg = "<div class='alert alert-error' role='alert'>Feedback already submitted.</div>";
    } else {
        // Fetch student details
        $stmt = $conn->prepare("SELECT name, classid FROM STUDENTS WHERE htno=? AND prog='DIP' AND year=25");
        $stmt->bind_param("s", $htno);
        $stmt->execute();
        $stmt->bind_result($name, $classid);

        if ($stmt->fetch()) {
            $student_found = true;
        } else {
            $msg = "<div class='alert alert-error' role='alert'>Invalid HTNO / Not DIP-25.</div>";
        }
        $stmt->close();
    }
}

// ------------------ STEP 2: SAVE FEEDBACK ------------------
if (isset($_POST['submit_feedback'])) {

    $sql = "INSERT INTO dip25_feedback 
    (htno, name, classid,
    english_rating, english_remarks,
    maths_rating, maths_remarks,
    physics_rating, physics_remarks,
    chemistry_rating, chemistry_remarks,
    bce_rating, bce_remarks,
    clang_rating, clang_remarks,
    eg_rating, eg_remarks,
    other_remark)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    // Check for errors in preparation
    if (!$stmt) {
        $msg = "<div class='alert alert-error' role='alert'>Database error: " . $conn->error . "</div>";
    } else {
        // Debug: Check parameter counts
        $expected_params = 18; // 18 question marks in VALUES clause
        $actual_params = count([
            $_POST['htno'],
            $_POST['name'],
            $_POST['classid'],
            $_POST['english_rating'], $_POST['english_remarks'],
            $_POST['maths_rating'], $_POST['maths_remarks'],
            $_POST['physics_rating'], $_POST['physics_remarks'],
            $_POST['chemistry_rating'], $_POST['chemistry_remarks'],
            $_POST['bce_rating'], $_POST['bce_remarks'],
            $_POST['clang_rating'], $_POST['clang_remarks'],
            $_POST['eg_rating'], $_POST['eg_remarks'],
            $_POST['other_remark']
        ]);
        
        if ($expected_params != $actual_params) {
            $msg = "<div class='alert alert-error' role='alert'>Parameter mismatch. Expected: $expected_params, Got: $actual_params</div>";
        } else {
            // CORRECTED bind_param format string
            $stmt->bind_param("sssisisisisisisiss",
                $_POST['htno'],
                $_POST['name'],
                $_POST['classid'],
                $_POST['english_rating'], $_POST['english_remarks'],
                $_POST['maths_rating'], $_POST['maths_remarks'],
                $_POST['physics_rating'], $_POST['physics_remarks'],
                $_POST['chemistry_rating'], $_POST['chemistry_remarks'],
                $_POST['bce_rating'], $_POST['bce_remarks'],
                $_POST['clang_rating'], $_POST['clang_remarks'],
                $_POST['eg_rating'], $_POST['eg_remarks'],
                $_POST['other_remark']
            );

            if ($stmt->execute()) {
                $msg = "<div class='alert alert-success' role='alert'>Feedback submitted successfully!</div>";
                // Clear student_found to prevent resubmission
                unset($student_found);
            } else {
                $msg = "<div class='alert alert-error' role='alert'>Error: " . $stmt->error . "</div>";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIP 25 Feedback</title>
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
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --border-color: #e5e7eb;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.05);
            --radius: 8px;
            --radius-lg: 12px;
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
        h1, h2, h3 {
            font-weight: 600;
            line-height: 1.3;
            color: var(--text-primary);
        }
        
        h1 {
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }
        
        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }
        
        p {
            margin-bottom: 0.75rem;
        }
        
        /* Layout */
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .main-card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .form-hint {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background-color: var(--bg-white);
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            width: 100%;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-submit {
            margin-top: 2rem;
            font-size: 1.125rem;
            padding: 16px 24px;
        }
        
        /* Subject Cards */
        .subject-card {
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .subject-card h3 {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .subject-icon {
            margin-right: 10px;
            font-size: 1.25rem;
        }
        
        /* Other Remarks Card */
        .other-remarks-card {
            background: #e8f4f8;
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .other-remarks-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        /* Student Info */
        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            background: var(--bg-light);
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Rating Styling */
        .rating-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 1rem;
        }
        
        .rating-option {
            flex: 1;
            min-width: 60px;
        }
        
        .rating-option input[type="radio"] {
            display: none;
        }
        
        .rating-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 8px;
            background: var(--bg-white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            font-weight: 500;
        }
        
        .rating-option input[type="radio"]:checked + label {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .rating-option label:hover {
            border-color: var(--primary-color);
        }
        
        .rating-value {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .rating-label {
            font-size: 0.75rem;
        }
        
        /* Note Box */
        .note-box {
            display: flex;
            align-items: center;
            margin-top: 10px;
            padding: 12px;
            background: rgba(37, 99, 235, 0.05);
            border-radius: var(--radius);
        }
        
        .note-icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .note-text {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        /* Responsive */
        @media (min-width: 640px) {
            body {
                padding: 24px;
            }
            
            .main-card {
                padding: 32px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .btn {
                width: auto;
            }
            
            .btn-submit {
                width: 300px;
                margin-left: auto;
                margin-right: auto;
                display: block;
            }
        }
        
        @media (min-width: 768px) {
            .rating-container {
                flex-wrap: nowrap;
            }
        }
        
        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus visible for keyboard users */
        *:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Loading state */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header-logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* Character Counter */
        .char-counter {
            text-align: right;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .char-counter.warning {
            color: var(--warning-color);
        }
        
        .char-counter.error {
            color: var(--error-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">DIP 25</div>
            <h1>Student Feedback Form</h1>
        </div>
        
        <?= $msg ?>
        
        <!-- HTNO INPUT FORM -->
        <?php if (!isset($student_found)) { ?>
        <div class="main-card">
            <h2>Enter Your Details</h2>
            <p class="form-hint">Please enter your HTNO to begin the feedback process.</p>
            
            <form method="post">
                <div class="form-group">
                    <label for="htno">Hall Ticket Number (HTNO)</label>
                    <input 
                        type="text" 
                        id="htno" 
                        name="htno" 
                        required 
                        placeholder="Enter your HTNO"
                        aria-describedby="htno-help"
                    >
                    <p id="htno-help" class="form-hint">Enter the HTNO provided by your institution.</p>
                </div>
                
                <button class="btn btn-primary" name="get_details" type="submit">
                    Get Details & Continue
                </button>
            </form>
        </div>
        <?php } ?>
        
        <!-- FEEDBACK FORM -->
        <?php if (isset($student_found)) { ?>
        <div class="main-card">
            <div class="student-info">
                <div class="info-item">
                    <span class="info-label">Hall Ticket Number</span>
                    <span class="info-value"><?= htmlspecialchars($htno) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student Name</span>
                    <span class="info-value"><?= htmlspecialchars($name) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class ID</span>
                    <span class="info-value"><?= htmlspecialchars($classid) ?></span>
                </div>
            </div>
            
            <h2>Course Feedback</h2>
            <p class="form-hint">Please rate each subject on a scale of 1 to 5 and provide your remarks.</p>
            
            <form method="post">
                <input type="hidden" name="htno" value="<?= htmlspecialchars($htno) ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
                <input type="hidden" name="classid" value="<?= htmlspecialchars($classid) ?>">
                
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
                
                foreach ($subjects as $key => $data) { 
                    list($label, $icon) = $data;
                ?>
                    <div class="subject-card">
                        <h3>
                            <span class="subject-icon"><?= $icon ?></span>
                            <?= $label ?>
                        </h3>
                        
                        <div class="form-group">
                            <label>Rating</label>
                            <div class="rating-container">
                                <?php 
                                $ratings = [
                                    1 => "Poor",
                                    2 => "Fair", 
                                    3 => "Good",
                                    4 => "Very Good",
                                    5 => "Excellent"
                                ];
                                
                                foreach ($ratings as $value => $text) { 
                                ?>
                                <div class="rating-option">
                                    <input 
                                        type="radio" 
                                        id="<?= $key ?>_rating_<?= $value ?>" 
                                        name="<?= $key ?>_rating" 
                                        value="<?= $value ?>" 
                                        required
                                    >
                                    <label for="<?= $key ?>_rating_<?= $value ?>">
                                        <span class="rating-value"><?= $value ?></span>
                                        <span class="rating-label"><?= $text ?></span>
                                    </label>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="<?= $key ?>_remarks">Remarks</label>
                            <textarea 
                                id="<?= $key ?>_remarks" 
                                name="<?= $key ?>_remarks" 
                                required 
                                placeholder="Share your feedback about this subject..."
                            ></textarea>
                            <p class="form-hint">Please provide specific feedback about teaching methods, content, or suggestions.</p>
                        </div>
                    </div>
                <?php } ?>
                
                <!-- OTHER REMARKS SECTION -->
                <div class="other-remarks-card">
                    <h3>
                        <span class="subject-icon">💬</span>
                        Other Remarks or Suggestions
                    </h3>
                    
                    <div class="form-group">
                        <label for="other_remark">Additional Feedback</label>
                        <textarea 
                            id="other_remark" 
                            name="other_remark"
                            required
                            placeholder="Any other remarks, suggestions, or general feedback about the program, facilities, or overall experience..."
                            maxlength="500"
                            aria-describedby="other_remark_help"
                        ></textarea>
                        <div id="other_remark_counter" class="char-counter">0/500 characters</div>
                        <p id="other_remark_help" class="form-hint">
                            You can provide general feedback, suggestions for improvement, or any other comments (max 500 characters).
                        </p>
                    </div>
                    
                    
                
                <button class="btn btn-primary btn-submit" name="submit_feedback" type="submit">
                    Submit All Feedback
                </button>
            </form>
        </div>
        <?php } ?>
        
        <div class="footer">
            <p>DIP 25 Feedback System &copy; <?= date('Y') ?></p>
            <p>Your feedback is valuable for improving the learning experience.</p>
        </div>
    </div>
    
    <script>
        // Enhance form accessibility and mobile experience
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on first input field
            const firstInput = document.querySelector('input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Add loading state to submit button
            const submitBtn = document.querySelector('button[name="submit_feedback"]');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    this.classList.add('loading');
                    this.innerHTML = 'Submitting...';
                });
            }
            
            // Improve rating selection for touch devices
            const ratingLabels = document.querySelectorAll('.rating-option label');
            ratingLabels.forEach(label => {
                label.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                label.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
            });
            
            // Add character counter for other_remark textarea
            const otherRemarkTextarea = document.getElementById('other_remark');
            if (otherRemarkTextarea) {
                const counter = document.getElementById('other_remark_counter');
                
                // Update counter on input
                otherRemarkTextarea.addEventListener('input', function() {
                    const length = this.value.length;
                    counter.textContent = `${length}/500 characters`;
                    
                    // Update counter color based on length
                    counter.classList.remove('warning', 'error');
                    if (length > 500) {
                        counter.classList.add('error');
                    } else if (length > 400) {
                        counter.classList.add('warning');
                    }
                });
                
                // Limit input to 500 characters
                otherRemarkTextarea.addEventListener('keydown', function(e) {
                    if (this.value.length >= 500 && e.key !== 'Backspace' && e.key !== 'Delete' && 
                        !e.ctrlKey && !e.metaKey && !e.altKey) {
                        e.preventDefault();
                        // Show visual feedback
                        counter.classList.add('error');
                        setTimeout(() => counter.classList.remove('error'), 300);
                    }
                });
                
                // Handle paste event to limit pasted content
                otherRemarkTextarea.addEventListener('paste', function(e) {
                    const pastedText = e.clipboardData.getData('text');
                    if (pastedText.length + this.value.length > 500) {
                        e.preventDefault();
                        const allowedLength = 500 - this.value.length;
                        const truncatedText = pastedText.substring(0, allowedLength);
                        document.execCommand('insertText', false, truncatedText);
                    }
                });
            }
            
            // Validate all required fields before submission
            const feedbackForm = document.querySelector('form[method="post"]');
            if (feedbackForm && submitBtn) {
                feedbackForm.addEventListener('submit', function(e) {
                    const requiredTextareas = this.querySelectorAll('textarea[required]');
                    let allValid = true;
                    
                    requiredTextareas.forEach(textarea => {
                        if (!textarea.value.trim()) {
                            allValid = false;
                            textarea.style.borderColor = 'var(--error-color)';
                            textarea.focus();
                        } else {
                            textarea.style.borderColor = '';
                        }
                    });
                    
                    if (!allValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields before submitting.');
                        submitBtn.classList.remove('loading');
                        submitBtn.innerHTML = 'Submit All Feedback';
                    }
                });
            }
            
            // Prevent form resubmission on refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>