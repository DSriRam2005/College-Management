<?php
require_once "db.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

/* Selected month/year */
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date("m"));
$year_full = isset($_GET['year']) ? intval($_GET['year']) : intval(date("Y"));
$filter = $_GET['group'] ?? "ALL";

/* Month info */
$first_day_timestamp = strtotime("$year_full-$month-01");
$days_in_month = date("t", $first_day_timestamp);
$start_weekday = date("w", $first_day_timestamp);

/* Year groups */
$year_columns = [25,24,23,22];

/* Colors */
$year_colors = [
    25 => "#a3c9ff",
    24 => "#b7f5c8",
    23 => "#ffe8a3",
    22 => "#ffc6c6",
    "DIP" => "#d6c6ff",
    "PG"  => "#e5d1ff"
];

/* PROGRAM NORMALIZATION */
function normalize_prog($p) {
    $p = strtoupper(trim($p));
    if ($p === "B.TECH") return "B.TECH";
    if ($p === "DIP") return "DIP";
    if (in_array($p, ["M.TECH","MBA","MCA"])) return "PG";
    return $p;
}

/* Fetch events WITH expert details */
$start = date("Y-m-01", $first_day_timestamp);
$end   = date("Y-m-t", $first_day_timestamp);

$sql = "SELECT C.*, 
               E.expert_name,
               E.expert_qualification,
               E.expert_experience,
               E.expert_from,
               E.expert_phone,
               E.expert_photo
        FROM CLASS_CALENDAR C
        LEFT JOIN EXPERTS E ON C.expert_id = E.expert_id
        WHERE C.date BETWEEN ? AND ?
        ORDER BY C.date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();

$events = [];

while ($row = $res->fetch_assoc()) {

    $d    = intval(date("j", strtotime($row['date'])));
    $prog = normalize_prog($row['prog']);
    $yr   = intval($row['year']);

    /* Filtering */
    if ($filter !== "ALL") {
        if ($filter == "PG"  && $prog !== "PG") continue;
        if ($filter == "DIP" && $prog !== "DIP") continue;
        if (in_array($filter, ["22","23","24","25"])) {
            if ($yr != intval($filter)) continue;
        }
    }

    /* Store events */
    if ($prog === "DIP") {
        $events[$d]["DIP"][] = $row;
    }
    elseif ($prog === "PG") {
        $events[$d]["PG"][] = $row;
    }
    elseif (in_array($yr, $year_columns)) {
        $events[$d][$yr][] = $row;
    }
}

$month_name = date("F", $first_day_timestamp);

/* Today highlight */
$current_day   = intval(date("j"));
$current_month = intval(date("n"));
$current_year  = intval(date("Y"));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= "$month_name $year_full - Academic Calendar" ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* YOUR ORIGINAL CSS — UNTOUCHED */
:root{
    --day-height-mobile: 120px;
    --day-height-md: 140px;
    --day-height-lg: 160px;
}
*{
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}
body{
    background:#f5f7fb;
    margin:0;
    padding-bottom:40px;
    color:#0f172a;
}
.container{
    max-width:1200px;
}
.calendar-header{
    font-size:26px;
    font-weight:700;
    text-align:center;
    margin-bottom:18px;
    color:#111827;
}
form.row.g-2.mb-3{
    background:#ffffff;
    padding:10px 12px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(15,23,42,0.08);
    border:1px solid #e2e8f0;
}
form .form-select{
    border-radius:10px;
    border:1px solid #d4d4d8;
    background:#f9fafb;
}
.btn-primary.w-100{
    border-radius:999px;
    font-weight:600;
}
.calendar-wrapper{
    background:#ffffff;
    padding:12px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(15,23,42,0.10);
    border:1px solid #e2e8f0;
}
.calendar-table{
    width:100%;
    table-layout:fixed;
}
.calendar-table thead th{
    text-align:center;
    background:#f9fafb;
    font-size:11px;
    font-weight:600;
    padding:10px 4px;
    border-bottom:1px solid #e5e7eb;
}
.day-cell{
    height:var(--day-height-mobile);
    border:1px solid #e5e7eb;
    padding:6px;
    vertical-align:top;
    transition:0.15s;
}
.day-today{
    background:#eff6ff !important;
    border:2px solid #2563eb !important;
}
.day-num{
    font-weight:700;
    font-size:13px;
    width:26px;
    height:26px;
    background:#e5edff;
    border-radius:99px;
    text-align:center;
    line-height:26px;
    margin-bottom:4px;
}
.event-block{
    padding:3px 6px;
    border-radius:99px;
    font-size:11px;
    margin-top:2px;
    border:1px solid rgba(15,23,42,0.06);
}
.more-count{font-size:11px;color:#2563eb;margin-top:2px;}

/* NEW MODAL STYLES - NO SCROLL */
.modal-content {
    border-radius: 15px;
    overflow: hidden;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    padding: 15px 25px;
    flex-shrink: 0;
}

.modal-title {
    font-weight: 600;
    font-size: 1.2rem;
}

.modal-body {
    padding: 25px;
    background: #f8f9fa;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.modal-footer {
    border-top: 1px solid #eaeaea;
    padding: 12px 25px;
    background: white;
    flex-shrink: 0;
}

/* Compact Grid Layout - Fits all events without scrolling */
.events-grid-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 100%;
    overflow: hidden;
}

/* Grid for multiple events */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    overflow: hidden;
    flex: 1;
}

/* Single Event Card - Compact Design */
.event-card-compact {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.07);
    border: 1px solid #eaeaea;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 180px;
    max-height: 220px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.event-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

/* Compact Header */
.compact-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
    flex-shrink: 0;
}

.compact-subject {
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0;
    line-height: 1.2;
    flex: 1;
    max-height: 38px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.compact-badge {
    background: #667eea;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    margin-left: 8px;
    flex-shrink: 0;
}

/* Compact Body - Two Column Layout */
.compact-body {
    display: flex;
    gap: 15px;
    flex: 1;
    min-height: 0;
}

/* Left Column - Class Info */
.compact-class-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
}

.compact-detail {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.compact-icon {
    color: #667eea;
    font-size: 0.85rem;
    flex-shrink: 0;
    margin-top: 2px;
    width: 16px;
    text-align: center;
}

.compact-text {
    flex: 1;
}

.compact-label {
    font-size: 0.75rem;
    color: #718096;
    margin-bottom: 1px;
}

.compact-value {
    font-size: 0.85rem;
    color: #2d3748;
    font-weight: 500;
    line-height: 1.2;
    max-height: 32px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Right Column - Expert Info */
.compact-expert-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
    padding-left: 10px;
    border-left: 1px dashed #eaeaea;
}

.expert-header-compact {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.expert-photo-compact {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #667eea;
    flex-shrink: 0;
}

.expert-name-compact {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0;
    line-height: 1.2;
    max-height: 20px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.expert-qual-compact {
    font-size: 0.75rem;
    color: #667eea;
    font-weight: 500;
    margin: 2px 0 0 0;
    max-height: 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.compact-expert-details {
    display: grid;
    grid-template-columns: 1fr;
    gap: 6px;
}

.compact-expert-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 6px;
    background: #f8f9ff;
    border-radius: 5px;
    border: 1px solid #eaeaea;
}

.compact-expert-icon {
    color: #667eea;
    font-size: 0.7rem;
    flex-shrink: 0;
}

.compact-expert-text {
    flex: 1;
    overflow: hidden;
}

.compact-expert-label {
    font-size: 0.7rem;
    color: #718096;
    white-space: nowrap;
}

.compact-expert-value {
    font-size: 0.75rem;
    color: #2d3748;
    font-weight: 500;
    max-height: 16px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* No events styling */
.no-events-compact {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 200px;
    width: 100%;
}

.no-events-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #cbd5e0;
}

.no-events-compact h4 {
    color: #718096;
    margin-bottom: 8px;
    font-weight: 500;
    font-size: 1.1rem;
}

.no-events-compact p {
    color: #a0aec0;
    margin: 0;
    font-size: 0.9rem;
}

/* Events counter */
.events-counter {
    font-size: 0.85rem;
    color: #718096;
    text-align: center;
    padding: 8px 0;
    background: #f8f9ff;
    border-radius: 8px;
    margin-bottom: 10px;
    flex-shrink: 0;
}

.events-counter strong {
    color: #667eea;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .events-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .event-card-compact {
        min-height: 200px;
    }
}

@media (max-width: 768px) {
    .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .event-card-compact {
        min-height: 180px;
        max-height: 200px;
    }
    
    .compact-body {
        flex-direction: column;
        gap: 10px;
    }
    
    .compact-expert-info {
        padding-left: 0;
        border-left: none;
        border-top: 1px dashed #eaeaea;
        padding-top: 10px;
    }
    
    .expert-header-compact {
        justify-content: center;
        text-align: center;
    }
}

@media (max-width: 576px) {
    .modal-header {
        padding: 12px 20px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .event-card-compact {
        padding: 12px;
        min-height: 170px;
    }
    
    .compact-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .compact-badge {
        margin-left: 0;
        align-self: flex-start;
    }
}

/* Limit max number of cards per row */
@media (min-width: 1400px) {
    .events-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>
</head>

<body>

<div class="container mt-4">

    <div class="calendar-header"><?= "$month_name $year_full" ?></div>

    <!-- Filter -->
    <form method="GET" class="row g-2 mb-3">

        <div class="col-md-4 col-12">
            <select name="month" class="form-select">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= ($m==$month?"selected":"") ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-4 col-12">
            <select name="year" class="form-select">
                <?php for($y=date("Y")-2;$y<=date("Y")+1;$y++): ?>
                <option value="<?= $y ?>" <?= ($y==$year_full?"selected":"") ?>>
                    <?= $y ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-3 col-12">
            <select name="group" class="form-select">
                <option value="ALL">MASTER CALENDAR</option>
                <option value="25">B.TECH - 25</option>
                <option value="24">B.TECH - 24</option>
                <option value="23">B.TECH - 23</option>
                <option value="22">B.TECH - 22</option>
                <option value="DIP">DIP</option>
                <option value="PG">PG</option>
            </select>
        </div>

        <div class="col-md-1 col-12">
            <button class="btn btn-primary w-100">Load</button>
        </div>

    </form>

    <!-- Calendar -->
    <div class="bg-white p-3 rounded shadow-sm calendar-wrapper">
        <div class="table-responsive">
            <table class="table calendar-table">
                <thead>
                    <tr>
                        <th>Sun</th><th>Mon</th><th>Tue</th>
                        <th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $day = 1;
                for ($r=0; $r<6; $r++){
                    echo "<tr>";
                    for ($c=0; $c<7; $c++){

                        if ($r==0 && $c<$start_weekday){
                            echo "<td class='day-cell'></td>";
                            continue;
                        }

                        if ($day > $days_in_month){
                            echo "<td class='day-cell'></td>";
                            continue;
                        }

                        $isToday = ($day==$current_day && $month==$current_month && $year_full==$current_year);

                        echo "<td class='day-cell ".($isToday?"day-today":"")."' onclick='openDayDetails($day)'>";

                        echo "<div class='day-num'>$day</div>";

                        /* Hidden data */
                        if (!empty($events[$day])){
                            echo "<div id='daydata-$day' class='d-none'>";

                            foreach ($events[$day] as $grp=>$arr){
                                foreach($arr as $ev){

                                    echo "<div class='evitem'
                                        data-subject='".htmlspecialchars($ev['subject'],ENT_QUOTES)."'
                                        data-topic='".htmlspecialchars($ev['topic'],ENT_QUOTES)."'
                                        data-expert='".htmlspecialchars($ev['expert_name'],ENT_QUOTES)."'
                                        data-expqual='".htmlspecialchars($ev['expert_qualification'],ENT_QUOTES)."'
                                        data-expexp='".htmlspecialchars($ev['expert_experience'],ENT_QUOTES)."'
                                        data-expfrom='".htmlspecialchars($ev['expert_from'],ENT_QUOTES)."'
                                        data-expphone='".htmlspecialchars($ev['expert_phone'],ENT_QUOTES)."'
                                        data-expphoto='".htmlspecialchars($ev['expert_photo'],ENT_QUOTES)."'
                                        data-venue='".htmlspecialchars($ev['venue'],ENT_QUOTES)."'
                                        data-start='".htmlspecialchars($ev['start_time'],ENT_QUOTES)."'
                                        data-end='".htmlspecialchars($ev['end_time'],ENT_QUOTES)."'
                                        data-prog='".normalize_prog($ev['prog'])."'
                                        data-year='{$ev['year']}'
                                        ></div>";
                                }
                            }

                            echo "</div>";
                        }

                        /* Visible events */
                        if (!empty($events[$day])){
                            foreach ($events[$day] as $grp=>$arr){

                                $color = $year_colors[$grp] ?? "#ddd";
                                $shown = 0;

                                foreach($arr as $ev){
                                    if($shown>=2) break;

                                    echo "<div class='event-block' style='background:$color'>
                                            ".htmlspecialchars($ev['subject'])."
                                          </div>";
                                    $shown++;
                                }

                                if (count($arr)>2){
                                    echo "<div class='more-count'>+".(count($arr)-2)." more</div>";
                                }
                            }
                        }

                        echo "</td>";
                        $day++;
                    }
                    echo "</tr>";
                }
                ?>

                </tbody>

            </table>
        </div>
    </div>

</div>


<!-- MODAL -->
<div class="modal fade" id="dayModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">📅 Day Events - <span id="modalDayTitle"></span></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="events-grid-container">
          <div id="eventsCounter" class="events-counter"></div>
          <div class="events-grid" id="dayModalBody">
            <!-- Content will be dynamically inserted here -->
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>


<script>
function openDayDetails(day){
    // Update modal title
    document.getElementById("modalDayTitle").textContent = "Day " + day;
    
    let box = document.getElementById("daydata-"+day);
    let modalContent = "";

    if(!box || box.children.length === 0){
        // No events
        modalContent = `
            <div class="no-events-compact">
                <span class="no-events-icon">📅</span>
                <h4>No Events Scheduled</h4>
                <p>There are no classes or events scheduled for this day.</p>
            </div>
        `;
        
        document.getElementById("eventsCounter").innerHTML = "";
    }
    else{
        let items = box.querySelectorAll(".evitem");
        
        // Update counter
        document.getElementById("eventsCounter").innerHTML = 
            `Showing <strong>${items.length}</strong> event${items.length > 1 ? 's' : ''} for this day`;
        
        items.forEach((ev, index) => {
            // Compact card with both class and expert info
            modalContent += `
            <div class="event-card-compact">
                <div class="compact-header">
                    <h3 class="compact-subject">${ev.dataset.subject}</h3>
                    <span class="compact-badge">${ev.dataset.prog} - Year ${ev.dataset.year}</span>
                </div>
                
                <div class="compact-body">
                    <!-- Left Column: Class Info -->
                    <div class="compact-class-info">
                        <div class="compact-detail">
                            <span class="compact-icon">📚</span>
                            <div class="compact-text">
                                <div class="compact-label">Topic</div>
                                <div class="compact-value">${ev.dataset.topic}</div>
                            </div>
                        </div>
                        
                        <div class="compact-detail">
                            <span class="compact-icon">🏢</span>
                            <div class="compact-text">
                                <div class="compact-label">Venue</div>
                                <div class="compact-value">${ev.dataset.venue}</div>
                            </div>
                        </div>
                        
                        <div class="compact-detail">
                            <span class="compact-icon">🕐</span>
                            <div class="compact-text">
                                <div class="compact-label">Time</div>
                                <div class="compact-value">${ev.dataset.start} - ${ev.dataset.end}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Expert Info -->
                    <div class="compact-expert-info">
                        <div class="expert-header-compact">
                            <img src="${ev.dataset.expphoto || 'https://via.placeholder.com/40x40?text=E'}" 
                                 alt="${ev.dataset.expert || 'Expert'}" 
                                 class="expert-photo-compact">
                            <div>
                                <h4 class="expert-name-compact">${ev.dataset.expert || 'Expert Not Specified'}</h4>
                                <p class="expert-qual-compact">${ev.dataset.expqual || 'Not specified'}</p>
                            </div>
                        </div>
                        
                        <div class="compact-expert-details">
                            <div class="compact-expert-item">
                                <span class="compact-expert-icon">📅</span>
                                <div class="compact-expert-text">
                                    <div class="compact-expert-label">Experience</div>
                                    <div class="compact-expert-value">${ev.dataset.expexp || 'Not specified'}</div>
                                </div>
                            </div>
                            
                            <div class="compact-expert-item">
                                <span class="compact-expert-icon">📍</span>
                                <div class="compact-expert-text">
                                    <div class="compact-expert-label">Location</div>
                                    <div class="compact-expert-value">${ev.dataset.expfrom || 'Not specified'}</div>
                                </div>
                            </div>
                            
                            <div class="compact-expert-item">
                                <span class="compact-expert-icon">📞</span>
                                <div class="compact-expert-text">
                                    <div class="compact-expert-label">Contact</div>
                                    <div class="compact-expert-value">${ev.dataset.expphone || 'Not specified'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            `;
        });
    }

    document.getElementById("dayModalBody").innerHTML = modalContent;
    
    // Show the modal
    new bootstrap.Modal(document.getElementById("dayModal")).show();
}
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>