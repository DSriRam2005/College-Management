<?php
require_once "db.php";

/* Show errors for debugging */
ini_set('display_errors',1);
error_reporting(E_ALL);

/* Validate ID */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("<h3 style='color:red;text-align:center;margin-top:30px;'>Invalid Event ID</h3>");
}

/* Fetch event */
$sql = "
SELECT c.*, u.username AS creator 
FROM CLASS_CALENDAR c
LEFT JOIN USERS u ON u.id = c.created_by
WHERE c.id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    die("<h3 style='color:red;text-align:center;margin-top:30px;'>Event not found</h3>");
}

$ev = $res->fetch_assoc();

/* Class type colors */
$colors = [
    'GATE EXPERT'      => '#007bff',
    'CAMPUS EXPERT'    => '#28a745',
    'PLACEMENT EXPERT' => '#ff8800'
];

$color = $colors[$ev['classtype']] ?? "#2980b9";
?>
<!DOCTYPE html>
<html>
<head>
<title>Event Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

* { font-family: 'Poppins', sans-serif; }

body {
    background: linear-gradient(145deg, #edf0f5 0%, #d6dde8 100%);
    min-height: 100vh;
    padding-bottom: 40px;
}

/* HEADER BOX */
.header-box {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-left: 6px solid <?= $color ?>;
    animation: fadeIn 0.4s ease;
}

.header-box h2 {
    font-weight: 700;
    color: #2c3e50;
}

.header-box h5 {
    font-weight: 400;
    color: #7a8896;
}

/* DETAILS CARD */
.details-card {
    border-radius: 16px;
    background: white;
    padding: 30px;
    box-shadow: 0 10px 28px rgba(0,0,0,0.10);
    animation: fadeIn 0.5s ease;
    border: none;
}

/* SUBTITLE LINE */
.section-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 12px;
    border-bottom: 2px solid #e1e5eb;
    padding-bottom: 5px;
}

/* CLASS ID BADGES */
.badge-classid {
    background: #e9eef5;
    color: #2c3e50;
    padding: 6px 12px;
    margin: 4px;
    border-radius: 10px;
    font-weight: 500;
    display: inline-block;
}

/* BACK BUTTON */
.btn-back {
    background: linear-gradient(90deg, #6c757d, #495057);
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 500;
    color: white;
    transition: 0.3s;
}
.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(50,50,50,0.3);
}

/* ANIMATION */
@keyframes fadeIn {
    from { opacity:0; transform: translateY(10px); }
    to   { opacity:1; transform:none; }
}

</style>
</head>

<body>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="header-box mb-4">
        <h2><i class="fas fa-book me-2"></i><?= htmlspecialchars($ev['subject']) ?></h2>
        <h5><?= htmlspecialchars($ev['topic']) ?></h5>

        <span class="badge px-3 py-2 mt-2" style="background: <?= $color ?>; font-size:14px;">
            <?= htmlspecialchars($ev['classtype']) ?>
        </span>
    </div>

    <!-- DETAILS CARD -->
    <div class="details-card">

        <!-- MAIN INFO -->
        <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Event Information</h5>

        <p><strong>Date:</strong> <?= date("d M Y", strtotime($ev['date'])) ?></p>
        <p><strong>Program:</strong> <?= htmlspecialchars($ev['prog']) ?></p>
        <p><strong>Year:</strong> <?= intval($ev['year']) ?></p>
        <p><strong>Expert Name:</strong> <?= htmlspecialchars($ev['expert_name']) ?></p>
        <p><strong>Venue:</strong> <?= htmlspecialchars($ev['venue']) ?></p>

        <hr>

        <!-- CLASS IDS -->
        <h5 class="section-title"><i class="fas fa-users me-2"></i>Class IDs</h5>

        <?php 
        $classList = array_filter(array_map("trim", explode(",", $ev['classids'])));

        if (count($classList) == 0) {
            echo "<p class='text-muted'>No class IDs assigned.</p>";
        } else {
            foreach ($classList as $cid) {
                echo "<span class='badge-classid'>".htmlspecialchars($cid)."</span>";
            }
        }
        ?>

        <hr>

        <!-- CREATION INFO -->
        <h5 class="section-title"><i class="fas fa-user-clock me-2"></i>Created Information</h5>

        <p><strong>Created By:</strong> <?= htmlspecialchars($ev['creator'] ?? "Unknown") ?></p>
        <p><strong>Created At:</strong> <?= date("d M Y H:i", strtotime($ev['created_at'])) ?></p>

        <!-- BACK BUTTON -->
        <a href="calendar_monthly_view.php" class="btn btn-back mt-3">
            ← Back to Calendar
        </a>

    </div>
</div>

</body>
</html>
