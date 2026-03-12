<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "db.php";

$message = "";
$reasons = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {

    $file = $_FILES["csv_file"]["tmp_name"];

    if (($handle = fopen($file, "r")) !== FALSE) {

        // Skip header row
        fgetcsv($handle);

        $updated = 0;
        $notUpdated = 0;

        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {

            // Correct function name here
            $htno = mysqli_real_escape_string($conn, trim($data[0]));

            $tfdue_12_9   = $data[1];
            $tfdue_today  = $data[2];
            $otdues_12_9  = $data[3];
            $otdues_today = $data[4];
            $busdue_12_9  = $data[5];
            $busdue_today = $data[6];
            $hosdue_12_9  = $data[7];
            $hosdue_today = $data[8];
            $olddue_12_9  = $data[9];
            $olddue_today = $data[10];

            // 1. Check HTNO exists
            $check = mysqli_query($conn, "SELECT id FROM STUDENTS WHERE htno='$htno'");
            if (mysqli_num_rows($check) == 0) {
                $notUpdated++;
                $reasons[] = "HTNO $htno → Not Found in database";
                continue;
            }

            // 2. Update query
            $sql = "
                UPDATE STUDENTS SET
                    tfdue_12_9   = '$tfdue_12_9',
                    tfdue_today  = '$tfdue_today',
                    otdues_12_9  = '$otdues_12_9',
                    otdues_today = '$otdues_today',
                    busdue_12_9  = '$busdue_12_9',
                    busdue_today = '$busdue_today',
                    hosdue_12_9  = '$hosdue_12_9',
                    hosdue_today = '$hosdue_today',
                    olddue_12_9  = '$olddue_12_9',
                    olddue_today = '$olddue_today'
                WHERE htno = '$htno'
            ";

            if (mysqli_query($conn, $sql)) {
                if (mysqli_affected_rows($conn) > 0) {
                    $updated++;
                } else {
                    $notUpdated++;
                    $reasons[] = "HTNO $htno → Data already same, no change needed";
                }
            } else {
                $notUpdated++;
                $reasons[] = "HTNO $htno → SQL Error: " . mysqli_error($conn);
            }
        }

        fclose($handle);

        $message = "Updated: $updated | Not Updated: $notUpdated";

    } else {
        $message = "Error opening CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload & Update Fees</title>
    <style>
        body{font-family:Arial;background:#f4f4f4;padding:20px;}
        .box{background:#fff;padding:20px;border-radius:6px;width:600px;margin:auto;}
        h2{text-align:center;}
        ul{max-height:300px;overflow:auto;}
        button{padding:8px 14px;border:none;background:#2563eb;color:#fff;border-radius:5px;cursor:pointer;}
    </style>
</head>
<body>

<div class="box">
    <h2>Upload Fees CSV</h2>

    <?php if($message!=""){ ?>
        <h3><?php echo $message; ?></h3>
    <?php } ?>

    <?php if(!empty($reasons)){ ?>
        <h3>Reasons for Not Updating:</h3>
        <ul style="color:red;">
            <?php foreach($reasons as $r){ ?>
                <li><?php echo htmlspecialchars($r); ?></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <button type="submit">Upload & Update</button>
    </form>

    <br>

    <!-- Sample CSV Download -->
    <button onclick="downloadSample()">Download Sample CSV</button>
</div>

<script>
function downloadSample() {
    const sample =
`htno,tfdue_12_9,tfdue_today,otdues_12_9,otdues_today,busdue_12_9,busdue_today,hosdue_12_9,hosdue_today,olddue_12_9,olddue_today
22A91A0501,1000,500,200,100,300,150,0,0,50,25
22A91A0502,0,0,0,0,0,0,0,0,0,0
22A91A0503,1500,800,300,150,400,200,100,100,0,0`;

    const blob = new Blob([sample], { type: "text/csv" });
    const url = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = "sample.csv";
    a.click();

    URL.revokeObjectURL(url);
}
</script>

</body>
</html>
