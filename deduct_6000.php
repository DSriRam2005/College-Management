<?php
/* =============================
   STUDENT FEE DEDUCTION PAGE
   Reduce 6000 from tfdue fields
   ============================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ===== MYSQLI ERROR MODE ===== */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


include "db.php"; // your DB connection file

$message = "";

if(isset($_POST['update'])) {

    $raw = trim($_POST['htnos']);

    if($raw != "") {

        // Split by newline or comma
        $lines = preg_split('/[\r\n,]+/', $raw);

        // Clean values
        $htnos = [];
        foreach($lines as $h){
            $h = trim($h);
            if($h != "") $htnos[] = $h;
        }

        if(count($htnos) > 0){

            // Create placeholders (?, ?, ?)
            $placeholders = implode(',', array_fill(0, count($htnos), '?'));

            $sql = "
                UPDATE STUDENTS
                SET
                    tfdue_12_9 = GREATEST(IFNULL(tfdue_12_9,0) - 6000, 0),
                    tfdue_today = GREATEST(IFNULL(tfdue_today,0) - 6000, 0)
                WHERE htno IN ($placeholders)
            ";

            $stmt = $conn->prepare($sql);

            // bind params dynamically
            $types = str_repeat('s', count($htnos));
            $stmt->bind_param($types, ...$htnos);

            $stmt->execute();

            $affected = $stmt->affected_rows;

            $message = "✅ Updated $affected students successfully.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Deduct 6000 - Student Fees</title>

    <style>
        body{
            font-family: Arial;
            background:#f4f6f9;
            padding:40px;
        }
        .box{
            max-width:800px;
            margin:auto;
            background:white;
            padding:30px;
            border-radius:10px;
            box-shadow:0 0 10px #ccc;
        }
        textarea{
            width:100%;
            height:350px;
            padding:10px;
            font-family:monospace;
        }
        button{
            background:#1f2f86;
            color:white;
            padding:12px 25px;
            border:none;
            border-radius:6px;
            font-size:16px;
            cursor:pointer;
        }
        .msg{
            margin-top:15px;
            font-weight:bold;
            color:green;
        }
    </style>
</head>

<body>

<div class="box">

    <h2>₹6000 Fee Deduction Tool</h2>

    <p>Paste HTNO list (one per line or comma separated)</p>

    <form method="post">

        <textarea name="htnos" placeholder="22B21A4561
22B21A4597
22B21A45A8"></textarea>

        <br><br>

        <button name="update">Deduct ₹6000</button>

    </form>

    <?php if($message){ ?>
        <div class="msg"><?php echo $message; ?></div>
    <?php } ?>

</div>

</body>
</html>
