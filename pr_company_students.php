<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != "PR") {
    die("ACCESS DENIED");
}

include "db.php";

// ---------- VALIDATION ----------
if (!isset($_GET['company']) || $_GET['company'] == "") {
    die("Invalid Company");
}

$company = $conn->real_escape_string($_GET['company']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Placed Students - <?php echo $company; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 25%, #a18cd1 50%, #fbc2eb 75%, #ff9a9e 100%);
        }

        .top-bar {
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .back {
            padding: 8px 18px;
            background: rgba(0,0,0,0.75);
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .back:hover {
            background: rgba(0,0,0,0.9);
            transform: translateY(-2px);
            box-shadow: 0 7px 16px rgba(0,0,0,0.3);
        }

        .back span.arrow {
            font-size: 16px;
        }

        .title-block {
            text-align: right;
            color: #ffffff;
            text-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        .title-block h2 {
            font-size: 26px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .title-block p {
            font-size: 14px;
            margin-top: 4px;
            opacity: 0.9;
        }

        .company-chip {
            display: inline-block;
            margin-top: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
            font-size: 13px;
        }

        .company-chip strong {
            font-weight: 700;
        }

        .cards-wrapper {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 18px;
        }

        .card {
            width: 260px;
            background: rgba(255,255,255,0.96);
            border-radius: 20px;
            padding: 16px 14px 18px;
            box-shadow: 0 12px 25px rgba(0,0,0,0.18);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        /* colorful strip on top */
        .card::before {
            content: "";
            position: absolute;
            left: -20%;
            top: -40px;
            width: 140%;
            height: 80px;
            background: linear-gradient(90deg, #ff6b6b, #feca57, #ff9f43);
            opacity: 0.95;
            transform: skewY(-6deg);
        }

        .card:nth-child(3n+1)::before {
            background: linear-gradient(90deg, #48dbfb, #1dd1a1, #00d2d3);
        }

        .card:nth-child(3n+2)::before {
            background: linear-gradient(90deg, #5f27cd, #341f97, #a29bfe);
        }

        .card:nth-child(3n+3)::before {
            background: linear-gradient(90deg, #ff6b6b, #ee5253, #ff9f43);
        }

        .card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 18px 32px rgba(0,0,0,0.25);
        }

        .photo-wrap {
            margin-top: 38px; /* space for strip */
            display: flex;
            justify-content: center;
        }

        .card img {
            width: 110px;
            height: 110px;
            border-radius: 14px;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.9);
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
        }

        .name {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            color: #333;
        }

        .label {
            font-size: 14px;
            margin-top: 4px;
            color: #555;
        }

        .label span.heading {
            font-weight: bold;
        }

        .address {
            font-size: 13px;
            margin-top: 10px;
            border-radius: 14px;
            padding: 8px 10px;
            line-height: 1.35;
            background: linear-gradient(135deg, #007bff, #00c6ff);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
        }

        .footer-note {
            margin-top: 18px;
            font-size: 12px;
            color: #ffffff;
            opacity: 0.85;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 16px 10px;
            }

            .top-bar {
                justify-content: center;
                text-align: center;
            }

            .title-block {
                text-align: center;
            }

            .title-block h2 {
                font-size: 22px;
            }

            .card {
                width: 100%;
                max-width: 340px;
            }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="pr_companies.php" class="back">
        <span class="arrow">←</span>
        Back to Companies
    </a>

    <div class="title-block">
        <h2>Placed Students</h2>
       
        <div class="company-chip">
            Company: <strong><?php echo htmlspecialchars($company); ?></strong>
        </div>
    </div>
</div>

<div class="cards-wrapper">
<?php
// ---- Get students placed in selected company ----
$q = "
    SELECT d.htno, d.name, s.branch, s.phone, s.photo,
           s.village, s.mandal, s.dist, s.state
    FROM PLACEMENT_DETAILS d
    LEFT JOIN STUDENTS s ON d.htno = s.htno
    WHERE d.placed_company = '$company'
    ORDER BY d.name ASC
";

$res = $conn->query($q);

if ($res->num_rows == 0) {
    echo "<p style='color:#fff; font-weight:bold;'>No students placed in this company.</p>";
} else {
    while ($row = $res->fetch_assoc()) {

        $photo = ($row['photo'] != "") ? $row['photo'] : "no_image.png"; // fallback

        $addressParts = array_filter([
            $row['village'],
            $row['mandal'],
            $row['dist'],
            $row['state']
        ]);
        $address = implode(", ", $addressParts);

        echo "
        <div class='card'>
            <div class='photo-wrap'>
                <img src='{$photo}' alt='Photo'>
            </div>
            <div class='name'>".htmlspecialchars($row['name'])."</div>
            <div class='label'><span class='heading'>HT No:</span> ".htmlspecialchars($row['htno'])."</div>
            <div class='label'><span class='heading'>Branch:</span> ".htmlspecialchars($row['branch'])."</div>
            <div class='label'><span class='heading'>Phone:</span> ".htmlspecialchars($row['phone'])."</div>
            <div class='address'>".htmlspecialchars($address)."</div>
        </div>
        ";
    }
}
?>
</div>

<div class="footer-note">
    Scroll to explore all placed students. Screenshot or print for colorful poster display.
</div>

</body>
</html>
