<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ADMIN','CALENDAR'])) {
    header('Location: calender_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* Load user defaults from USERS */
$q = $conn->prepare("SELECT prog, year, classid FROM USERS WHERE id = ?");
$q->bind_param("i", $user_id);
$q->execute();
$user_data = $q->get_result()->fetch_assoc();

$user_prog  = $user_data['prog'] ?? '';
$user_year  = $user_data['year'] ?? '';
$user_class = $user_data['classid'] ?? '';

$prog = $_POST['prog'] ?? $user_prog;
$year = $_POST['year'] ?? $user_year;

$classids = [];
$errors = [];

/* FETCH EXPERTS */
$experts = [];
$rq = $conn->query("SELECT expert_id, expert_name FROM EXPERTS ORDER BY expert_name");
while ($row = $rq->fetch_assoc()) {
    $experts[] = $row;
}

/* FETCH SUBJECTS */
$subjects = [];
$sq = $conn->query("SELECT id, subject_code, subject_name FROM SEM_SUBJECTS ORDER BY subject_name");
while ($row = $sq->fetch_assoc()) {
    $subjects[] = $row;
}

/* FETCH CLASSIDS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_classids'])) {

    if (!$prog || !$year) {
        $errors[] = "Select program and year.";
    } else {
        $sql = "SELECT DISTINCT classid FROM STUDENTS 
                WHERE prog = ? AND year = ? 
                AND classid IS NOT NULL AND classid!=''
                ORDER BY classid";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $prog, $year);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($r = $res->fetch_assoc()) {
            $cid = $r['classid'];

            $cntQ = $conn->prepare("SELECT COUNT(*) AS total FROM STUDENTS WHERE classid=?");
            $cntQ->bind_param("s", $cid);
            $cntQ->execute();
            $count = $cntQ->get_result()->fetch_assoc()['total'];

            $classids[] = [
                "classid" => $cid,
                "count"   => $count
            ];
        }

        if ($_SESSION['role'] === 'CALENDAR' && !empty($user_class) && !empty($user_year)) {
            $classids = array_filter($classids, fn($c) => $c['classid'] === $user_class);
        }

        if (!$classids) {
            $errors[] = "No class IDs found.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Add Calendar Event</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
* { font-family: 'Poppins', sans-serif; }
body { background: #eef2f7; }
.card { border-radius: 14px; box-shadow: 0 4px 18px rgba(0,0,0,0.1); }
#expert_list div:hover, 
#subject_list div:hover { background:#f1f1f1; cursor:pointer; }
</style>
</head>

<body>

<div class="container mt-4">
<div class="card">
<div class="card-body">

<h4 class="mb-3 text-primary">Create Event</h4>

<?php foreach($errors as $e): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<!-- STEP 1: SELECT PROGRAM + YEAR -->
<form method="post" class="mb-4">

    <div class="row">
        <!-- PROGRAM -->
        <div class="col-md-4 mb-3">
            <label>Program</label>
            <?php if ($_SESSION['role'] === 'CALENDAR' && !empty($user_prog)): ?>
                <input type="text" class="form-control" value="<?= $user_prog; ?>" readonly>
                <input type="hidden" name="prog" value="<?= $user_prog; ?>">
            <?php else: ?>
                <select name="prog" class="form-select" required>
                    <option value="">-- Select Program --</option>
                    <?php
                    $r = $conn->query("SELECT DISTINCT prog FROM STUDENTS WHERE prog!='' ORDER BY prog");
                    while ($row = $r->fetch_assoc()):
                        $p = $row['prog'];
                    ?>
                    <option value="<?= $p; ?>" <?= $prog==$p?'selected':''; ?>><?= $p; ?></option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- YEAR -->
        <div class="col-md-4 mb-3">
            <label>Year</label>

            <?php
            if ($_SESSION['role'] === 'CALENDAR' && !empty($user_year)): ?>
                
                <input type="text" class="form-control" value="<?= $user_year; ?>" readonly>
                <input type="hidden" name="year" value="<?= $user_year; ?>">

            <?php
            else: ?>

                <select name="year" class="form-select" required>
                    <option value="">-- Select Year --</option>
                    <?php
                    $yr = $conn->query("SELECT DISTINCT year FROM STUDENTS WHERE year IS NOT NULL ORDER BY year");
                    while ($r2 = $yr->fetch_assoc()):
                        $y = $r2['year'];
                    ?>
                    <option value="<?= $y; ?>" <?= $year==$y?'selected':''; ?>><?= $y; ?></option>
                    <?php endwhile; ?>
                </select>

            <?php endif; ?>

        </div>

        <!-- FETCH BUTTON -->
        <div class="col-md-4 mb-3 align-self-end">
            <button type="submit" name="fetch_classids" class="btn btn-outline-primary w-100">
                Fetch Class IDs
            </button>
        </div>
    </div>
</form>

<!-- STEP 2: MAIN EVENT FORM -->
<?php if (!empty($classids)): ?>
<form method="post" action="calendar_save.php">

    <input type="hidden" name="prog" value="<?= $prog; ?>">
    <input type="hidden" name="year" value="<?= $year; ?>">

    <label>Class IDs</label>
    <div class="border rounded p-3 mb-3" style="max-height:250px; overflow-y:auto;" id="classidBox">

        <?php foreach ($classids as $c): ?>
            <div class="form-check">
                <input class="form-check-input class-check"
                       type="checkbox"
                       value="<?= $c['classid']; ?>"
                       data-count="<?= $c['count']; ?>"
                       name="classids[]">

                <label class="form-check-label">
                    <?= $c['classid']; ?>
                    <span class="text-primary fw-bold">(<?= $c['count']; ?>)</span>
                </label>
            </div>
        <?php endforeach; ?>

    </div>

    <h6 class="text-success mb-4">
        Selected Classes: <span id="selClasses">0</span> |
        Total Students: <span id="selStudents">0</span>
    </h6>

    <div class="row">
        <div class="col-md-3 mb-3">
            <label>Date</label>
            <input type="date" name="event_date" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
            <label>Class Type</label>
            <select name="classtype" class="form-select" required>
                <option value="">-- Select --</option>
                <option>GATE EXPERT</option>
                <option>CAMPUS EXPERT</option>
                <option>PLACEMENT EXPERT</option>
                <option>KIOT</option>
                <option>HACKATHON</option>
                <option>PROJECT</option>
            </select>
        </div>

        <!-- EXPERT AUTO SUGGEST -->
        <div class="col-md-5 mb-3 position-relative">
            <label>Expert</label>

            <input type="hidden" name="expert_id" id="expert_id" required>

            <input type="text" id="expert_search" class="form-control"
                   placeholder="Type expert name…" autocomplete="off">

            <div id="expert_list"
                 class="border bg-white rounded mt-1"
                 style="display:none; position:absolute; z-index:9999; width:95%; max-height:200px; overflow:auto;">
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-3 mb-3">
            <label>Start Time</label>
            <input type="time" name="start_time" class="form-control" required>
        </div>

        <div class="col-md-3 mb-3">
            <label>End Time</label>
            <input type="time" name="end_time" class="form-control" required>
        </div>

    </div>

    <!-- TYPE FIELD -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Type</label>
            <select name="TYPE" id="TYPE" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="OFFLINE">OFFLINE</option>
                <option value="ONLINE">ONLINE</option>
                
            </select>
        </div>
    </div>

    <!-- FACULTY COORDINATOR -->
    <div class="mb-3">
        <label>Faculty Coordinator</label>
        <input type="text" name="faculty_coordinator" class="form-control" required>
    </div>

    <!-- SUBJECT AUTOSUGGEST -->
    <div class="row">

        <div class="col-md-6 mb-3 position-relative">
            <label>Subject</label>

            <input type="hidden" name="subject" id="subject_value" required>

            <input type="text" id="subject_search" class="form-control"
                   placeholder="Type subject…" autocomplete="off">

            <div id="subject_list"
                 class="border bg-white rounded mt-1"
                 style="display:none; position:absolute; z-index:9999; width:95%; max-height:200px; overflow:auto;">
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label>Topic</label>
            <input type="text" name="topic" class="form-control" required>
        </div>

    </div>

    <!-- VENUE (ONLY FOR OFFLINE) -->
    <div class="mb-3" id="venue_block" style="display:none;">
        <label>Venue</label>
        <input type="text" name="venue" id="venue" class="form-control">
    </div>

    <div class="text-end">
        <a href="calendar_dashboard.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Event</button>
    </div>

</form>
<?php endif; ?>

</div>
</div>
</div>

<!-- COUNT SCRIPT -->
<script>
function updateTotals() {
    let boxes = document.querySelectorAll(".class-check");
    let totalClasses = 0;
    let totalStudents = 0;

    boxes.forEach(b => {
        if (b.checked) {
            totalClasses++;
            totalStudents += parseInt(b.getAttribute("data-count"));
        }
    });

    document.getElementById("selClasses").innerText = totalClasses;
    document.getElementById("selStudents").innerText = totalStudents;
}

document.querySelectorAll(".class-check").forEach(box => {
    box.addEventListener("change", updateTotals);
});
</script>

<!-- EXPERT AUTOSUGGEST -->
<script>
let experts = <?= json_encode($experts); ?>;

const searchBox = document.getElementById("expert_search");
const listBox   = document.getElementById("expert_list");
const expertId  = document.getElementById("expert_id");

searchBox.addEventListener("keyup", function () {
    let q = this.value.toLowerCase().trim();
    listBox.innerHTML = "";

    if (q.length === 0) {
        listBox.style.display = "none";
        return;
    }

    let filtered = experts.filter(e => e.expert_name.toLowerCase().includes(q));

    if (filtered.length === 0) {
        listBox.style.display = "none";
        return;
    }

    filtered.forEach(e => {
        let div = document.createElement("div");
        div.innerHTML = e.expert_name;
        div.classList.add("p-2", "border-bottom");

        div.onclick = function () {
            searchBox.value = e.expert_name;
            expertId.value  = e.expert_id;
            listBox.style.display = "none";
        };

        listBox.appendChild(div);
    });

    listBox.style.display = "block";
});
</script>

<!-- SUBJECT AUTOSUGGEST -->
<script>
let subjects = <?= json_encode($subjects); ?>;

const subSearch = document.getElementById("subject_search");
const subList   = document.getElementById("subject_list");
const subValue  = document.getElementById("subject_value");

subSearch.addEventListener("keyup", function () {
    let q = this.value.toLowerCase().trim();
    subList.innerHTML = "";

    if (q.length === 0) {
        subList.style.display = "none";
        return;
    }

    let filtered = subjects.filter(s =>
        s.subject_name.toLowerCase().includes(q) ||
        (s.subject_code && s.subject_code.toLowerCase().includes(q))
    );

    if (filtered.length === 0) {
        subList.style.display = "none";
        return;
    }

    filtered.forEach(s => {
        let div = document.createElement("div");

        div.innerHTML = `<strong>${s.subject_name}</strong>
                         <span class='text-muted'> (${s.subject_code})</span>`;
        div.classList.add("p-2", "border-bottom");

        div.onclick = function () {
            subSearch.value = s.subject_name;
            subValue.value  = s.subject_name;
            subList.style.display = "none";
        };

        subList.appendChild(div);
    });

    subList.style.display = "block";
});
</script>

<!-- VENUE SHOW/HIDE SCRIPT -->
<script>
document.getElementById("TYPE").addEventListener("change", function () {
    let vBlock = document.getElementById("venue_block");
    let venue  = document.getElementById("venue");

    if (this.value === "OFFLINE") {
        vBlock.style.display = "block";
        venue.setAttribute("required", "required");
    } else {
        vBlock.style.display = "none";
        venue.removeAttribute("required");
        venue.value = "";
    }
});
</script>

</body>
</html>
