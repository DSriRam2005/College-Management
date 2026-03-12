<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Calendar Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            background: #f4f5f7;
            font-family: Arial, sans-serif;
        }

        /* TOPBAR */
        .topbar {
            background: #16222b;
            padding: 12px 18px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1000;
        }

        .topbar .left {
            font-size: 17px;
            font-weight: 600;
        }

        /* BUTTON WRAPPER (ONE CODE FOR BOTH) */
        .menu-buttons {
            display: flex;
            gap: 8px;
        }

        .menu-buttons .btn {
            padding: 5px 12px;
            font-size: 14px;
        }

        /* HAMBURGER */
        .hamburger {
            display: none;
            font-size: 26px;
            cursor: pointer;
            padding: 6px;
        }

        /* MOBILE MENU (same buttons) */
        .menu-buttons.mobile {
            display: none;
            flex-direction: column;
            background: #16222b;
            padding: 12px 20px;
            position: absolute;
            right: 0;
            top: 55px;
            width: 200px;
            box-shadow: -2px 4px 10px rgba(0,0,0,0.3);
            border-bottom-left-radius: 10px;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-5px);}
            to {opacity: 1; transform: translateY(0);}
        }

        /* MOBILE RESPONSIVE */
        @media(max-width: 768px) {
            .menu-buttons.desktop { display: none; }
            .hamburger { display: block; }
        }

        iframe {
            width: 100%;
            height: calc(100vh - 55px);
            border: 0;
        }
    </style>
</head>

<body>

<div class="topbar">

    <div class="left">
        <i class="fas fa-calendar-alt me-2"></i> Academic Calendar
    </div>

    <!-- HAMBURGER -->
    <i class="fas fa-bars hamburger" onclick="toggleMenu()"></i>

    <!-- ONE SET OF BUTTONS (DESKTOP MODE) -->
    <div class="menu-buttons desktop" id="desktopButtons">
        <button class="btn btn-light btn-sm" onclick="loadPage('calendar_view.php')">View Calendar</button>
        <button class="btn btn-primary btn-sm" onclick="loadPage('experts.php')">Experts</button>
        <button class="btn btn-primary btn-sm" onclick="loadPage('calendar_add.php')">Add Event</button>
<button class="btn btn-primary btn-sm" onclick="loadPage('add_subject.php')">Add Subject</button>
        <button class="btn btn-danger btn-sm" onclick="document.getElementById('logoutForm').submit();">Logout</button>
    </div>

    <!-- SAME BUTTONS (MOBILE DROPDOWN) -->
    <div class="menu-buttons mobile" id="mobileMenu">
        <button class="btn btn-light btn-sm" onclick="loadPage('calendar_view.php'); toggleMenu();">View Calendar</button>
        <button class="btn btn-primary btn-sm" onclick="loadPage('experts.php'); toggleMenu();">Experts</button>
        <button class="btn btn-primary btn-sm" onclick="loadPage('calendar_add.php'); toggleMenu();">Add Event</button>
        <button class="btn btn-primary btn-sm" onclick="loadPage('add_subject.php')">Add Subject</button>
        <button class="btn btn-danger btn-sm" onclick="document.getElementById('logoutForm').submit();">Logout</button>
    </div>

</div>

<form id="logoutForm" action="calendar_logout.php" method="post" style="display:none;">
    <input type="hidden" name="logout" value="1">
</form>

<iframe id="mainFrame" src="calendar_view.php"></iframe>

<script>
function loadPage(page) {
    document.getElementById("mainFrame").src = page;
}

function toggleMenu() {
    let m = document.getElementById("mobileMenu");
    m.style.display = (m.style.display === "flex") ? "none" : "flex";
}

/* CLOSE MOBILE MENU WHEN CLICK OUTSIDE */
document.addEventListener("click", function(e) {
    let menu = document.getElementById("mobileMenu");
    let burger = document.querySelector(".hamburger");

    if (!menu.contains(e.target) && !burger.contains(e.target)) {
        menu.style.display = "none";
    }
});
</script>

</body>
</html>
