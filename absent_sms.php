<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

include 'db.php';
$conn->query("SET time_zone = '+05:30'");

// SMS API static values
$sender = 'KIETGP'; 
$auth = 'D!~9038HjSgZLpvVa'; 
$template_id = '1107175739144224776';


$message_template = "Dear Parent,\n{#var#} was absent today.\nFor any queries, kindly contact the class teacher:\n{#var#} (Ph: {#var#}).\nKIET";

// Function to send SMS
function SendSMS($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return "Curl Error: $err";
    }
    curl_close($ch);
    return $result;
}

$sent_count = 0;
$failed_count = 0;

// ------------------ MAIN QUERY (Corrected) ------------------

$sql = "
    SELECT s.htno, s.name, s.phone, s.f_phone, s.m_phone, s.st_phone,
           u.name AS staff_name, u.ph_no AS staff_mobile
    FROM attendance a
    INNER JOIN STUDENTS s ON a.htno = s.htno
    LEFT JOIN USERS u ON s.classid = u.classid AND u.role IN ('PR','HTPO','CPTO','TEAM','TPO','ZONE','CALENDAR','ADMIN')
    WHERE a.status = 'Absent'
      AND a.att_date = CURDATE()
";

$result = $conn->query($sql);

$numberMessages = [];

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $student_name = trim($row['name']);
        $staff_name   = trim($row['staff_name']);
        $staff_mobile = preg_replace('/\D/', '', $row['staff_mobile']);

        // Collect all possible numbers
        $numbers = [
            
            preg_replace('/\D/', '', $row['f_phone']),
            
            preg_replace('/\D/', '', $row['st_phone']),
        ];

        $numbers = array_unique(array_filter($numbers));

        foreach ($numbers as $num) {
            $num = substr($num, -10);
            if (!preg_match('/^[6-9]\d{9}$/', $num)) continue;

            // Build message
            $msg = $message_template;
            $msg = preg_replace('/{#var#}/', strtoupper($student_name), $msg, 1);
            $msg = preg_replace('/{#var#}/', $staff_name ?: "Class Teacher", $msg, 1);
            $msg = preg_replace('/{#var#}/', $staff_mobile ?: "NA", $msg, 1);

            $numberMessages[$num][] = $msg;
        }
    }

    // ---------- SEND SMS ----------
    foreach ($numberMessages as $phone => $messages) {
        foreach ($messages as $msg) {
            $encoded_msg = urlencode($msg);
            $url = "https://m.smsap.com/API/sms-api.php?auth={$auth}&msisdn={$phone}&senderid={$sender}&message={$encoded_msg}&template_id={$template_id}";

            $response = SendSMS($url);

            file_put_contents("sms_log.txt", "Sent to: $phone | Msg: $msg | Response: $response\n", FILE_APPEND);

            if (stripos($response, 'success') !== false)
                $sent_count++;
            else
                $failed_count++;
        }
    }

    $status = "Messages sent: $sent_count, Failed: $failed_count.";

} else {
    $status = "No absentees found or DB error: " . $conn->error;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Send Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5 text-center">
    <h3 class="mb-3 text-success"><?= htmlspecialchars($status) ?></h3>
    <a href="at_today_attendance.php" class="btn btn-primary">← Back to Attendance Page</a>
</div>
</body>
</html>
