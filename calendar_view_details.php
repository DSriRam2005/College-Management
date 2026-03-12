<?php
// calendar_view_details.php
require_once "db.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

/* EVENT ID */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("<h3>Invalid Event ID</h3>");
}

/* FETCH EVENT + EXPERT */
$sql = "SELECT C.*, E.expert_name, E.expert_qualification, 
               E.expert_experience, E.expert_from, E.expert_phone, E.expert_photo
        FROM CLASS_CALENDAR C
        LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
        WHERE C.id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("<h3>Event Not Found</h3>");
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Class Details - <?= htmlspecialchars($event['subject']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body {
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    color: #333;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 10px;
}

/* BACK BUTTON */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #007bff;
    color: #fff !important;
    padding: 6px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.85rem;
    border: none;
    margin-bottom: 10px;
}

.back-btn i {
    font-size: 0.85rem;
}

/* MAIN CARD */
.main-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
}

/* HEADER */
.page-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
    margin-bottom: 12px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px;
}

.subject-block {
    min-width: 200px;
}

.subject-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.topic-subtitle {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

/* CLASS TYPE BADGE */
.status-badge {
    display: inline-block;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    border: 1px solid #ccc;
    margin-left: 4px;
}

.badge-gate {
    border-color: #3a86ff;
    color: #3a86ff;
}

.badge-campus {
    border-color: #38b000;
    color: #38b000;
}

.badge-placement {
    border-color: #ff6d00;
    color: #ff6d00;
}

/* PRINT BUTTON */
.btn-print {
    font-size: 0.8rem;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #007bff;
    background: #fff;
    color: #007bff;
}

/* SECTION TITLE */
.section-title-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 10px 0;
}

.section-title-wrap i {
    font-size: 0.9rem;
    color: #007bff;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0;
}

/* GRID */
.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}

.detail-card {
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 6px 8px;
    background: #fafafa;
    font-size: 0.85rem;
}

.detail-label {
    font-size: 0.78rem;
    color: #777;
    margin-bottom: 2px;
}

.detail-value {
    font-size: 0.85rem;
    font-weight: 500;
    margin: 0;
    word-break: break-word;
}

.empty-value {
    color: #999;
    font-style: italic;
    font-weight: 400;
}

/* TIME BADGE */
.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.8rem;
    padding: 2px 6px;
    border-radius: 4px;
    background: #e9f2ff;
    color: #004a99;
}

/* EXPERT SECTION */
.expert-section {
    border-top: 1px solid #eee;
    padding-top: 10px;
    margin-top: 5px;
    font-size: 0.85rem;
}

.expert-header {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

/* Expert photo */
.expert-photo,
.expert-photo-missing {
    width: 90px;
    height: 90px;
    border-radius: 6px;
    object-fit: cover;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
}

.expert-photo-missing i {
    font-size: 1.8rem;
    color: #777;
}

/* Expert text */
.expert-info {
    flex: 1;
    min-width: 180px;
}

.expert-name {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0 0 2px 0;
}

.expert-qualification {
    font-size: 0.82rem;
    color: #555;
    margin: 0 0 4px 0;
}

.expert-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.expert-badge {
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 0.75rem;
    background: #fafafa;
}

/* RESPONSIVE */
@media (max-width: 576px) {
    .container {
        padding: 6px;
    }

    .main-card {
        padding: 10px;
    }

    .subject-title {
        font-size: 1rem;
    }

    .topic-subtitle {
        font-size: 0.8rem;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .back-btn {
        width: 100%;
        justify-content: center;
    }
}

/* PRINT */
@media print {
    body {
        background: #fff;
    }
    .back-btn,
    .btn-print {
        display: none !important;
    }
    .main-card {
        border: none;
        padding: 0;
    }
}
</style>
</head>

<body>

<div class="container">

    <a href="calendar_monthly_view.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Calendar
    </a>

    <div class="main-card">

        <!-- HEADER -->
        <div class="page-header">
            <div class="subject-block">
                <h1 class="subject-title">
                    <?= htmlspecialchars($event['subject']) ?>
                    <?php if ($event['classtype']): 
                        $badgeClass = 'badge-' . strtolower(str_replace(' ', '-', $event['classtype']));
                    ?>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($event['classtype']) ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <h2 class="topic-subtitle"><?= htmlspecialchars($event['topic']) ?></h2>
            </div>

            <button type="button" class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>

        <!-- CLASS DETAILS SECTION -->
        <div class="details-section">
            <div class="section-title-wrap">
                <i class="fas fa-calendar-alt"></i>
                <h3 class="section-title">Class Details</h3>
            </div>

            <div class="details-grid">
                <!-- Date -->
                <div class="detail-card">
                    <div class="detail-label">Date</div>
                    <p class="detail-value"><?= htmlspecialchars($event['date']) ?></p>
                </div>

                <!-- Program & Year -->
                <div class="detail-card">
                    <div class="detail-label">Program & Year</div>
                    <p class="detail-value">
                        <?= htmlspecialchars($event['prog']) ?> - Year <?= htmlspecialchars($event['year']) ?>
                    </p>
                </div>

                <!-- Class Type -->
                <div class="detail-card">
                    <div class="detail-label">Class Type</div>
                    <p class="detail-value">
                        <?= $event['classtype'] ? htmlspecialchars($event['classtype']) : '<span class="empty-value">Not specified</span>' ?>
                    </p>
                </div>

                <!-- Venue -->
                <div class="detail-card">
                    <div class="detail-label">Venue</div>
                    <p class="detail-value">
                        <?= $event['venue'] ? htmlspecialchars($event['venue']) : '<span class="empty-value">Not specified</span>' ?>
                    </p>
                </div>

                <!-- Class IDs -->
                <div class="detail-card">
                    <div class="detail-label">Class IDs</div>
                    <p class="detail-value">
                        <?= $event['classids'] ? htmlspecialchars($event['classids']) : '<span class="empty-value">Not specified</span>' ?>
                    </p>
                </div>

                <!-- Time -->
                <div class="detail-card">
                    <div class="detail-label">Time</div>
                    <?php if ($event['start_time'] && $event['end_time']): ?>
                        <span class="time-badge">
                            <i class="fas fa-clock"></i>
                            <?= htmlspecialchars($event['start_time']) ?> - <?= htmlspecialchars($event['end_time']) ?>
                        </span>
                    <?php else: ?>
                        <p class="detail-value"><span class="empty-value">Not specified</span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- EXPERT DETAILS SECTION -->
        <div class="expert-section">
            <div class="section-title-wrap">
                <i class="fas fa-user-tie"></i>
                <h3 class="section-title">Expert Details</h3>
            </div>

            <div class="expert-header">
                <?php if ($event['expert_photo']): ?>
                    <img src="<?= htmlspecialchars($event['expert_photo']) ?>" 
                         alt="<?= htmlspecialchars($event['expert_name']) ?>" 
                         class="expert-photo">
                <?php else: ?>
                    <div class="expert-photo-missing">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>

                <div class="expert-info">
                    <p class="expert-name">
                        <?= $event['expert_name'] ? htmlspecialchars($event['expert_name']) : '<span class="empty-value">Not specified</span>' ?>
                    </p>
                    <p class="expert-qualification">
                        <?= $event['expert_qualification'] ? htmlspecialchars($event['expert_qualification']) : '<span class="empty-value">Not specified</span>' ?>
                    </p>

                    <div class="expert-badges">
                        <span class="expert-badge">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= $event['expert_from'] ? htmlspecialchars($event['expert_from']) : 'Location not specified' ?>
                        </span>

                        <?php if ($event['expert_experience']): ?>
                            <span class="expert-badge">
                                <i class="fas fa-briefcase"></i>
                                <?= htmlspecialchars($event['expert_experience']) ?> Experience
                            </span>
                        <?php endif; ?>

                        
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

</body>
</html>
