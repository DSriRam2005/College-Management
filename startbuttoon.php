<?php
// start_stop.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php'; // Your DB connection

$message = "";

if (isset($_POST['start'])) {
    $sql = "UPDATE `start` SET start_stop = 1";
    if ($conn->query($sql) === TRUE) {
        $message = "All rows updated: start_stop = 1 (Started)";
    } else {
        $message = "Error: " . $conn->error;
    }
}

if (isset($_POST['stop'])) {
    $sql = "UPDATE `start` SET start_stop = 0";
    if ($conn->query($sql) === TRUE) {
        $message = "All rows updated: start_stop = 0 (Stopped)";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch current status for display
$result = $conn->query("SELECT categoryname, start_stop FROM `start`");
$statuses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $statuses[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start / Stop Control</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        button { padding: 15px 30px; font-size: 18px; margin: 10px; cursor: pointer; }
        .start { background-color: #4CAF50; color: white; border: none; }
        .stop { background-color: #f44336; color: white; border: none; }
        .message { margin-top: 20px; font-weight: bold; }
        table { margin: 20px auto; border-collapse: collapse; width: 50%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Start / Stop Control</h1>
    <form method="post">
        <button type="submit" name="start" class="start">Start</button>
        <button type="submit" name="stop" class="stop">Stop</button>
    </form>

    <?php if($message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <h2>Current Status</h2>
    <table>
        <tr>
            <th>Category Name</th>
            <th>Status</th>
        </tr>
        <?php foreach($statuses as $status): ?>
            <tr>
                <td><?php echo htmlspecialchars($status['categoryname']); ?></td>
                <td><?php echo $status['start_stop'] ? 'Started' : 'Stopped'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
