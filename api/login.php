<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$identifier = trim($input['identifier'] ?? ''); // Username or phone
$password = $input['password'] ?? '';

if (!$identifier || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing identifier or password']);
    exit;
}

try {
    // Check if user exists (username or phone)
    $stmt = $pdo->prepare('SELECT user_id, password, is_verified, is_activated FROM users WHERE username = ? OR phone = ?');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    // Check verification and activation
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode(['error' => 'Phone not verified']);
        exit;
    }

    if (!$user['is_activated']) {
        http_response_code(403);
        echo json_encode(['error' => 'Account not activated']);
        exit;
    }

    echo json_encode(['message' => 'Login successful', 'user_id' => $user['user_id']]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>