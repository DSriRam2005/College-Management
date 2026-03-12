<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != "PR") {
    die("ACCESS DENIED");
}

include "db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Placed Companies - PR Panel</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 25%, #fbc2eb 50%, #a18cd1 75%, #84fab0 100%);
        }

        .page-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .page-title h2 {
            font-size: 32px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ffffff;
            text-shadow: 0 4px 10px rgba(0,0,0,0.25);
            margin-bottom: 8px;
        }

        .page-title p {
            font-size: 14px;
            color: #fdfdfd;
            opacity: 0.9;
        }

        .company-grid {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .company-box {
            width: 260px;
            padding: 16px 16px 18px;
            border-radius: 18px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            background: linear-gradient(145deg, rgba(255,255,255,0.90), rgba(255,255,255,0.75));
            box-shadow: 0 12px 25px rgba(0,0,0,0.18);
            transition: transform 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
        }

        /* Colorful top strip */
        .company-box::before {
            content: "";
            position: absolute;
            left: -20%;
            top: -40px;
            width: 140%;
            height: 80px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb, #5f27cd);
            opacity: 0.9;
            transform: skewY(-5deg);
        }

        /* Different color themes */
        .company-box:nth-child(3n+1)::before {
            background: linear-gradient(90deg, #ff6b6b, #ff9f43, #ffcd94);
        }

        .company-box:nth-child(3n+2)::before {
            background: linear-gradient(90deg, #1dd1a1, #10ac84, #48dbfb);
        }

        .company-box:nth-child(3n+3)::before {
            background: linear-gradient(90deg, #5f27cd, #341f97, #a29bfe);
        }

        .company-box:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 18px 32px rgba(0,0,0,0.25);
        }

        .company-logo-wrap {
            margin-top: 35px; /* to clear the colored strip */
            display: flex;
            justify-content: center;
        }

        .company-logo-wrap img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            border-radius: 12px;
            padding: 6px;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .company-name {
            margin-top: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            text-align: center;
        }

        .count-box {
            margin-top: 10px;
            padding: 8px 10px;
            font-size: 14px;
            border-radius: 20px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #fff;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
            display: inline-block;
        }

        .count-wrap {
            margin-top: 6px;
            text-align: center;
        }

        /* Small text under cards (optional) */
        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            color: #ffffff;
            opacity: 0.85;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 16px 10px;
            }

            .page-title h2 {
                font-size: 24px;
            }

            .company-box {
                width: 100%;
                max-width: 340px;
            }
        }
    </style>
</head>
<body>

<div class="page-title">
    <h2>PLACEMENT COMPANIES</h2>
    
</div>

<div class="company-grid">
    <?php
    $companies = $conn->query("SELECT * FROM PLACEMENT_COMPANIES ORDER BY company_name ASC");

    while ($c = $companies->fetch_assoc()) {

        $company = urlencode($c['company_name']);

        $count = $conn->query("
            SELECT COUNT(*) AS total 
            FROM PLACEMENT_DETAILS 
            WHERE placed_company='{$c['company_name']}'
        ")->fetch_assoc()['total'];

        echo "
        <div class='company-box' onclick=\"window.location='pr_company_students.php?company=$company'\">
            <div class='company-logo-wrap'>
                <img src='{$c['company_logo']}' alt='Company Logo'>
            </div>
            <div class='company-name'>{$c['company_name']}</div>
            <div class='count-wrap'>
                <span class='count-box'>Placed: $count Students</span>
            </div>
        </div>
        ";
    }
    ?>
</div>

<div class="footer-note">
    Tap on any company card to view the list of placed students.
</div>

</body>
</html>
