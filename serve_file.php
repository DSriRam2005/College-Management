<?php
// serve_file.php
session_start();
include 'db.php';
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
if ($test_id <= 0) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

// Fetch path OR file blob
$stmt = $conn->prepare("SELECT question_paper_path FROM tests WHERE test_id = ? LIMIT 1");
$stmt->bind_param("i",$test_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(404);
    echo "Not found";
    exit;
}
$stmt->bind_result($path);
$stmt->fetch();
$stmt->close();

$path = trim((string)($path ?? ''));

// If path is empty -> 404
if ($path === '') {
    http_response_code(404);
    echo "No file configured";
    exit;
}

// If the DB stores a full URL, redirect
if (filter_var($path, FILTER_VALIDATE_URL)) {
    header("Location: " . $path);
    exit;
}

// Otherwise assume it's a filesystem path relative to project
$safe = str_replace(["\0", "../", "..\\"], '', $path);
$fullPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($safe, "/\\");

if (!is_file($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    echo "File not found or unreadable";
    exit;
}

// Serve with correct headers
$mime = mime_content_type($fullPath) ?: 'application/octet-stream';
$basename = basename($fullPath);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
