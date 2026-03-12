<?php
$servername = "sql211.infinityfree.com";
$username   = "if0_39689452";
$password   = "0JaTuFZVF3U0L";
$dbname     = "if0_39689452_newjan2026";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Force MySQL to use India timezone
$conn->query("SET time_zone = '+05:30'");
?>
