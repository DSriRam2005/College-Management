<?php
include 'db.php';

// ===== AJAX Handler =====
if(isset($_GET['ajax']) && $_GET['ajax']==1){
    $col = $_GET['column'];
    $val = $_GET['value'];

    if($val === 'all'){
        $sql = "SELECT htno, $col FROM 3FEEDBACK WHERE $col IS NOT NULL AND TRIM($col) != ''";
    } else {
        $sql = "SELECT htno, $col FROM 3FEEDBACK WHERE $col = '$val'";
    }

    $res = $conn->query($sql);
    echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%;'>";
    echo "<tr><th>HT No</th><th>".htmlspecialchars($col)."</th></tr>";
    while($row = $res->fetch_assoc()){
        echo "<tr><td>".$row['htno']."</td><td>".nl2br(htmlspecialchars($row[$col]))."</td></tr>";
    }
    echo "</table>";
    exit;
}

// ===== Helper functions =====
function getCounts($conn, $column) {
    $sql = "SELECT $column as val, COUNT(*) as cnt FROM 3FEEDBACK GROUP BY $column";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()){
        $data[$row['val']] = $row['cnt'];
    }
    return $data;
}

function getAverage($conn, $column){
    $sql = "SELECT AVG($column) as avg_val FROM 3FEEDBACK";
    $res = $conn->query($sql)->fetch_assoc();
    return is_null($res['avg_val']) ? '-' : round($res['avg_val'],2);
}

$totalFeedbacks = $conn->query("SELECT COUNT(*) as c FROM 3FEEDBACK")->fetch_assoc()['c'];

// ===== Categories mapped to your questions =====
$numericRatings = [
    "usefulness" => "Usefulness of Hackathon (1=Not useful,5=Extremely useful)",
    "trainers_knowledge" => "Trainers’ Knowledge & Expertise",
    "trainers_explain" => "Trainers’ Explanation of Problems & Solutions",
    "trainers_helpful" => "Trainers’ Helpfulness & Approachability",
    "overall_exp" => "Overall Experience of Hackathon",
    "format_engage" => "Engagement of Hackathon Format"
];

$yesSomeNo = [
    "speed" => "Did hackathon improve problem-solving speed?",
    "confidence" => "Did hackathon improve coding confidence?",
    "guidance" => "Did trainers’ guidance improve understanding of CodeVita-style problems?",
    "more_hackathons" => "Would you like more practice hackathons before CodeVita?"
];

$multipleChoice = [
    "difficulty" => "Were the problems at the right level of difficulty? (Too Easy / Balanced / Too Difficult)"
];

$openText = [
    "skills" => "What new concepts/skills did you learn?",
    "strategies" => "Did you learn any new strategies for CodeVita?",
    "trainer_like" => "What did you like most about the trainers?",
    "trainer_improve" => "What could trainers improve for future sessions?",
    "like_most" => "What did you like most about the hackathon?",
    "improve_next" => "What could be improved for next time?",
    "suggestions" => "Any specific topics/problems you want in the next hackathon?"
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Hackathon Feedback Summary</title>
<style>
body { font-family: Arial, sans-serif; margin:20px; background:#f8fafc; color:#333; }
table { border-collapse: collapse; width: 100%; max-width: 1200px; margin: 20px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
th, td { padding:12px; text-align:center; border-bottom:1px solid #e5e7eb; }
th { background:#1f2937; color:#fff; font-weight:600; }
tr:nth-child(even) { background:#f9fafb; }
tr:hover { background:#e0f2fe; }
.clickable { cursor:pointer; color:#0d6efd; text-decoration:underline; }

/* Modal */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background: rgba(0,0,0,0.5);}
.modal-content { background:#fff; margin:5% auto; padding:20px; border-radius:8px; max-width:800px; max-height:80%; overflow:auto; }
.close { float:right; font-size:24px; cursor:pointer; }
</style>
</head>
<body>

<h2 style="text-align:center;">Hackathon Feedback Summary</h2>
<table>
<tr>
    <th>Category / Question</th>
    <th>Total Feedbacks</th>
    <th>Average / Count</th>
    <th>5★ / Yes</th>
    <th>4★ / Somewhat</th>
    <th>3★ / No</th>
    <th>2★</th>
    <th>1★</th>
</tr>

<!-- Numeric Ratings -->
<?php foreach($numericRatings as $col => $label):
    $counts = getCounts($conn,$col);
    $avg = getAverage($conn,$col);
    $total = array_sum($counts);
    $c5 = $counts[5] ?? 0;
    $c4 = $counts[4] ?? 0;
    $c3 = $counts[3] ?? 0;
    $c2 = $counts[2] ?? 0;
    $c1 = $counts[1] ?? 0;
?>
<tr>
<td><?php echo $label;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','all')"><?php echo $total;?></span></td>
<td><?php echo $avg;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>',5)"><?php echo $c5;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>',4)"><?php echo $c4;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>',3)"><?php echo $c3;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>',2)"><?php echo $c2;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>',1)"><?php echo $c1;?></span></td>
</tr>
<?php endforeach; ?>

<!-- Yes / Somewhat / No -->
<?php foreach($yesSomeNo as $col=>$label):
    $counts = getCounts($conn,$col);
    $y = $counts['Yes'] ?? 0;
    $s = $counts['Somewhat'] ?? 0;
    $n = $counts['No'] ?? 0;
?>
<tr>
<td><?php echo $label;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','all')"><?php echo $totalFeedbacks;?></span></td>
<td><?php echo $y+$s+$n;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','Yes')"><?php echo $y;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','Somewhat')"><?php echo $s;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','No')"><?php echo $n;?></span></td>
<td colspan="2">-</td>
</tr>
<?php endforeach; ?>

<!-- Multiple Choice -->
<?php foreach($multipleChoice as $col=>$label):
    $counts = getCounts($conn,$col);
    $easy = $counts['Too Easy'] ?? 0;
    $balanced = $counts['Balanced'] ?? 0;
    $hard = $counts['Too Difficult'] ?? 0;
?>
<tr>
<td><?php echo $label;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','all')"><?php echo $totalFeedbacks;?></span></td>
<td><?php echo $easy+$balanced+$hard;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','Too Easy')"><?php echo $easy;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','Balanced')"><?php echo $balanced;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','Too Difficult')"><?php echo $hard;?></span></td>
<td colspan="2">-</td>
</tr>
<?php endforeach; ?>

<!-- Open Text -->
<?php foreach($openText as $col=>$label):
    $res = $conn->query("SELECT COUNT(*) as c FROM 3FEEDBACK WHERE $col IS NOT NULL AND TRIM($col) != ''");
    $count = $res->fetch_assoc()['c'];
?>
<tr>
<td><?php echo $label;?></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','all')"><?php echo $totalFeedbacks;?></span></td>
<td><span class="clickable" onclick="showList('<?php echo $col;?>','all')"><?php echo $count;?></span></td>
<td colspan="5">-</td>
</tr>
<?php endforeach; ?>
</table>

<!-- Modal -->
<div id="modal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <div id="modal-body">Loading...</div>
  </div>
</div>

<script>
function showList(column,value){
    const modal = document.getElementById('modal');
    const body = document.getElementById('modal-body');
    modal.style.display='block';
    body.innerHTML='Loading...';

    fetch(`?ajax=1&column=${column}&value=${value}`)
    .then(res=>res.text())
    .then(data=>body.innerHTML=data);
}

function closeModal(){
    document.getElementById('modal').style.display='none';
}

window.onclick = function(event){
    const modal = document.getElementById('modal');
    if(event.target==modal){ modal.style.display='none'; }
}
</script>
</body>
</html>
