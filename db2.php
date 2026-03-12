 <?php
$host = "sql211.infinityfree.com";  // MySQL Hostname
$username = "if0_39689452";         // MySQL Username
$password = "0JaTuFZVF3U0L";       // MySQL Password
$database = "if0_39689452_ttesting"; // MySQL Database Name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: To check connection success
// echo "Connected successfully";
?>