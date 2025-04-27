<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$theme = $_POST['theme'] ?? 'light';

if (!in_array($theme, ['light', 'dark'])) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare('UPDATE users SET theme = ? WHERE user_id = ?');
$stmt->bind_param('si', $theme, $user_id);
if ($stmt->execute()) {
    echo 'success';
} else {
    http_response_code(500);
}
$stmt->close();
?>