<?php
session_start();
include 'db.php';

/* ---------------------------------------------
   SUBJECTS + GUEST FACULTY (EDIT HERE)
----------------------------------------------*/
$guest_sessions = [
    "BEE- Mr. Praveen sir",
    "BEE - Mr. Kamalakar Sir",
    "Physics - Mr. Tejeswi Gupta Sir",
    "EG - Mr. Viond Sir",
    "IP - Mr. Bala Krishna Sir"
];

/* ---------------------------------------------
   CREATE TABLE IF NOT EXISTS
----------------------------------------------*/
$conn->query("
    CREATE TABLE IF NOT EXISTS guest_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        htno VARCHAR(50),
        name VARCHAR(100),
        classid VARCHAR(50),
        session_name VARCHAR(200),
        topic VARCHAR(300),
        rating INT,
        remark VARCHAR(500),
        feedback_date DATETIME
    )
");

/* ---------------------------------------------
   INITIALIZE MESSAGES
----------------------------------------------*/
$success_msg = "";
$error_msg = "";

/* ---------------------------------------------
   STEP 3 — HANDLE SUBMISSION WITH STRICT VALIDATION
----------------------------------------------*/
if (isset($_POST['submit_feedback'])) {

    $htno = $_POST['htno'];

    // Block resubmission (server-side)
    $chk = $conn->prepare("SELECT id FROM guest_feedback WHERE htno=? LIMIT 1");
    $chk->bind_param("s", $htno);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $error_msg = "You have already submitted your feedback.";
    } else {

        if (!isset($_POST['session_name']) || count($_POST['session_name']) == 0) {
            $error_msg = "Select at least one session.";
        } else {

            $name    = $_POST['name'];
            $classid = $_POST['classid'];

            $sessions = $_POST['session_name'];
            $topics   = $_POST['topic'];
            $ratings  = $_POST['rating'];
            $remarks  = $_POST['remark'];

            // Validation: All fields required
            for ($i = 0; $i < count($sessions); $i++) {
                if (trim($topics[$i]) == "" || trim($remarks[$i]) == "" || trim($ratings[$i]) == "") {
                    $error_msg = "Please fill ALL fields for ALL sessions.";
                    break;
                }
            }

            if ($error_msg == "") {
                $stmt = $conn->prepare("
                    INSERT INTO guest_feedback 
                    (htno, name, classid, session_name, topic, rating, remark, feedback_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                for ($i = 0; $i < count($sessions); $i++) {
                    $stmt->bind_param(
                        "sssssis",
                        $htno,
                        $name,
                        $classid,
                        $sessions[$i],
                        $topics[$i],
                        $ratings[$i],
                        $remarks[$i]
                    );
                    $stmt->execute();
                }

                $success_msg = "Feedback submitted successfully.";
            }
        }
    }
}

/* ---------------------------------------------
   STEP 1 — FETCH STUDENT DETAILS
----------------------------------------------*/
$student = null;
$submitted = false;

if (isset($_POST['check_htno'])) {

    $htno = trim($_POST['htno']);

    // First check if HTNO exists at all
$stmt = $conn->prepare("SELECT htno, name, classid, year FROM STUDENTS WHERE htno=? LIMIT 1");
$stmt->bind_param("s", $htno);
$stmt->execute();
$stu_data = $stmt->get_result()->fetch_assoc();

if ($stu_data) {

    if ($stu_data['year'] != 25) {
        // Student exists but wrong year
        $error_msg = "Enter a valid Roll no";
    } else {
        // Valid student from year 25
        $student = $stu_data;

        // Check previously submitted
        $chk = $conn->prepare("SELECT id FROM guest_feedback WHERE htno=? LIMIT 1");
        $chk->bind_param("s", $student['htno']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $submitted = true;
        }
    }

} else {
    // Invalid HTNO
    $error_msg = "Invalid Roll Number. Please enter a correct HTNO.";
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Faculty Feedback System</title>
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--dark);
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            display: inline-block;
            background: var(--secondary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #219653;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .student-info {
            background: var(--light);
            border-left: 4px solid var(--secondary);
        }
        
        .student-info h3 {
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .session-block {
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            background: #fafbfc;
            transition: all 0.3s;
        }
        
        .session-block:hover {
            border-color: var(--secondary);
            background: #fff;
        }
        
        .session-title {
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .star {
            font-size: 28px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star:hover, .star.selected {
            color: gold;
        }
        
        .rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        select[multiple] {
            height: 150px;
        }
        
        .instructions {
            background: #e8f4fc;
            border-left: 4px solid var(--secondary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 6px 6px 0;
        }
        
        .instructions h3 {
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .instructions ul {
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card {
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function showSessions() {
            let container = document.getElementById("session_container");
            container.innerHTML = "";

            let selected = Array.from(document.getElementById("sessions").selectedOptions).map(o => o.value);

            if (selected.length === 0) {
                container.innerHTML = '<div class="alert alert-error">Please select at least one session</div>';
                return;
            }

            selected.forEach((session, index) => {
                let block = `
                    <div class='session-block'>
                        <h3 class='session-title'><i class='fas fa-chalkboard-teacher'></i> ${session}</h3>

                        <input type='hidden' name='session_name[]' value='${session}'>

                        <div class="form-group">
                            <label for="topic_${index}">Topic Covered *</label>
                            <input type='text' id="topic_${index}" name='topic[]' class="form-control" placeholder="Enter the topic covered in this session" required>
                        </div>

                        <div class="form-group">
                            <label>Rating *</label>
                            <input type='hidden' id='rating_${index}' name='rating[]' required>
                            
                            <div class="star-rating" id="stars_${index}">
                                <span class="star" onclick="setRating(${index},1)">★</span>
                                <span class="star" onclick="setRating(${index},2)">★</span>
                                <span class="star" onclick="setRating(${index},3)">★</span>
                                <span class="star" onclick="setRating(${index},4)">★</span>
                                <span class="star" onclick="setRating(${index},5)">★</span>
                            </div>
                            
                            <div class="rating-labels">
                                <span>Poor</span>
                                <span>Excellent</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remark_${index}">Remarks *</label>
                            <textarea id="remark_${index}" name='remark[]' class="form-control" required rows='3' placeholder="Share your feedback about this session"></textarea>
                        </div>
                    </div>
                `;
                container.innerHTML += block;
            });
        }

        function setRating(index, value) {
            document.getElementById("rating_" + index).value = value;

            let stars = document.querySelectorAll("#stars_" + index + " .star");
            stars.forEach((star, i) => {
                if (i < value) {
                    star.classList.add("selected");
                } else {
                    star.classList.remove("selected");
                }
            });
        }

        function validateForm() {
            let ratings = document.querySelectorAll("input[name='rating[]']");
            for (let r of ratings) {
                if (r.value.trim() === "") {
                    alert("Please select star rating for every session.");
                    return false;
                }
            }
            
            // Additional validation
            let sessions = document.getElementById("sessions");
            if (sessions.selectedOptions.length === 0) {
                alert("Please select at least one session.");
                return false;
            }
            
            return true;
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comment-dots"></i> Guest Faculty Feedback</h1>
            <p>November 14–15, 2025</p>
        </div>

        <?php if ($success_msg): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success_msg ?>
        </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> <?= $error_msg ?>
        </div>
        <?php endif; ?>

        <?php if (!$student && !$submitted): ?>
<div class="card">
    <h2><i class="fas fa-id-card"></i> Student Verification</h2>
    <p>Please enter your roll number to begin the feedback process.</p>
    
    <form method="post">
        <div class="form-group">
            <label for="htno">Roll Number (HTNO) *</label>
            <input type="text" id="htno" name="htno" class="form-control" placeholder="Enter your roll number" required>
        </div>
        <button type="submit" name="check_htno" class="btn btn-block">
            <i class="fas fa-arrow-right"></i> Proceed to Feedback
        </button>
    </form>
</div>
<?php endif; ?>


        <?php if ($student && !$submitted): ?>
        <div class="card student-info">
            <h3><i class="fas fa-user-graduate"></i> Student Details</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Roll Number</span>
                    <span class="info-value"><?= $student['htno'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?= $student['name'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class</span>
                    <span class="info-value"><?= $student['classid'] ?></span>
                </div>
            </div>
        </div>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <ul>
                <li>Select all guest sessions you attended using Ctrl+Click (or Cmd+Click on Mac)</li>
                <li>Fill out the feedback form for each selected session</li>
                <li>Provide a rating from 1 (Poor) to 5 (Excellent) for each session</li>
                <li>All fields are required</li>
                <li>You can only submit your feedback once</li>
            </ul>
        </div>

        <div class="card">
            <form method="post" onsubmit="return validateForm();">
                <input type="hidden" name="htno" value="<?= $student['htno'] ?>">
                <input type="hidden" name="name" value="<?= $student['name'] ?>">
                <input type="hidden" name="classid" value="<?= $student['classid'] ?>">

                <div class="form-group">
                    <label for="sessions">Select Attended Guest Sessions *</label>
                    <select name="sessions[]" id="sessions" class="form-control" multiple required onchange="showSessions()">
                        <?php foreach ($guest_sessions as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold Ctrl (or Cmd on Mac) to select multiple sessions</small>
                </div>

                <div id="session_container"></div>

                <button type="submit" name="submit_feedback" class="btn btn-success btn-block">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($submitted): ?>
        <div class="card">
            <div class="alert alert-error">
                <h3><i class="fas fa-exclamation-circle"></i> Feedback Already Submitted</h3>
                <p>You have already submitted your feedback for the guest faculty sessions. Resubmission is not allowed.</p>
                <p>If you believe this is an error, please contact the administration.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Guest Faculty Feedback System &copy; 2025</p>
        </div>
    </div>
</body>
</html>