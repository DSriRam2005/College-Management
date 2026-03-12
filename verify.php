<?php
session_start();
include 'db.php';

$message = "";
$student = null;

// Step 1: Search student by HT No
if (isset($_POST['search_htno'])) {
    $htno = $_POST['htno'];
    $stmt = $conn->prepare("SELECT * FROM STUDENTS WHERE htno=?");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    if (!$student) $message = "Student not found!";
}

// Step 2: Send OTP
if (isset($_POST['send_otp'])) {
    $htno = $_POST['htno'];
    $email = $_POST['email'];

    $otp = rand(100000, 999999);

    // Save OTP in verifys table
    $stmt = $conn->prepare("INSERT INTO verifys (email, otp) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();

    // Send OTP via PHP mail()
    $subject = "Your OTP Verification Code";
    $messageBody = "Hello,\n\nYour OTP is: $otp\nIt is valid for 10 minutes.\n\nRegards,\nAdmin";
    $headers = "From: kietgroupinfo@gmail.com
";
    $headers .= "Reply-To: kietgroupinfo@gmail.com\r\n";

    if (mail($email, $subject, $messageBody, $headers)) {
        $message = "OTP sent to $email! It is valid for 10 minutes.";
    } else {
        $message = "Failed to send OTP. Please try again.";
    }
}

// Step 3: Verify OTP
if (isset($_POST['verify_otp'])) {
    $htno = $_POST['htno'];
    $email = $_POST['email'];
    $otp_input = $_POST['otp'];

    $stmt = $conn->prepare("SELECT * FROM verifys WHERE email=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $otp_created = strtotime($row['created_at']);
        $now = time();
        $diff = ($now - $otp_created)/60; // minutes

        if ($diff > 10) {
            $message = "OTP expired. Please request a new one.";
        } elseif ($row['otp'] == $otp_input) {
            // Save verified email
            $stmt = $conn->prepare("UPDATE STUDENTS SET email=? WHERE htno=?");
            $stmt->bind_param("ss", $email, $htno);
            $stmt->execute();
            $message = "Email verified and saved successfully!";
        } else {
            $message = "Invalid OTP!";
        }
    } else {
        $message = "No OTP found. Please request a new one.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Email Verification</title>
</head>
<body>
<h2>Student Email Verification</h2>
<p style="color:red;"><?php echo $message; ?></p>

<!-- Step 1: Search by HTNO -->
<form method="post">
    <input type="text" name="htno" placeholder="Enter HT No" required>
    <button type="submit" name="search_htno">Search</button>
</form>

<?php if ($student): ?>
    <h3>Student Details</h3>
    <p>Name: <?php echo $student['name']; ?></p>
    <p>Program: <?php echo $student['prog']; ?></p>
    <p>College: <?php echo $student['college']; ?></p>

    <!-- Step 2 & 3: Email & OTP -->
    <form method="post">
        <input type="hidden" name="htno" value="<?php echo $student['htno']; ?>">
        <input type="email" name="email" placeholder="Enter your Email" value="<?php echo $student['email'] ?? ''; ?>" required><br><br>
        <input type="text" name="otp" placeholder="Enter OTP if received"><br><br>
        <button type="submit" name="send_otp">Send OTP</button>
        <button type="submit" name="verify_otp">Verify OTP</button>
    </form>
<?php endif; ?>
</body>
</html>
