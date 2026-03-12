<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

$htno = $_GET['htno'] ?? '';
$class_id = $_GET['class_id'] ?? '';

if (!$htno || !$class_id) {
    die("Invalid Access");
}

/* =========================================
   ✅ FETCH CLASS + EXPERT DETAILS
========================================= */
$stmt = $conn->prepare("
    SELECT C.*, 
           E.expert_name, 
           E.expert_qualification, 
           E.expert_experience, 
           E.expert_from,
           E.expert_photo
    FROM CLASS_CALENDAR C
    LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
    WHERE C.id = ?
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    die("Class Not Found");
}

/* =========================================
   ✅ BLOCK DUPLICATE FEEDBACK
========================================= */
$stmt = $conn->prepare("SELECT ID FROM CLASS_FEEDBACK WHERE HTNO=? AND CLASS_CALENDAR_ID=?");
$stmt->bind_param("si", $htno, $class_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

if ($res->num_rows > 0) {
    die("<h3 style='color:red;text-align:center;margin-top:50px;'>Feedback Already Submitted For This Class</h3>");
}

/* =========================================
   ✅ SUBMIT FEEDBACK
========================================= */
if (isset($_POST['submit_feedback'])) {
    $q1 = $_POST['q1'];
    $q2 = $_POST['q2'];
    $q3 = $_POST['q3'];
    $comments = $_POST['comments'];

    $stmt = $conn->prepare("
        INSERT INTO CLASS_FEEDBACK 
        (HTNO, CLASS_CALENDAR_ID, Q1_RATING, Q2_RATING, Q3_RATING, COMMENTS)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("siiiis", $htno, $class_id, $q1, $q2, $q3, $comments);
    $stmt->execute();
    $stmt->close();

    echo "<script>
        alert('Feedback Submitted Successfully');
        window.location = 'students.php';
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Feedback</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    padding: 20px 0;
}

.page-wrapper {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 15px;
}

.page-title {
    font-size: 2.2rem;
    font-weight: 800;
    text-align: center;
    color: white;
    margin-bottom: 10px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    position: relative;
    padding-bottom: 15px;
}

.page-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: linear-gradient(90deg, #ffd700, #ff9500);
    border-radius: 2px;
}

.page-subtitle {
    text-align: center;
    color: rgba(255,255,255,0.9);
    font-size: 1.1rem;
    margin-bottom: 30px;
    font-weight: 300;
}

/* DETAILS CARD */
.details-card {
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
    margin-bottom: 30px;
    background: white;
    transition: transform 0.3s ease;
}

.details-card:hover {
    transform: translateY(-5px);
}

.details-card .card-header {
    background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    font-weight: 600;
    font-size: 1.2rem;
    border: none;
    padding: 15px 25px;
}

/* EXPERT PHOTO */
.expert-photo-wrapper {
    position: relative;
    display: inline-block;
}

.expert-photo {
    width: 160px;
    height: 160px;
    object-fit: cover;
    border-radius: 50%;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    border: 5px solid white;
    transition: all 0.3s ease;
}

.expert-photo:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}

.no-photo {
    width: 160px;
    height: 160px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    font-weight: 600;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    border: 5px solid white;
}

.no-photo i {
    font-size: 3rem;
    opacity: 0.8;
}

/* DETAILS SECTION */
.details-content p {
    margin-bottom: 10px;
    font-size: 1rem;
    color: #333;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.details-content p:last-child {
    border-bottom: none;
}

.details-content b {
    color: #1e3c72;
    min-width: 120px;
    display: inline-block;
}

.details-content .badge {
    background: linear-gradient(90deg, #00b09b, #96c93d);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 500;
}

/* FEEDBACK FORM CARD */
.feedback-card {
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: none;
    background: white;
    overflow: hidden;
}

.feedback-card .card-header {
    background: linear-gradient(90deg, #00b09b 0%, #96c93d 100%);
    color: white;
    font-weight: 600;
    font-size: 1.3rem;
    border: none;
    padding: 18px 25px;
    position: relative;
    overflow: hidden;
}

.feedback-card .card-header:before {
    content: '⭐';
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 2rem;
    opacity: 0.3;
}

/* RATING SECTION */
.rating-section {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    margin-bottom: 20px;
    border-left: 4px solid #00b09b;
}

.rating-label {
    font-weight: 600;
    font-size: 1.1rem;
    color: #1e3c72;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-label i {
    color: #ffc107;
}

/* ========================================= */
/* FIXED STAR RATING SYSTEM */
/* ========================================= */
.star-rating {
    display: flex;
    gap: 5px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2.5rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
    text-shadow: 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
}

/* Fill stars when selected */
.star-rating:not(:hover) input:checked ~ label,
.star-rating:hover label:hover ~ label {
    color: #ddd;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label,
.star-rating:not(:hover) input:checked ~ label {
    color: #ffc107;
}

.star-rating label:hover ~ label {
    color: #ffc107;
}

/* Tooltip for star values */
.star-rating label:after {
    content: attr(data-value);
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: #1e3c72;
    color: white;
    padding: 5px 10px;
    border-radius: 10px;
    font-size: 0.9rem;
    opacity: 0;
    transition: all 0.3s;
    white-space: nowrap;
    pointer-events: none;
    z-index: 10;
}

.star-rating label:hover:after {
    opacity: 1;
    top: -40px;
}

/* Active star animation */
@keyframes starPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.star-rating input:checked + label {
    animation: starPop 0.3s ease;
}

/* EMOJI RATING */
.emoji-rating {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.emoji-option {
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 10px;
    border-radius: 15px;
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    flex: 1;
    min-width: 120px;
    max-width: 150px;
}

.emoji-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.emoji-option.selected {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(33, 150, 243, 0.3);
    border: 2px solid #2196f3;
}

.emoji-icon {
    font-size: 3rem;
    margin-bottom: 10px;
    display: block;
}

.emoji-text {
    font-size: 0.9rem;
    color: #333;
    font-weight: 500;
    margin-bottom: 5px;
}

.emoji-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e3c72;
    background: #e3f2fd;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* COMMENTS TEXTAREA */
.comments-section {
    margin: 25px 0;
}

.comments-section textarea {
    border-radius: 15px;
    border: 2px solid #e0e0e0;
    padding: 15px;
    font-size: 1rem;
    transition: all 0.3s;
    resize: vertical;
    min-height: 120px;
    background: #f8f9fa;
}

.comments-section textarea:focus {
    border-color: #00b09b;
    box-shadow: 0 0 0 0.25rem rgba(0, 176, 155, 0.25);
    background: white;
}

/* SUBMIT BUTTON */
.btn-submit {
    background: linear-gradient(90deg, #00b09b 0%, #96c93d 100%);
    border: none;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    padding: 15px 30px;
    border-radius: 15px;
    transition: all 0.3s ease;
    width: 100%;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
}

.btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 176, 155, 0.4);
    color: white;
}

.btn-submit:active {
    transform: translateY(-1px);
}

.btn-submit:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: 0.5s;
}

.btn-submit:hover:before {
    left: 100%;
}

/* RATING SCALE INFO */
.rating-scale {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 15px;
    border-radius: 15px;
    margin-bottom: 20px;
    border-left: 5px solid #ffc107;
}

.rating-scale h6 {
    color: #1e3c72;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-scale h6 i {
    color: #ffc107;
}

.rating-scale-items {
    display: flex;
    justify-content: space-between;
    text-align: center;
}

.rating-scale-item {
    flex: 1;
    padding: 5px;
}

.rating-scale-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e3c72;
    margin-bottom: 5px;
}

.rating-scale-text {
    font-size: 0.8rem;
    color: #666;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .page-title {
        font-size: 1.8rem;
    }
    
    .star-rating label {
        font-size: 2rem;
    }
    
    .emoji-option {
        min-width: 100px;
    }
    
    .details-content p {
        font-size: 0.9rem;
    }
    
    .expert-photo, .no-photo {
        width: 120px;
        height: 120px;
    }
}

/* ANIMATIONS */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.details-card, .feedback-card {
    animation: fadeIn 0.6s ease-out;
}
</style>
</head>

<body>

<div class="page-wrapper">

<h1 class="page-title">📝 Class Feedback</h1>
<p class="page-subtitle">Share your valuable feedback to help us improve</p>

<!-- ========================= -->
<!-- CLASS + EXPERT DETAILS -->
<!-- ========================= -->

<div class="card details-card mb-4">
    <div class="card-header">
        <i class="fas fa-chalkboard-teacher me-2"></i> Class & Expert Details
    </div>

    <div class="card-body">
        <div class="row g-4">
            
            <!-- EXPERT PHOTO -->
            <div class="col-md-4 text-center">
                <div class="expert-photo-wrapper">
                    <?php if ($class['expert_photo']): ?>
                        <img src="<?= $class['expert_photo'] ?>" class="expert-photo" alt="Expert Photo">
                    <?php else: ?>
                        <div class="no-photo">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <span class="badge"><i class="fas fa-star me-1"></i> Expert</span>
                </div>
            </div>

            <!-- DETAILS -->
            <div class="col-md-8 details-content">
                <div class="row">
                    <div class="col-md-6">
                        <p><b><i class="fas fa-user me-2"></i>Expert Name:</b> <?= $class['expert_name'] ?></p>
                        <p><b><i class="fas fa-graduation-cap me-2"></i>Qualification:</b> <?= $class['expert_qualification'] ?></p>
                        <p><b><i class="fas fa-briefcase me-2"></i>Experience:</b> <?= $class['expert_experience'] ?></p>
                        <p><b><i class="fas fa-map-marker-alt me-2"></i>From:</b> <?= $class['expert_from'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><b><i class="fas fa-calendar-alt me-2"></i>Date:</b> <?= $class['date'] ?></p>
                        <p><b><i class="fas fa-clock me-2"></i>Time:</b> <?= $class['start_time'] ?> - <?= $class['end_time'] ?></p>
                        <p><b><i class="fas fa-book me-2"></i>Subject:</b> <?= $class['subject'] ?></p>
                        <p><b><i class="fas fa-tag me-2"></i>Topic:</b> <?= $class['topic'] ?></p>
                        <p><b><i class="fas fa-chalkboard me-2"></i>Class Type:</b> <?= $class['classtype'] ?></p>
                        <p><b><i class="fas fa-laptop-house me-2"></i>Mode:</b> <span class="badge"><?= $class['TYPE'] ?></span></p>
                        <p><b><i class="fas fa-map-marked-alt me-2"></i>Venue:</b> <?= $class['venue'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================= -->
<!-- RATING SCALE INFO -->
<!-- ========================= -->

<div class="rating-scale">
    <h6><i class="fas fa-info-circle"></i> Rating Scale Guide</h6>
    <div class="rating-scale-items">
        <div class="rating-scale-item">
            <div class="rating-scale-value">1</div>
            <div class="rating-scale-text">Poor<br>😞</div>
        </div>
        <div class="rating-scale-item">
            <div class="rating-scale-value">2</div>
            <div class="rating-scale-text">Fair<br>😐</div>
        </div>
        <div class="rating-scale-item">
            <div class="rating-scale-value">3</div>
            <div class="rating-scale-text">Good<br>🙂</div>
        </div>
        <div class="rating-scale-item">
            <div class="rating-scale-value">4</div>
            <div class="rating-scale-text">Very Good<br>😊</div>
        </div>
        <div class="rating-scale-item">
            <div class="rating-scale-value">5</div>
            <div class="rating-scale-text">Excellent<br>🤩</div>
        </div>
    </div>
</div>

<!-- ========================= -->
<!-- FEEDBACK FORM -->
<!-- ========================= -->

<div class="card feedback-card">
    <div class="card-header">
        <i class="fas fa-comment-dots me-2"></i> Submit Your Feedback
    </div>

    <form method="post" class="card-body">

        <!-- Q1 - STAR RATING -->
        <div class="rating-section">
            <label class="rating-label">
                <i class="fas fa-chalkboard-teacher"></i> 1. Teaching Quality & Expertise
            </label>
            <div class="star-rating">
                <input type="radio" id="q1-1" name="q1" value="1" required>
                <label for="q1-1" data-value="Poor (1)">★</label>
                <input type="radio" id="q1-2" name="q1" value="2">
                <label for="q1-2" data-value="Fair (2)">★</label>
                <input type="radio" id="q1-3" name="q1" value="3">
                <label for="q1-3" data-value="Good (3)">★</label>
                <input type="radio" id="q1-4" name="q1" value="4">
                <label for="q1-4" data-value="Very Good (4)">★</label>
                <input type="radio" id="q1-5" name="q1" value="5">
                <label for="q1-5" data-value="Excellent (5)">★</label>
            </div>
        </div>

        <!-- Q2 - EMOJI RATING -->
        <div class="rating-section">
            <label class="rating-label">
                <i class="fas fa-lightbulb"></i> 2. Explanation Clarity & Communication
            </label>
            <div class="emoji-rating">
                <label class="emoji-option">
                    <input type="radio" name="q2" value="1" hidden>
                    <span class="emoji-icon">😞</span>
                    <span class="emoji-text">Confusing</span>
                    <span class="emoji-value">1</span>
                </label>
                <label class="emoji-option">
                    <input type="radio" name="q2" value="2" hidden>
                    <span class="emoji-icon">😐</span>
                    <span class="emoji-text">Average</span>
                    <span class="emoji-value">2</span>
                </label>
                <label class="emoji-option">
                    <input type="radio" name="q2" value="3" hidden>
                    <span class="emoji-icon">🙂</span>
                    <span class="emoji-text">Clear</span>
                    <span class="emoji-value">3</span>
                </label>
                <label class="emoji-option">
                    <input type="radio" name="q2" value="4" hidden>
                    <span class="emoji-icon">😊</span>
                    <span class="emoji-text">Very Clear</span>
                    <span class="emoji-value">4</span>
                </label>
                <label class="emoji-option">
                    <input type="radio" name="q2" value="5" hidden>
                    <span class="emoji-icon">🤩</span>
                    <span class="emoji-text">Excellent</span>
                    <span class="emoji-value">5</span>
                </label>
            </div>
        </div>

        <!-- Q3 - STAR RATING -->
        <div class="rating-section">
            <label class="rating-label">
                <i class="fas fa-chart-line"></i> 3. Session Usefulness & Learning
            </label>
            <div class="star-rating">
                <input type="radio" id="q3-1" name="q3" value="1" required>
                <label for="q3-1" data-value="Not Useful (1)">★</label>
                <input type="radio" id="q3-2" name="q3" value="2">
                <label for="q3-2" data-value="Somewhat Useful (2)">★</label>
                <input type="radio" id="q3-3" name="q3" value="3">
                <label for="q3-3" data-value="Useful (3)">★</label>
                <input type="radio" id="q3-4" name="q3" value="4">
                <label for="q3-4" data-value="Very Useful (4)">★</label>
                <input type="radio" id="q3-5" name="q3" value="5">
                <label for="q3-5" data-value="Extremely Useful (5)">★</label>
            </div>
        </div>

        <!-- COMMENTS -->
        <div class="comments-section">
            <label class="rating-label">
                <i class="fas fa-comment-alt"></i> Additional Comments & Suggestions
            </label>
            <textarea name="comments" class="form-control" required 
                      placeholder="Please share your detailed feedback, suggestions for improvement, or any other comments about the session..."></textarea>
            <div class="form-text mt-2">
                <i class="fas fa-info-circle text-primary me-1"></i>
                Your constructive feedback helps us improve future sessions
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button type="submit" name="submit_feedback" class="btn btn-submit">
            <i class="fas fa-paper-plane me-2"></i> Submit Feedback
        </button>

    </form>
</div>

</div>

<!-- ========================= -->
<!-- INTERACTIVE SCRIPTS -->
<!-- ========================= -->

<script>
// Emoji selection effect
document.querySelectorAll('.emoji-option').forEach(option => {
    option.addEventListener('click', function() {
        // Remove selected class from siblings
        this.parentElement.querySelectorAll('.emoji-option').forEach(el => {
            el.classList.remove('selected');
        });
        // Add selected class to clicked option
        this.classList.add('selected');
        // Check the radio input
        this.querySelector('input').checked = true;
    });
});

// Initialize star ratings
document.querySelectorAll('.star-rating').forEach(ratingContainer => {
    const inputs = ratingContainer.querySelectorAll('input[type="radio"]');
    const labels = ratingContainer.querySelectorAll('label');
    
    // Initialize based on checked input
    inputs.forEach(input => {
        if (input.checked) {
            updateStarDisplay(ratingContainer, input.value);
        }
    });
    
    // Add click event to each label
    labels.forEach(label => {
        label.addEventListener('click', function() {
            const inputId = this.getAttribute('for');
            const input = document.getElementById(inputId);
            const value = input.value;
            
            // Update all stars in this group
            updateStarDisplay(ratingContainer, value);
            
            // Add animation to clicked star
            this.style.animation = 'starPop 0.3s ease';
            setTimeout(() => {
                this.style.animation = '';
            }, 300);
        });
    });
    
    // Add hover effects
    ratingContainer.addEventListener('mouseover', function(e) {
        if (e.target.tagName === 'LABEL') {
            const label = e.target;
            const inputId = label.getAttribute('for');
            const input = document.getElementById(inputId);
            const value = input.value;
            
            // Highlight stars on hover
            labels.forEach((star, index) => {
                if (index < value) {
                    star.style.color = '#ffc107';
                }
            });
        }
    });
    
    ratingContainer.addEventListener('mouseout', function() {
        // Restore based on selected value
        const checkedInput = ratingContainer.querySelector('input:checked');
        if (checkedInput) {
            updateStarDisplay(ratingContainer, checkedInput.value);
        } else {
            labels.forEach(star => {
                star.style.color = '#ddd';
            });
        }
    });
});

function updateStarDisplay(container, value) {
    const labels = container.querySelectorAll('label');
    labels.forEach((star, index) => {
        if (index < value) {
            star.style.color = '#ffc107';
        } else {
            star.style.color = '#ddd';
        }
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const q1 = document.querySelector('input[name="q1"]:checked');
    const q2 = document.querySelector('input[name="q2"]:checked');
    const q3 = document.querySelector('input[name="q3"]:checked');
    const comments = document.querySelector('textarea[name="comments"]');
    
    if (!q1 || !q2 || !q3 || !comments.value.trim()) {
        e.preventDefault();
        alert('Please complete all ratings and provide comments before submitting.');
        
        // Highlight missing fields
        if (!q1) {
            document.querySelector('.rating-section:nth-child(1)').style.borderColor = '#dc3545';
        }
        if (!q2) {
            document.querySelector('.rating-section:nth-child(2)').style.borderColor = '#dc3545';
        }
        if (!q3) {
            document.querySelector('.rating-section:nth-child(3)').style.borderColor = '#dc3545';
        }
        if (!comments.value.trim()) {
            comments.style.borderColor = '#dc3545';
        }
        
        // Scroll to first error
        document.querySelector('.rating-section:nth-child(1)').scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
});

// Reset border colors on input
document.querySelectorAll('input[type="radio"], textarea').forEach(input => {
    input.addEventListener('change', function() {
        const section = this.closest('.rating-section');
        if (section) {
            section.style.borderColor = '#00b09b';
        }
        if (this.type === 'radio') {
            document.querySelector('textarea[name="comments"]').style.borderColor = '#e0e0e0';
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>