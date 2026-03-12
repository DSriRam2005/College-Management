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
    /* COUNTS */
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

    /* AMOUNTS */
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
    ) AS total_amt
FROM STUDENTS
WHERE year = 25
AND prog = 'B.TECH'
AND REFEMPID IS NOT NULL
";

$card_res = mysqli_query($conn, $card_sql);
if (!$card_res) { die(mysqli_error($conn)); }
$cards = mysqli_fetch_assoc($card_res);

/* ================== MAIN REPORT ================== */
$sql = "
SELECT 
    s.REFEMPID,
    k.NAME AS staff_name,

    /* COUNTS */
    SUM(s.PHASE='1' AND s.CLG_TYPE='H')    AS ph1,
    SUM(s.PHASE='2' AND s.CLG_TYPE='H')    AS ph2,
    SUM(s.PHASE='3' AND s.CLG_TYPE='H')    AS ph3,
    SUM(s.PHASE='SPOT' AND s.CLG_TYPE='H') AS spoth,
    SUM(s.PHASE='BCAT' AND s.CLG_TYPE='H') AS bcath,

    SUM(s.PHASE='1' AND s.CLG_TYPE='D')    AS pd1,
    SUM(s.PHASE='2' AND s.CLG_TYPE='D')    AS pd2,
    SUM(s.PHASE='3' AND s.CLG_TYPE='D')    AS pd3,
    SUM(s.PHASE='SPOT' AND s.CLG_TYPE='D') AS spotd,
    SUM(s.PHASE='BCAT' AND s.CLG_TYPE='D') AS bcatd,

    COUNT(*) AS total_count,

    /* AMOUNTS */
    (SUM(s.PHASE='1' AND s.CLG_TYPE='H') * 6000)   AS ph1_amt,
    (SUM(s.PHASE='2' AND s.CLG_TYPE='H') * 15000)  AS ph2_amt,
    (SUM(s.PHASE='3' AND s.CLG_TYPE='H') * 15000)  AS ph3_amt,
    (SUM(s.PHASE='SPOT' AND s.CLG_TYPE='H') * 15000) AS spoth_amt,
    (SUM(s.PHASE='BCAT' AND s.CLG_TYPE='H') * 30000) AS bcath_amt,

    (SUM(s.PHASE='1' AND s.CLG_TYPE='D') * 4000)   AS pd1_amt,
    (SUM(s.PHASE='2' AND s.CLG_TYPE='D') * 10000)  AS pd2_amt,
    (SUM(s.PHASE='3' AND s.CLG_TYPE='D') * 10000)  AS pd3_amt,
    (SUM(s.PHASE='SPOT' AND s.CLG_TYPE='D') * 10000) AS spotd_amt,
    (SUM(s.PHASE='BCAT' AND s.CLG_TYPE='D') * 30000) AS bcatd_amt,

    (
      (SUM(s.PHASE='1' AND s.CLG_TYPE='H') * 6000) +
      (SUM(s.PHASE='2' AND s.CLG_TYPE='H') * 15000) +
      (SUM(s.PHASE='3' AND s.CLG_TYPE='H') * 15000) +
      (SUM(s.PHASE='SPOT' AND s.CLG_TYPE='H') * 15000) +
      (SUM(s.PHASE='BCAT' AND s.CLG_TYPE='H') * 30000) +

      (SUM(s.PHASE='1' AND s.CLG_TYPE='D') * 4000) +
      (SUM(s.PHASE='2' AND s.CLG_TYPE='D') * 10000) +
      (SUM(s.PHASE='3' AND s.CLG_TYPE='D') * 10000) +
      (SUM(s.PHASE='SPOT' AND s.CLG_TYPE='D') * 10000) +
      (SUM(s.PHASE='BCAT' AND s.CLG_TYPE='D') * 30000)
    ) AS total_amt

FROM STUDENTS s
LEFT JOIN kiet_staff k ON s.REFEMPID = k.EMPID
WHERE s.year = 25
AND s.prog = 'B.TECH'
AND s.REFEMPID IS NOT NULL
GROUP BY s.REFEMPID, k.NAME
ORDER BY total_amt DESC
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
        <button class="toggle" onclick="toggleDark()">🌙</button>
    </div>
</div>

<!-- ================= CARDS ================= -->
<?php
$year = 25;
$prog = 'B.TECH';
?>

<div class="cards">

<div class="card">
PH1
<span>
<a class="clickable" href="pr_details.php?phase=1&type=H&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['ph1'] ?>
</a>
</span>
<small>₹<?= number_format($cards['ph1_amt']) ?></small>
</div>

<div class="card">
PH2
<span>
<a class="clickable" href="pr_details.php?phase=2&type=H&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['ph2'] ?>
</a>
</span>
<small>₹<?= number_format($cards['ph2_amt']) ?></small>
</div>

<div class="card">
PH3
<span>
<a class="clickable" href="pr_details.php?phase=3&type=H&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['ph3'] ?>
</a>
</span>
<small>₹<?= number_format($cards['ph3_amt']) ?></small>
</div>

<div class="card">
SPOTH
<span>
<a class="clickable" href="pr_details.php?phase=SPOT&type=H&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['spoth'] ?>
</a>
</span>
<small>₹<?= number_format($cards['spoth_amt']) ?></small>
</div>

<div class="card">
BCATH
<span>
<a class="clickable" href="pr_details.php?phase=BCAT&type=H&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['bcath'] ?>
</a>
</span>
<small>₹<?= number_format($cards['bcath_amt']) ?></small>
</div>

<div class="card">
PD1
<span>
<a class="clickable" href="pr_details.php?phase=1&type=D&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['pd1'] ?>
</a>
</span>
<small>₹<?= number_format($cards['pd1_amt']) ?></small>
</div>

<div class="card">
PD2
<span>
<a class="clickable" href="pr_details.php?phase=2&type=D&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['pd2'] ?>
</a>
</span>
<small>₹<?= number_format($cards['pd2_amt']) ?></small>
</div>

<div class="card">
PD3
<span>
<a class="clickable" href="pr_details.php?phase=3&type=D&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['pd3'] ?>
</a>
</span>
<small>₹<?= number_format($cards['pd3_amt']) ?></small>
</div>
<div class="card">
SPOTD
<span>
<a class="clickable" href="pr_details.php?phase=SPOT&type=D&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['spotd'] ?>
</a>
</span>
<small>₹<?= number_format($cards['spotd_amt']) ?></small>
</div>

<div class="card">
BCATD
<span>
<a class="clickable" href="pr_details.php?phase=BCAT&type=D&year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['bcatd'] ?>
</a>
</span>
<small>₹<?= number_format($cards['bcatd_amt']) ?></small>
</div>

<div class="card">
TOTAL
<span>
<a class="clickable" href="pr_details.php?year=<?= $year ?>&prog=<?= $prog ?>">
<?= $cards['total'] ?>
</a>
</span>
</div>

<div class="card">
AMOUNT
<span>₹<?= number_format($cards['total_amt']) ?></span>
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
    <th rowspan="2">TOTAL</th>
</tr>
<tr>
    <th>PH1</th><th>PH2</th><th>PH3</th><th>SPOTH</th><th>BCATH</th>
    <th>PD1</th><th>PD2</th><th>PD3</th><th>SPOTD</th><th>BCATD</th>
</tr>

<?php
$year = 25;
$prog = 'B.TECH';

while($r = mysqli_fetch_assoc($result)){
    $ref = $r['REFEMPID'];

    echo "<tr class='data-row'>";
    echo "<td class='sticky-col'>{$ref}</td>";
    echo "<td class='sticky-col-2 name'>{$r['staff_name']}</td>";

    // HOSTEL
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=1&type=H&year=$year&prog=$prog'>{$r['ph1']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=2&type=H&year=$year&prog=$prog'>{$r['ph2']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=3&type=H&year=$year&prog=$prog'>{$r['ph3']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=SPOT&type=H&year=$year&prog=$prog'>{$r['spoth']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=BCAT&type=H&year=$year&prog=$prog'>{$r['bcath']}</a></td>";

    // DAYSCHOLARS
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=1&type=D&year=$year&prog=$prog'>{$r['pd1']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=2&type=D&year=$year&prog=$prog'>{$r['pd2']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=3&type=D&year=$year&prog=$prog'>{$r['pd3']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=SPOT&type=D&year=$year&prog=$prog'>{$r['spotd']}</a></td>";
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&phase=BCAT&type=D&year=$year&prog=$prog'>{$r['bcatd']}</a></td>";

    // TOTAL COUNT
    echo "<td><a class='clickable' href='pr_details.php?ref=$ref&year=$year&prog=$prog'>{$r['total_count']}</a></td>";
    echo "</tr>";

    // AMOUNT ROW (also clickable if you want)
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
    echo "</tr>";
}
?>

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
