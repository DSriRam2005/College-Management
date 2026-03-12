<?php
session_start();
include 'db.php';

/* ================= ACCESS ================= */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN','PR'])) {
    die("ACCESS DENIED");
}

$msg = "";

/* ================= SAVE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $empid  = intval($_POST['empid'] ?? 0);
    $amount = floatval($_POST['given_incentives'] ?? 0);

    if ($empid <= 0) {
        $msg = "Please select staff from suggestions";
    } elseif ($amount <= 0) {
        $msg = "Invalid amount";
    } else {

        /* ALWAYS INSERT – MULTIPLE ENTRIES ALLOWED */
        $sql = "
            INSERT INTO admission_incentives_given (empid, given_incentives)
            VALUES ($empid, $amount)
        ";

        if (mysqli_query($conn, $sql)) {
            $msg = "Incentive added successfully";
        } else {
            $msg = "Error: " . mysqli_error($conn);
        }
    }
}

/* ================= AJAX SEARCH ================= */
if (isset($_GET['ajax']) && isset($_GET['q'])) {

    $q = mysqli_real_escape_string($conn, $_GET['q']);

    $res = mysqli_query($conn,"
        SELECT EMPID, NAME
        FROM kiet_staff
        WHERE NAME LIKE '%$q%'
        ORDER BY NAME
        LIMIT 10
    ");

    $data = [];
    while($r = mysqli_fetch_assoc($res)){
        $data[] = $r;
    }

    echo json_encode($data);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Give Incentive</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body{
    font-family:Segoe UI,Arial;
    background:#f4f6fb;
}
.box{
    max-width:420px;
    margin:40px auto;
    background:#fff;
    padding:20px;
    border-radius:10px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
}
label{font-weight:600}
input,button{
    width:100%;
    padding:10px;
    margin-top:6px;
    margin-bottom:14px;
}
button{
    background:#16a34a;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.msg{
    font-weight:600;
    margin-bottom:10px;
    color:green;
}

#suggestions{
    border:1px solid #ccc;
    border-radius:6px;
    display:none;
    max-height:180px;
    overflow:auto;
}
#suggestions div{
    padding:8px;
    cursor:pointer;
}
#suggestions div:hover{
    background:#e5e7eb;
}
</style>
</head>

<body>

<div class="box">
<h2>Give Incentive Amount</h2>

<?php if($msg) echo "<div class='msg'>$msg</div>"; ?>

<form method="post">

<label>Staff Name</label>
<input type="text" id="staff_name" placeholder="Type staff name..." autocomplete="off" required>
<input type="hidden" name="empid" id="empid">

<div id="suggestions"></div>

<label>Given Incentive Amount (₹)</label>
<input type="number" step="0.01" name="given_incentives" required>

<button type="submit">Add Incentive</button>

</form>
</div>

<script>
const staffInput = document.getElementById('staff_name');
const empidInput = document.getElementById('empid');
const box = document.getElementById('suggestions');

staffInput.addEventListener('keyup', () => {
    let q = staffInput.value.trim();
    empidInput.value = '';

    if(q.length < 2){
        box.style.display = 'none';
        return;
    }

    fetch(`give_incentive.php?ajax=1&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            box.innerHTML = '';
            data.forEach(s => {
                let d = document.createElement('div');
                d.textContent = `${s.NAME} (${s.EMPID})`;
                d.onclick = () => {
                    staffInput.value = s.NAME;
                    empidInput.value = s.EMPID;
                    box.style.display = 'none';
                };
                box.appendChild(d);
            });
            box.style.display = data.length ? 'block' : 'none';
        });
});
</script>

</body>
</html>
