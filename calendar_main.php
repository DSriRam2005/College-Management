<?php ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Academic Calendar - Toggle Views</title>

<!-- REMOVE mobile responsiveness -->
<meta name="viewport" content="width=1200">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* FORCE DESKTOP ALWAYS */
html, body {
    width: 1350px;
    min-width: 1350px !important;
    max-width: 1350px;
    overflow-x: hidden !important;

    height: 100%;
    margin: 0;
    padding: 0;
    background:#eef2f7;
    font-family: Poppins, sans-serif;
}

/* Disable Bootstrap responsive container */
.container {
    width: 1350px !important;
    max-width: 1350px !important;
}

/* Top toggle bar */
.toggle-wrapper {
    width: 1350px;
    margin: 0 auto;
    padding:15px;
    position: fixed;
    top:0;
    left:0;
    right:0;
    z-index:1000;
    background:#eef2f7;
}

.toggle-bar {
    background:white;
    padding:12px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    display:flex;
    gap:10px;
    width:100%;
}

.toggle-btn {
    flex:1;
    padding:12px;
    border-radius:8px;
    font-weight:600;
    text-align:center;
    border:2px solid #2980b9;
    cursor:pointer;
    transition:.2s;
    background:white;
    color:#2980b9;
    user-select:none;
}

.toggle-btn.active {
    background:#2980b9;
    color:#fff;
}

.toggle-btn:hover {
    background:#3498db;
    color:white;
}

/* Full-screen frame (desktop only) */
#calendarFrame {
    position: absolute;
    top: 95px;
    left: 0;
    width: 1350px;
    min-width:1350px;
    height: calc(100vh - 95px);
    border: none;
    background:white;
}
</style>
</head>

<body>

<!-- FULL-WIDTH TOGGLE BAR -->
<div class="toggle-wrapper">
    <div class="toggle-bar">
        <div class="toggle-btn active" id="btn-view" onclick="switchView('view')">
            Date View
        </div>
        <div class="toggle-btn" id="btn-outlook" onclick="switchView('outlook')">
            Month View
        </div>
    </div>
</div>

<!-- FIXED-WIDTH DESKTOP FRAME -->
<iframe id="calendarFrame" src="calendar_monthly_view.php"></iframe>

<script>
function switchView(type) {

    let frame = document.getElementById("calendarFrame");

    // REMOVE ACTIVE
    document.getElementById("btn-view").classList.remove("active");
    document.getElementById("btn-outlook").classList.remove("active");

    if (type === "view") {
        frame.src = "calendar_monthly_view.php";
        document.getElementById("btn-view").classList.add("active");
    } 
    else {
        frame.src = "calendar_monthly_outlook_yearwise.php";
        document.getElementById("btn-outlook").classList.add("active");
    }
}
</script>

</body>
</html>
