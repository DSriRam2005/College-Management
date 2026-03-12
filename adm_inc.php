<?php

session_start();
include 'db.php';

/* Only PR */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'PR') {
    header("Location: index.php");
    exit();
}

/* ================== CARD TOTALS ================== */
$card_sql = "
SELECT
    /* ================= 2025 COUNTS ================= */
    SUM(PHASE='1' AND CLG_TYPE='H') AS ph1,
    SUM(PHASE='2' AND CLG_TYPE='H') AS ph2,
    SUM(PHASE='3' AND CLG_TYPE='H') AS ph3,
    SUM(PHASE='SPOT' AND CLG_TYPE='H') AS spoth,
    SUM(PHASE='BCAT' AND CLG_TYPE='H') AS bcath,

    SUM(PHASE='1' AND CLG_TYPE='D') AS pd1,
    SUM(PHASE='2' AND CLG_TYPE='D') AS pd2,
    SUM(PHASE='3' AND CLG_TYPE='D') AS pd3,
    SUM(PHASE='SPOT' AND CLG_TYPE='D') AS spotd,
    SUM(PHASE='BCAT' AND CLG_TYPE='D') AS bcatd,

    /* ================= 2025 AMOUNTS (BTECH) ================= */
    SUM(PHASE='1' AND CLG_TYPE='H') * 6000  AS ph1_amt,
    SUM(PHASE='2' AND CLG_TYPE='H') * 15000 AS ph2_amt,
    SUM(PHASE='3' AND CLG_TYPE='H') * 15000 AS ph3_amt,
    SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000 AS spoth_amt,
    SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000 AS bcath_amt,

    SUM(PHASE='1' AND CLG_TYPE='D') * 4000  AS pd1_amt,
    SUM(PHASE='2' AND CLG_TYPE='D') * 10000 AS pd2_amt,
    SUM(PHASE='3' AND CLG_TYPE='D') * 10000 AS pd3_amt,
    SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000 AS spotd_amt,
    SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000 AS bcatd_amt,

    COUNT(*) AS total,

    (
      (SUM(PHASE='1' AND CLG_TYPE='H') * 6000) +
      (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) +
      (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) +
      (SUM(PHASE='1' AND CLG_TYPE='D') * 4000) +
      (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) +
      (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000)
    ) AS total_amt,

    /* ================= 2025 INCENTIVES ================= */
    (SELECT IFNULL(SUM(incentive_amount),0)
     FROM admission_incentives
     WHERE admission_year=2025 AND cets='polycet'
    ) AS polycet_2025_amt,

    (SELECT IFNULL(SUM(incentive_amount),0)
     FROM admission_incentives
     WHERE admission_year=2025 AND cets='ecet'
    ) AS ecet_2025_amt,

    /* ================= 2024 INCENTIVES ================= */
    (SELECT IFNULL(SUM(incentive_amount),0)
     FROM admission_incentives
     WHERE admission_year=2024 AND cets='eapcet') AS total_btech_2024,

    (SELECT IFNULL(SUM(incentive_amount),0)
     FROM admission_incentives
     WHERE admission_year=2024 AND cets='polycet') AS polycet_2024,

    (SELECT IFNULL(SUM(incentive_amount),0)
     FROM admission_incentives
     WHERE admission_year=2024 AND cets='ecet') AS ecet_2024,

    (
        (SELECT IFNULL(SUM(incentive_amount),0)
         FROM admission_incentives WHERE admission_year=2024 AND cets='eapcet')
      + (SELECT IFNULL(SUM(incentive_amount),0)
         FROM admission_incentives WHERE admission_year=2024 AND cets='polycet')
      + (SELECT IFNULL(SUM(incentive_amount),0)
         FROM admission_incentives WHERE admission_year=2024 AND cets='ecet')
    ) AS total_2024_admissions,

    /* ================= TOTAL INCENTIVE ================= */
    (
        (
          (SUM(PHASE='1' AND CLG_TYPE='H') * 6000) +
          (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) +
          (SUM(PHASE='1' AND CLG_TYPE='D') * 4000) +
          (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000)
        )
        +
        (SELECT IFNULL(SUM(incentive_amount),0)
         FROM admission_incentives WHERE admission_year IN (2024,2025))
    ) AS total_incentive_amount,

    /* ================= GIVEN & PENDING ================= */
    (SELECT IFNULL(SUM(given_incentives),0)
     FROM admission_incentives_given) AS given_incentives,

    (
        (
          (
            (SUM(PHASE='1' AND CLG_TYPE='H') * 6000) +
            (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) +
            (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) +
            (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) +
            (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) +
            (SUM(PHASE='1' AND CLG_TYPE='D') * 4000) +
            (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) +
            (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) +
            (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) +
            (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000)
          )
          +
          (SELECT IFNULL(SUM(incentive_amount),0)
           FROM admission_incentives WHERE admission_year IN (2024,2025))
        )
        -
        (SELECT IFNULL(SUM(given_incentives),0)
         FROM admission_incentives_given)
    ) AS pending_incentive

FROM STUDENTS
WHERE year = 25
AND prog = 'B.TECH'
AND REFEMPID IS NOT NULL
";


$card_res = mysqli_query($conn, $card_sql);
if (!$card_res) { die(mysqli_error($conn)); }
$cards = mysqli_fetch_assoc($card_res);

$sql = "
SELECT 
    e.empid AS REFEMPID,
    k.NAME AS staff_name,

    /* ===== COUNTS (2025 STUDENTS) ===== */
    IFNULL(st.ph1,0)    AS ph1,
    IFNULL(st.ph2,0)    AS ph2,
    IFNULL(st.ph3,0)    AS ph3,
    IFNULL(st.spoth,0)  AS spoth,
    IFNULL(st.bcath,0)  AS bcath,

    IFNULL(st.pd1,0)    AS pd1,
    IFNULL(st.pd2,0)    AS pd2,
    IFNULL(st.pd3,0)    AS pd3,
    IFNULL(st.spotd,0)  AS spotd,
    IFNULL(st.bcatd,0)  AS bcatd,

    /* ===== PHASE AMOUNTS ===== */
    IFNULL(st.ph1_amt,0)   AS ph1_amt,
    IFNULL(st.ph2_amt,0)   AS ph2_amt,
    IFNULL(st.ph3_amt,0)   AS ph3_amt,
    IFNULL(st.spoth_amt,0) AS spoth_amt,
    IFNULL(st.bcath_amt,0) AS bcath_amt,

    IFNULL(st.pd1_amt,0)   AS pd1_amt,
    IFNULL(st.pd2_amt,0)   AS pd2_amt,
    IFNULL(st.pd3_amt,0)   AS pd3_amt,
    IFNULL(st.spotd_amt,0) AS spotd_amt,
    IFNULL(st.bcatd_amt,0) AS bcatd_amt,

    IFNULL(st.total_count,0) AS total_count,
    IFNULL(st.total_amt,0)   AS total_amt,

    /* ===== INCENTIVES 2025 ===== */
    IFNULL(ai.polycet_2025_amt,0) AS polycet_2025_amt,
    IFNULL(ai.ecet_2025_amt,0)    AS ecet_2025_amt,

    (
        IFNULL(st.total_amt,0)
      + IFNULL(ai.polycet_2025_amt,0)
      + IFNULL(ai.ecet_2025_amt,0)
    ) AS total_2025_admissions,

    /* ===== INCENTIVES 2024 ===== */
    IFNULL(ai.eapcet_2024_amt,0)  AS total_btech_2024,
    IFNULL(ai.polycet_2024_amt,0) AS polycet_2024_amt,
    IFNULL(ai.ecet_2024_amt,0)    AS ecet_2024_amt,

    (
        IFNULL(ai.eapcet_2024_amt,0)
      + IFNULL(ai.polycet_2024_amt,0)
      + IFNULL(ai.ecet_2024_amt,0)
    ) AS total_2024_admissions,

    /* ===== TOTAL INCENTIVE ===== */
    (
        (
            IFNULL(st.total_amt,0)
          + IFNULL(ai.polycet_2025_amt,0)
          + IFNULL(ai.ecet_2025_amt,0)
        )
      +
        (
            IFNULL(ai.eapcet_2024_amt,0)
          + IFNULL(ai.polycet_2024_amt,0)
          + IFNULL(ai.ecet_2024_amt,0)
        )
    ) AS total_incentive_amt,

    /* ===== GIVEN & PENDING ===== */
    IFNULL(aig.given_incentives,0) AS given_incentives,

    (
        (
            (
                IFNULL(st.total_amt,0)
              + IFNULL(ai.polycet_2025_amt,0)
              + IFNULL(ai.ecet_2025_amt,0)
            )
          +
            (
                IFNULL(ai.eapcet_2024_amt,0)
              + IFNULL(ai.polycet_2024_amt,0)
              + IFNULL(ai.ecet_2024_amt,0)
            )
        )
        - IFNULL(aig.given_incentives,0)
    ) AS pending_incentive

FROM
(
    SELECT REFEMPID AS empid
    FROM STUDENTS
    WHERE year=25 AND prog='B.TECH' AND REFEMPID IS NOT NULL
    UNION
    SELECT empid FROM admission_incentives
    UNION
    SELECT empid FROM admission_incentives_given
) e

LEFT JOIN
(
    SELECT 
        REFEMPID,
        SUM(PHASE='1' AND CLG_TYPE='H') AS ph1,
        SUM(PHASE='2' AND CLG_TYPE='H') AS ph2,
        SUM(PHASE='3' AND CLG_TYPE='H') AS ph3,
        SUM(PHASE='SPOT' AND CLG_TYPE='H') AS spoth,
        SUM(PHASE='BCAT' AND CLG_TYPE='H') AS bcath,

        SUM(PHASE='1' AND CLG_TYPE='D') AS pd1,
        SUM(PHASE='2' AND CLG_TYPE='D') AS pd2,
        SUM(PHASE='3' AND CLG_TYPE='D') AS pd3,
        SUM(PHASE='SPOT' AND CLG_TYPE='D') AS spotd,
        SUM(PHASE='BCAT' AND CLG_TYPE='D') AS bcatd,

        (SUM(PHASE='1' AND CLG_TYPE='H') * 6000)  AS ph1_amt,
        (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) AS ph2_amt,
        (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) AS ph3_amt,
        (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) AS spoth_amt,
        (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) AS bcath_amt,

        (SUM(PHASE='1' AND CLG_TYPE='D') * 4000)  AS pd1_amt,
        (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) AS pd2_amt,
        (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) AS pd3_amt,
        (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) AS spotd_amt,
        (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000) AS bcatd_amt,

        COUNT(*) AS total_count,

        (
          (SUM(PHASE='1' AND CLG_TYPE='H') * 6000) +
          (SUM(PHASE='2' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='3' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='SPOT' AND CLG_TYPE='H') * 15000) +
          (SUM(PHASE='BCAT' AND CLG_TYPE='H') * 30000) +

          (SUM(PHASE='1' AND CLG_TYPE='D') * 4000) +
          (SUM(PHASE='2' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='3' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='SPOT' AND CLG_TYPE='D') * 10000) +
          (SUM(PHASE='BCAT' AND CLG_TYPE='D') * 30000)
        ) AS total_amt

    FROM STUDENTS
    WHERE year=25 AND prog='B.TECH' AND REFEMPID IS NOT NULL
    GROUP BY REFEMPID
) st ON e.empid = st.REFEMPID

LEFT JOIN
(
    SELECT 
        empid,
        SUM(CASE WHEN admission_year=2025 AND cets='polycet' THEN incentive_amount ELSE 0 END) AS polycet_2025_amt,
        SUM(CASE WHEN admission_year=2025 AND cets='ecet'    THEN incentive_amount ELSE 0 END) AS ecet_2025_amt,
        SUM(CASE WHEN admission_year=2024 AND cets='eapcet' THEN incentive_amount ELSE 0 END) AS eapcet_2024_amt,
        SUM(CASE WHEN admission_year=2024 AND cets='polycet' THEN incentive_amount ELSE 0 END) AS polycet_2024_amt,
        SUM(CASE WHEN admission_year=2024 AND cets='ecet'    THEN incentive_amount ELSE 0 END) AS ecet_2024_amt
    FROM admission_incentives
    GROUP BY empid
) ai ON e.empid = ai.empid

LEFT JOIN
(
    SELECT empid, SUM(given_incentives) AS given_incentives
    FROM admission_incentives_given
    GROUP BY empid
) aig ON e.empid = aig.empid

LEFT JOIN kiet_staff k ON e.empid = k.EMPID

ORDER BY pending_incentive DESC
";




$result = mysqli_query($conn, $sql);
if (!$result) { die(mysqli_error($conn)); }
?>


<!DOCTYPE html>
<html>
<head>
<title>PR REPORT – Admissions Ref (YEAR-25 | B.TECH)</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
:root{
    --bg:#f4f6fb;
    --card:#ffffff;
    --primary:#1f2937;
    --accent:#2563eb;
    --soft:#e5e7eb;
    --amt:#ecfeff;
    --text:#111;
}

body.dark{
    --bg:#0f172a;
    --card:#1e293b;
    --primary:#020617;
    --accent:#38bdf8;
    --soft:#334155;
    --amt:#0f766e;
    --text:#e5e7eb;
}

*{box-sizing:border-box}
body{
    margin:0;
    font-family:Segoe UI, Arial, sans-serif;
    background:var(--bg);
    color:var(--text);
}

/* ================= HEADER ================= */
.header{
    position:sticky;
    top:0;
    z-index:1000;
    background:var(--card);
    padding:10px;
    border-bottom:1px solid var(--soft);
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:10px;
}

.header h2{
    flex:1;
    margin:0;
    font-size:18px;
}

.controls{
    display:flex;
    gap:10px;
    align-items:center;
}

input[type="text"]{
    padding:6px 10px;
    border:1px solid var(--soft);
    border-radius:6px;
    outline:none;
}

.toggle{
    cursor:pointer;
    padding:6px 10px;
    border-radius:6px;
    background:var(--accent);
    color:#fff;
    border:none;
}

/* ================= CARDS ================= */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(140px,1fr));
    gap:12px;
    padding:15px;
    max-width:1400px;
    margin:auto;
}
.card{
    background:var(--card);
    border-radius:12px;
    padding:14px;
    text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.card span{
    display:block;
    font-size:22px;
    font-weight:700;
    color:var(--accent);
}
.card small{font-size:12px;color:#888}

/* ================= TABLE ================= */
.table-wrap{
    overflow:auto;
    padding:10px;
}

table{
    border-collapse:collapse;
    width:100%;
    min-width:900px;
    background:var(--card);
}

th,td{
    border:1px solid var(--soft);
    padding:6px;
    text-align:center;
    font-size:13px;
    white-space:nowrap;
}

th{
    background:var(--primary);
    color:#fff;
    position:sticky;
    top:0;
    z-index:5;
}

.group{background:#374151!important}
.amtrow{background:var(--amt);font-weight:bold}

/* ================= STICKY COLUMNS ================= */
.sticky-col{
    position:sticky;
    left:0;
    background:var(--card);
    z-index:3;
}
.sticky-col-2{
    position:sticky;
    left:90px;
    background:var(--card);
    z-index:3;
}

/* mobile */
@media(max-width:600px){
    .header h2{font-size:16px}
}
.clickable{
    color:var(--accent);
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
}
.clickable:hover{
    text-decoration:underline;
}

</style>
</head>

<body>

<!-- ================= HEADER ================= -->
<div class="header">
    <h2>REPORT OF ADMISSIONS REF (YEAR-25 | B.TECH)</h2>
   <div class="controls">
    <input type="text" id="search" placeholder="Search staff name...">

    <a href="give_incentive.php" class="toggle" style="text-decoration:none;">
        💰 Give Incentive
    </a>

    <button class="toggle" onclick="toggleDark()">🌙</button>
</div>

</div>

<!-- ================= CARDS ================= -->
<?php
$year = 25;
$prog = 'B.TECH';
?>

<div class="cards">

<!-- ===== EXISTING 2025 PHASE CARDS ===== -->

<div class="card">
PH1
<span><a class="clickable" href="pr_details.php?phase=1&type=H&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['ph1'] ?></a></span>
<small>₹<?= number_format($cards['ph1_amt']) ?></small>
</div>

<div class="card">
PH2
<span><a class="clickable" href="pr_details.php?phase=2&type=H&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['ph2'] ?></a></span>
<small>₹<?= number_format($cards['ph2_amt']) ?></small>
</div>

<div class="card">
PH3
<span><a class="clickable" href="pr_details.php?phase=3&type=H&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['ph3'] ?></a></span>
<small>₹<?= number_format($cards['ph3_amt']) ?></small>
</div>

<div class="card">
SPOTH
<span><a class="clickable" href="pr_details.php?phase=SPOT&type=H&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['spoth'] ?></a></span>
<small>₹<?= number_format($cards['spoth_amt']) ?></small>
</div>

<div class="card">
BCATH
<span><a class="clickable" href="pr_details.php?phase=BCAT&type=H&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['bcath'] ?></a></span>
<small>₹<?= number_format($cards['bcath_amt']) ?></small>
</div>

<div class="card">
PD1
<span><a class="clickable" href="pr_details.php?phase=1&type=D&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['pd1'] ?></a></span>
<small>₹<?= number_format($cards['pd1_amt']) ?></small>
</div>

<div class="card">
PD2
<span><a class="clickable" href="pr_details.php?phase=2&type=D&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['pd2'] ?></a></span>
<small>₹<?= number_format($cards['pd2_amt']) ?></small>
</div>

<div class="card">
PD3
<span><a class="clickable" href="pr_details.php?phase=3&type=D&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['pd3'] ?></a></span>
<small>₹<?= number_format($cards['pd3_amt']) ?></small>
</div>

<div class="card">
SPOTD
<span><a class="clickable" href="pr_details.php?phase=SPOT&type=D&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['spotd'] ?></a></span>
<small>₹<?= number_format($cards['spotd_amt']) ?></small>
</div>

<div class="card">
BCATD
<span><a class="clickable" href="pr_details.php?phase=BCAT&type=D&year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['bcatd'] ?></a></span>
<small>₹<?= number_format($cards['bcatd_amt']) ?></small>
</div>

<div class="card">
TOTAL BTECH 2025
<span><a class="clickable" href="pr_details.php?year=<?= $year ?>&prog=<?= $prog ?>"><?= $cards['total'] ?></a></span>
</div>

<div class="card">
AMOUNT BTECH 2025
<span>₹<?= number_format($cards['total_amt']) ?></span>
</div>
<div class="card">
POLYCET 2025
<span>₹<?= number_format($cards['polycet_2025_amt']) ?></span>
</div>

<div class="card">
ECET 2025
<span>₹<?= number_format($cards['ecet_2025_amt']) ?></span>
</div>

<!-- ===== NEW 2024 + INCENTIVE CARDS ===== -->

<div class="card">
TOTAL BTECH 2024
<span>₹<?= number_format($cards['total_btech_2024']) ?></span>
</div>

<div class="card">
POLYCET 2024
<span>₹<?= number_format($cards['polycet_2024']) ?></span>
</div>

<div class="card">
ECET 2024
<span>₹<?= number_format($cards['ecet_2024']) ?></span>
</div>

<div class="card">
TOTAL 2024 ADMISSIONS
<span>₹<?= number_format($cards['total_2024_admissions']) ?></span>
</div>

<div class="card">
TOTAL INCENTIVE AMOUNT
<span>₹<?= number_format($cards['total_incentive_amount']) ?></span>
</div>

<div class="card">
GIVEN INCENTIVES
<span>₹<?= number_format($cards['given_incentives']) ?></span>
</div>

<div class="card">
PENDING INCENTIVE
<span>₹<?= number_format($cards['pending_incentive']) ?></span>
</div>

</div>



<!-- ================= TABLE ================= -->
<div class="table-wrap">
<table id="reportTable">

<tr>
    <th class="sticky-col" rowspan="2">REFEMPID</th>
    <th class="sticky-col-2" rowspan="2">NAME</th>

    <th class="group" colspan="5">HOSTEL</th>
    <th class="group" colspan="5">DAYSCHOLARS</th>

    <th rowspan="2">TOTAL BTECH 2025</th>
    <th rowspan="2">POLYCET 2025</th>
    <th rowspan="2">ECET 2025</th>
    <th rowspan="2">TOTAL 2025 ADMISSIONS</th>

    <th rowspan="2">TOTAL BTECH 2024</th>
    <th rowspan="2">POLYCET 2024</th>
    <th rowspan="2">ECET 2024</th>
    <th rowspan="2">TOTAL 2024 ADMISSIONS</th>

    <th rowspan="2">TOTAL INCENTIVE AMOUNT</th>
    <th rowspan="2">GIVEN INCENTIVES</th>
    <th rowspan="2">PENDING INCENTIVE</th>
</tr>

<tr>
    <th>PH1</th><th>PH2</th><th>PH3</th><th>SPOTH</th><th>BCATH</th>
    <th>PD1</th><th>PD2</th><th>PD3</th><th>SPOTD</th><th>BCATD</th>
</tr>

<?php
while($r = mysqli_fetch_assoc($result)){
    echo "<tr class='data-row'>";
    echo "<td class='sticky-col'>{$r['REFEMPID']}</td>";
    echo "<td class='sticky-col-2 name'>{$r['staff_name']}</td>";

    /* HOSTEL */
    echo "<td>{$r['ph1']}</td>";
    echo "<td>{$r['ph2']}</td>";
    echo "<td>{$r['ph3']}</td>";
    echo "<td>{$r['spoth']}</td>";
    echo "<td>{$r['bcath']}</td>";

    /* DAY SCHOLARS */
    echo "<td>{$r['pd1']}</td>";
    echo "<td>{$r['pd2']}</td>";
    echo "<td>{$r['pd3']}</td>";
    echo "<td>{$r['spotd']}</td>";
    echo "<td>{$r['bcatd']}</td>";

    /* TOTALS */
    echo "<td>{$r['total_count']}</td>";
    echo "<td>₹".number_format($r['polycet_2025_amt'])."</td>";
    echo "<td>₹".number_format($r['ecet_2025_amt'])."</td>";
    echo "<td>₹".number_format($r['total_2025_admissions'])."</td>";

    /* 2024 */
    echo "<td>₹".number_format($r['total_btech_2024'])."</td>";
    echo "<td>₹".number_format($r['polycet_2024_amt'])."</td>";
    echo "<td>₹".number_format($r['ecet_2024_amt'])."</td>";
    echo "<td>₹".number_format($r['total_2024_admissions'])."</td>";

    /* INCENTIVES */
    echo "<td>₹".number_format($r['total_incentive_amt'])."</td>";
    echo "<td>₹".number_format($r['given_incentives'])."</td>";
    echo "<td>₹".number_format($r['pending_incentive'])."</td>";
    echo "</tr>";

    /* ===== AMOUNT ROW ===== */
    echo "<tr class='amtrow'>";
    echo "<td colspan='2'>AMT</td>";

    echo "<td>{$r['ph1_amt']}</td>";
    echo "<td>{$r['ph2_amt']}</td>";
    echo "<td>{$r['ph3_amt']}</td>";
    echo "<td>{$r['spoth_amt']}</td>";
    echo "<td>{$r['bcath_amt']}</td>";

    echo "<td>{$r['pd1_amt']}</td>";
    echo "<td>{$r['pd2_amt']}</td>";
    echo "<td>{$r['pd3_amt']}</td>";
    echo "<td>{$r['spotd_amt']}</td>";
    echo "<td>{$r['bcatd_amt']}</td>";

    echo "<td>{$r['total_amt']}</td>";
    echo "<td colspan='2'></td>"; // aligns GIVEN & PENDING
    echo "</tr>";
}
?>
</table>
</div>

</div>


</table>
</div>

<script>
/* ================= DARK MODE ================= */
function toggleDark(){
    document.body.classList.toggle('dark');
    localStorage.setItem('dark', document.body.classList.contains('dark'));
}
if(localStorage.getItem('dark')==='true'){
    document.body.classList.add('dark');
}

/* ================= SEARCH ================= */
document.getElementById('search').addEventListener('keyup',function(){
    let v=this.value.toLowerCase();
    document.querySelectorAll('.data-row').forEach(row=>{
        let name=row.querySelector('.name').innerText.toLowerCase();
        row.style.display=name.includes(v)?'':'none';
        if(row.nextElementSibling) row.nextElementSibling.style.display=row.style.display;
    });
});
</script>

</body>
</html>
