<?php
session_start();
if (!isset($_SESSION['bus_id'])) {
    header("Location: hostelbus.php");
    exit();
}
include 'db.php';

$student_id = $_POST['id'] ?? null;
$busname = $_SESSION['busname'];

if ($student_id) {
    // Get current max seat assigned for this bus
    $query = $conn->prepare("SELECT seatno FROM BUSREG WHERE busname = ? AND seatno <> '' ORDER BY id ASC");
    $query->bind_param("s", $busname);
    $query->execute();
    $result = $query->get_result();

    $assigned = [];
    while ($row = $result->fetch_assoc()) {
        $assigned[] = $row['seatno'];
    }

    // Generate seat list A1..I5
    $rows = range('A', 'I');
    $seats = [];
    foreach ($rows as $r) {
        for ($i = 1; $i <= 5; $i++) {
            $seats[] = $r . $i;
        }
    }

    // Find first available seat
    $available = array_diff($seats, $assigned);
    $nextSeat = reset($available);

    if ($nextSeat) {
        $update = $conn->prepare("UPDATE BUSREG SET seatno = ? WHERE id = ? AND busname = ?");
        $update->bind_param("sis", $nextSeat, $student_id, $busname);
        $update->execute();
    }
}

header("Location: hostelbus_dashboard.php");
exit();
?>
