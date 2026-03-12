<?php
// ✅ Show errors (debug mode)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
include 'db.php';

// ✅ Color coding class for styling
function getRatingClass($avg) {
    if (!is_numeric($avg)) return '';
    if ($avg >= 4.5) return 'avg-rating-5';
    if ($avg >= 3.5) return 'avg-rating-4';
    if ($avg >= 2.5) return 'avg-rating-3';
    if ($avg >= 1.5) return 'avg-rating-2';
    return 'avg-rating-1';
}

// ✅ Convert numeric rating → ★★★★★ stars
function renderStars($rating) {
    if (!is_numeric($rating)) return "No FB";

    $full = floor($rating);
    $empty = 5 - $full;

    return str_repeat("★", $full) . str_repeat("✩", $empty);
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedYear = $_GET['year'] ?? "";

$yearResult = $conn->query("SELECT DISTINCT year FROM STUDENTS ORDER BY year ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback Report - PR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --bg: #0f172a;
        --card-bg: #ffffff;
        --primary: #4f46e5;
        --primary-soft: #eef2ff;
        --border: #e5e7eb;
        --text-main: #111827;
        --text-muted: #6b7280;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 16px;
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at top, #e0f2fe 0, #eef2ff 40%, #f9fafb 100%);
        color: var(--text-main);
        display: flex;
        justify-content: center;
    }

    .page-wrapper {
        width: 100%;
        max-width: 1200px;
    }

    .card {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 18px 18px 22px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.16);
        border: 1px solid rgba(148, 163, 184, 0.3);
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .page-title {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
    }

    .page-subtitle {
        font-size: 13px;
        color: var(--text-muted);
    }

    .chip-summary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ecfeff;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        color: #0f766e;
        border: 1px solid #a5f3fc;
        white-space: nowrap;
    }

    .chip-summary strong {
        background: #14b8a6;
        color: #ecfeff;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
    }

    .filter-box {
        background: #f9fafb;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 14px;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        font-size: 13px;
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .filter-box label {
        font-weight: 500;
        color: #374151;
        font-size: 13px;
    }

    .filter-box input,
    .filter-box select {
        padding: 6px 8px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 13px;
        min-width: 140px;
        outline: none;
        background: #ffffff;
    }

    .filter-box button {
        padding: 7px 14px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.4);
        white-space: nowrap;
    }

    .filter-box button:hover {
        filter: brightness(1.03);
    }

    .alert {
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 13px;
    }

    .alert-danger {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* TABLE */
    .table-wrapper {
        margin-top: 10px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border);
        background: white;
    }

    .table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        min-width: 760px;
        font-size: 13px;
    }

    thead th {
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        color: #ffffff;
        font-weight: 600;
        padding: 9px 8px;
        text-align: center;
        border-bottom: 1px solid #4c51bf;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    tbody td {
        border-top: 1px solid #e5e7eb;
        padding: 8px 8px;
        text-align: center;
        vertical-align: top;
    }

    tbody tr:nth-child(odd) {
        background: #f9fafb;
    }

    tbody tr:nth-child(even) {
        background: #ffffff;
    }

    tbody tr:hover {
        background: #eef2ff;
    }

    tbody td:first-child {
        background: #eff6ff;
        font-weight: 600;
        color: #1e40af;
        border-right: 1px solid #dbeafe;
        white-space: nowrap;
    }

    /* Rating badges */
    .rating-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 4px;
    }

    .avg-rating-5 { background: #d1fae5; color: #047857; }
    .avg-rating-4 { background: #fffbeb; color: #d97706; }
    .avg-rating-3 { background: #eff6ff; color: #2563eb; }
    .avg-rating-2 { background: #fee2e2; color: #dc2626; }
    .avg-rating-1 { background: #fef3c7; color: #78350f; }

    .fb-count {
        display: inline-block;
        margin-top: 3px;
        font-size: 11px;
        color: #6b7280;
    }

    .period-cell {
        font-size: 12px;
        line-height: 1.4;
    }

    .period-cell strong {
        font-weight: 600;
    }

    .subject-text {
        color: #4b5563;
        font-size: 11px;
    }

    .btn-comments {
        margin-top: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        border: none;
        background: #6366f1;
        color: #ffffff;
        font-size: 11px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
        white-space: nowrap;
    }

    .btn-comments:hover {
        filter: brightness(1.05);
    }

    /* Overall rating cell */
    tbody td:last-child {
        font-weight: 600;
    }

    /* Hidden feedback content holder */
    .fb-hidden {
        display: none;
    }

    /* MODAL POPUP */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 999;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-dialog {
        background: #ffffff;
        border-radius: 14px;
        max-width: 640px;
        width: 100%;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.5);
        overflow: hidden;
    }

    .modal-header {
        padding: 10px 14px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: #f9fafb;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #111827;
    }

    .modal-close {
        border: none;
        background: transparent;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        color: #6b7280;
        padding: 0 4px;
    }

    .modal-body {
        padding: 10px 14px 12px;
        overflow-y: auto;
        font-size: 13px;
        background: #ffffff;
    }

    .fb-item {
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .fb-item:last-child {
        border-bottom: none;
    }

    .fb-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 6px;
        margin-bottom: 2px;
    }

    .fb-htno {
        font-weight: 600;
        color: #111827;
    }

    .fb-rating {
        font-size: 12px;
        color: #f59e0b;
        font-weight: 600;
    }

    .fb-date {
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 3px;
    }

    .fb-review {
        font-size: 13px;
        color: #374151;
        font-style: italic;
        white-space: pre-wrap;
    }

    @media (max-width: 640px) {
        body {
            padding: 10px;
        }
        .card {
            padding: 14px 12px 18px;
            border-radius: 12px;
        }
        .page-title {
            font-size: 18px;
        }
        .filter-box {
            flex-direction: column;
            align-items: flex-start;
        }
        .filter-group {
            width: 100%;
        }
        .filter-box input,
        .filter-box select {
            width: 100%;
        }
        table {
            font-size: 12px;
        }
    }
</style>

</head>
<body>

<div class="page-wrapper">
    <div class="card">
        <div class="page-header">
            <div>
                <div class="page-title">Daily Feedback Performance Report ⭐</div>
                <div class="page-subtitle">
                    View period-wise feedback, average ratings and student comments.
                </div>
            </div>
            <div class="chip-summary">
                <strong><?= htmlspecialchars($selectedDate) ?></strong>
                Feedback Snapshot
            </div>
        </div>

        <div class="filter-box">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;width:100%;">
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?= $selectedDate ?>" required>
                </div>

                <div class="filter-group">
                    <label>Year:</label>
                    <select name="year">
                        <option value="">All</option>
                        <?php while ($yr = $yearResult->fetch_assoc()) { ?>
                            <option value="<?= $yr['year'] ?>" <?= ($selectedYear == $yr['year']) ? 'selected' : '' ?>>
                                <?= $yr['year'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit">Filter</button>
            </form>
        </div>

        <?php
        // ✅ Fetch class list based on selection
        if ($selectedYear != "") {
            $queryClass = "
                SELECT DISTINCT p.classid
                FROM periods p
                JOIN STUDENTS s ON s.classid = p.classid
                WHERE p.date = '$selectedDate' AND s.year = '$selectedYear'
                ORDER BY p.classid ASC
            ";
        } else {
            $queryClass = "
                SELECT DISTINCT classid
                FROM periods
                WHERE date = '$selectedDate'
                ORDER BY classid ASC
            ";
        }

        $classData = $conn->query($queryClass);

        if ($classData->num_rows == 0) {
            echo "<div class='alert alert-danger'>No classes found for $selectedDate.</div>";
        } else {
        ?>

        <div class="table-wrapper">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Period 1</th>
                            <th>Period 2</th>
                            <th>Period 3</th>
                            <th>Period 4</th>
                            <th>Overall Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    while ($row = $classData->fetch_assoc()) {

                        $classid = $row['classid'];

                        $periodQuery = "
                            SELECT id, period_no, faculty_name, subject_name
                            FROM periods
                            WHERE classid = '$classid'
                              AND date = '$selectedDate'
                            ORDER BY period_no ASC
                        ";

                        $periods = $conn->query($periodQuery);

                        $report = [1=>"—",2=>"—",3=>"—",4=>"—"];
                        $ratings = [];

                        while ($p = $periods->fetch_assoc()) {

                            // ✅ Average Rating
                            $avgQuery = "SELECT AVG(rating) AS avg_rating FROM period_feedback WHERE period_id = {$p['id']}";
                            $avgRes = $conn->query($avgQuery)->fetch_assoc();
                            $avg_rating = $avgRes['avg_rating'] ? round($avgRes['avg_rating'], 2) : "No FB";

                            // ✅ Unique Feedback Count
                            $fbCountQuery = "
                                SELECT COUNT(DISTINCT htno) AS fb_count
                                FROM period_feedback
                                WHERE period_id = {$p['id']}
                            ";
                            $fbCount = $conn->query($fbCountQuery)->fetch_assoc()['fb_count'] ?? 0;

                            if (is_numeric($avg_rating)) $ratings[] = $avg_rating;

                            $cls = getRatingClass($avg_rating);
                            $stars = renderStars($avg_rating);

                            // 🔥 Build cell content + hidden comments container
                            $report[$p['period_no']] = "
                                <div class='period-cell'>
                                    <strong>{$p['faculty_name']}</strong><br>
                                    <span class='subject-text'>({$p['subject_name']})</span><br>
                                    <span class='rating-badge {$cls}'>{$stars}</span><br>
                                    <span class='fb-count'>FB: {$fbCount}</span><br>
                                    <button type='button' class='btn-comments' onclick=\"openFB('fb{$p['id']}')\">
                                        View Comments
                                    </button>
                                </div>
                                <div id='fb{$p['id']}' class='fb-hidden'>
                            ";

                            // Fetch full feedback list
                            $fbListQuery = "
                                SELECT htno, rating, review, feedback_date 
                                FROM period_feedback 
                                WHERE period_id = {$p['id']}
                                ORDER BY feedback_date DESC
                            ";
                            $fbList = $conn->query($fbListQuery);

                            if ($fbList->num_rows > 0) {
                                while ($fb = $fbList->fetch_assoc()) {
                                    $htno = htmlspecialchars($fb['htno']);
                                    $fbdt = htmlspecialchars($fb['feedback_date']);
                                    $fbtext = nl2br(htmlspecialchars($fb['review']));
                                    $frating = htmlspecialchars($fb['rating']);

                                    $report[$p['period_no']] .= "
                                        <div class='fb-item'>
                                            <div class='fb-item-header'>
                                                <span class='fb-htno'>{$htno}</span>
                                                <span class='fb-rating'>Rating: {$frating}</span>
                                            </div>
                                            <div class='fb-date'>{$fbdt}</div>
                                            <div class='fb-review'>{$fbtext}</div>
                                        </div>
                                    ";
                                }
                            } else {
                                $report[$p['period_no']] .= "
                                    <div class='fb-item'>
                                        <div class='fb-review'>No feedback submitted.</div>
                                    </div>
                                ";
                            }

                            $report[$p['period_no']] .= "</div>";
                        }

                        // ✅ Overall Rating
                        $overall = count($ratings) ? round(array_sum($ratings)/count($ratings),2) : "No FB";
                        $overallClass = getRatingClass($overall);
                        $overallStars = renderStars($overall);

                        echo "
                        <tr>
                            <td><b>$classid</b></td>
                            <td>{$report[1]}</td>
                            <td>{$report[2]}</td>
                            <td>{$report[3]}</td>
                            <td>{$report[4]}</td>
                            <td><span class='rating-badge {$overallClass}'>{$overallStars}</span></td>
                        </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php } // end else classes found ?>

    </div>
</div>

<!-- 🔥 POPUP MODAL FOR COMMENTS -->
<div id="fbModal" class="modal-overlay" onclick="closeFB()">
    <div class="modal-dialog" onclick="event.stopPropagation();">
        <div class="modal-header">
            <h3>Feedback Comments</h3>
            <button type="button" class="modal-close" onclick="closeFB()">&times;</button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Comments injected here -->
        </div>
    </div>
</div>

<script>
function openFB(id) {
    var source = document.getElementById(id);
    if (!source) return;

    var modal = document.getElementById('fbModal');
    var body  = document.getElementById('modalBody');

    body.innerHTML = source.innerHTML;
    modal.classList.add('show');
}

function closeFB() {
    var modal = document.getElementById('fbModal');
    modal.classList.remove('show');
}
</script>

</body>
</html>
