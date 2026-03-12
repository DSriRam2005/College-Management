<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

$message = "";
$updated = 0;
$notfound = 0;
$notfound_list = [];

if (isset($_POST['upload'])) {

    if ($_FILES['csv_file']['error'] == 0) {

        $file = fopen($_FILES['csv_file']['tmp_name'], "r");

        // skip header
        fgetcsv($file);

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

            $htno    = strtoupper(trim($data[0] ?? ''));
            $village = trim($data[1] ?? '');
            $mandal  = trim($data[2] ?? '');
            $dist    = trim($data[3] ?? '');

            if ($htno == "") continue;

            // check student exists
            $check = $conn->prepare("SELECT id FROM STUDENTS WHERE htno=?");
            $check->bind_param("s", $htno);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows == 0) {
                $notfound++;
                $notfound_list[] = $htno;
                continue;
            }

            // update village mandal dist
            $update = $conn->prepare("
                UPDATE STUDENTS 
                SET village=?, mandal=?, dist=? 
                WHERE htno=?
            ");

            $update->bind_param("ssss", $village, $mandal, $dist, $htno);
            $update->execute();

            $updated++;
        }

        fclose($file);

        $message = "Updated: $updated | Not Found: $notfound";

    } else {
        $message = "File upload error.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Village Mandal District</title>

<style>
body { font-family: Arial; background:#f4f6f9; padding:40px; }
.box { max-width:650px; margin:auto; background:white; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
input { width:100%; padding:10px; margin:10px 0; }
button { padding:10px; width:100%; background:#007bff; color:white; border:none; cursor:pointer; }
.msg { margin-top:20px; font-weight:bold; }
.notfound { margin-top:15px; background:#ffeaea; padding:10px; border-radius:5px; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
th, td { border:1px solid #ddd; padding:8px; }
th { background:#007bff; color:white; }
</style>

</head>
<body>

<div class="box">

<h3>Upload CSV to Update Village / Mandal / District</h3>

<form method="POST" enctype="multipart/form-data">
<input type="file" name="csv_file" accept=".csv" required>
<button type="submit" name="upload">Upload & Update</button>
</form>

<?php if ($message != "") echo "<div class='msg'>$message</div>"; ?>

<?php if (!empty($notfound_list)) { ?>
<div class="notfound">
<strong>HTNO Not Found:</strong><br>
<?php echo implode(", ", $notfound_list); ?>
</div>
<?php } ?>

<div style="margin-top:25px;background:#f8f9fa;padding:15px;border-radius:6px;">
<strong>CSV Sample Format</strong>

<table>
<tr>
<th>htno</th>
<th>village</th>
<th>mandal</th>
<th>dist</th>
</tr>

<tr>
<td>21A91A0501</td>
<td>Kakinada Rural</td>
<td>Kakinada East</td>
<td>Kakinada</td>
</tr>

<tr>
<td>21A91A0502</td>
<td>Tuni</td>
<td>Tuni</td>
<td>Anakapalli</td>
</tr>

</table>

</div>

</div>

</body>
</html>