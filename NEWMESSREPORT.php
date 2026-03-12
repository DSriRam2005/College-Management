<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- DB1 Connection ----------
$db1 = new mysqli("sql211.infinityfree.com", "if0_39689452", "0JaTuFZVF3U0L", "if0_39689452_09_10");
if ($db1->connect_error) die("DB1 Connection failed: " . $db1->connect_error);
$db1->set_charset("utf8mb4");

// ---------- DB2 Connection ----------
$db2 = new mysqli("sql313.infinityfree.com", "if0_39723681", "0ckajZsehKrdkW8", "if0_39723681_btech2025");
if ($db2->connect_error) die("DB2 Connection failed: " . $db2->connect_error);
$db2->set_charset("utf8mb4");

$data = [];

// ---------- Fetch Mess Fee from DB1 ----------
$sql1 = "
    SELECT s.classid AS classname,
           SUM(COALESCE(m.oct_ttamt,0)) AS total_fee,
           SUM(COALESCE(m.oct_due,0)) AS present_due
    FROM messfee m
    INNER JOIN STUDENTS s ON s.htno = m.htno
    GROUP BY s.classid
";
$result1 = $db1->query($sql1);

if ($result1 && $result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $class = trim((string)$row['classname']);
        if ($class === '') continue;
        $data[$class] = [
            'total_fee' => (float)$row['total_fee'],
            'present_due' => (float)$row['present_due']
        ];
    }
}

// ---------- Fetch Mess Fee from DB2 ----------
$sql2 = "
    SELECT s.classname,
           SUM(COALESCE(m.oct25,0)) AS total_fee,
           SUM(COALESCE(m.oct25_due,0)) AS present_due
    FROM messfee m
    INNER JOIN students s ON s.HT_NO = m.htno
    GROUP BY s.classname
";
$result2 = $db2->query($sql2);

if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $class = trim((string)$row['classname']);
        if ($class === '') continue;
        if (!isset($data[$class])) {
            $data[$class] = ['total_fee' => 0, 'present_due' => 0];
        }
        $data[$class]['total_fee'] += (float)$row['total_fee'];
        $data[$class]['present_due'] += (float)$row['present_due'];
    }
}

// ---------- Calculate Paid + Percent ----------
$finalData = [];
$grand_total = 0;
$grand_present = 0;
$grand_paid = 0;

foreach ($data as $class => $info) {
    $total = $info['total_fee'];
    $present = $info['present_due'];
    if ($total <= 0 && $present <= 0) continue;

    $paid = $total - $present;
    $percent = ($total > 0) ? ($paid / $total * 100) : 0;

    $finalData[] = [
        'classname' => $class,
        'total_fee' => $total,
        'present_due' => $present,
        'paid' => $paid,
        'percent' => $percent
    ];

    $grand_total += $total;
    $grand_present += $present;
    $grand_paid += $paid;
}

$grand_percent = ($grand_total > 0) ? ($grand_paid / $grand_total * 100) : 0;

// ---------- Sort by Paid % (High → Low) ----------
usort($finalData, function($a, $b) {
    return $b['percent'] <=> $a['percent'];
});

// ---------- Close DBs ----------
$db1->close();
$db2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>October Mess Fee Combined Report</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 30px;
    color: #1a1a1a;
}
h2 {
    text-align: center;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 35px;
    letter-spacing: 0.5px;
}
.cards {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}
.card {
    flex: 1 1 230px;
    background: linear-gradient(145deg, #ffffff, #f3f4f6);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    text-align: center;
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}
.card h3 {
    margin: 0;
    font-size: 15px;
    color: #6b7280;
    font-weight: 500;
}
.card p {
    margin: 12px 0 0;
    font-size: 22px;
    font-weight: 700;
}
.card:nth-child(1) p { color: #2563eb; }
.card:nth-child(2) p { color: #f59e0b; }
.card:nth-child(3) p { color: #16a34a; }
.card:nth-child(4) p { color: #9333ea; }

/* -------- TABLE -------- */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}
thead th {
    background: linear-gradient(135deg, #1e3a8a, #1e293b);
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 14px;
    padding: 14px 18px;
    letter-spacing: 0.4px;
}
tbody td {
    padding: 13px 18px;
    font-size: 15px;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.25s ease;
}
tbody tr:hover td {
    background: #f9fafb;
}
/* Color-coded rows */
.paid-high td {
    background-color: #ecfdf5;
    color: #065f46;
}
.paid-mid td {
    background-color: #fffbeb;
    color: #92400e;
}
.paid-low td {
    background-color: #fef2f2;
    color: #991b1b;
}
/* Badge for Paid % */
td:last-child {
    font-weight: 600;
    text-align: center;
}
td:last-child::before {
    content: "● ";
    font-size: 10px;
    vertical-align: middle;
}
.paid-high td:last-child::before {
    color: #16a34a;
}
.paid-mid td:last-child::before {
    color: #f59e0b;
}
.paid-low td:last-child::before {
    color: #dc2626;
}
tfoot td {
    font-weight: 600;
    background: #f1f5f9;
    color: #1f2937;
    padding: 14px 18px;
}
/* -------- RESPONSIVE -------- */
@media (max-width: 768px) {
    body { padding: 15px; }
    table, th, td {
        font-size: 13px;
        padding: 10px;
    }
    .cards {
        flex-direction: column;
        align-items: center;
    }
}
</style>
</head>
<body>

<h2>🍽️ October Mess Fee Combined Report (Highest → Lowest Paid %)</h2>

<div class="cards">
    <div class="card">
        <h3>Total Fee</h3>
        <p>₹<?= number_format($grand_total, 2) ?></p>
    </div>
    <div class="card">
        <h3>Present Due</h3>
        <p>₹<?= number_format($grand_present, 2) ?></p>
    </div>
    <div class="card">
        <h3>Paid</h3>
        <p>₹<?= number_format($grand_paid, 2) ?></p>
    </div>
    <div class="card">
        <h3>Paid %</h3>
        <p><?= number_format($grand_percent, 2) ?>%</p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Class ID / Class Name</th>
            <th>Total Fee</th>
            <th>Present Due</th>
            <th>Paid</th>
            <th>Paid %</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (empty($finalData)) {
            echo "<tr><td colspan='5' style='text-align:center;'>No data found</td></tr>";
        } else {
            foreach ($finalData as $row) {
                $percent = $row['percent'];
                $color = "paid-low";
                if ($percent >= 75) $color = "paid-high";
                elseif ($percent >= 50) $color = "paid-mid";

                echo "<tr class='$color'>
                        <td>{$row['classname']}</td>
                        <td>₹" . number_format($row['total_fee'], 2) . "</td>
                        <td>₹" . number_format($row['present_due'], 2) . "</td>
                        <td>₹" . number_format($row['paid'], 2) . "</td>
                        <td>" . number_format($row['percent'], 2) . "%</td>
                      </tr>";
            }
        }
        ?>
    </tbody>
</table>

</body>
</html>
