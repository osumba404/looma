<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;
$code = trim($input['code'] ?? '');

if (!$user_id || !$code) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or code']);
    exit;
}

try {
    // Check verification code
    $stmt = $pdo->prepare('SELECT code, status, expires_at FROM verification_codes WHERE user_id = ? AND code = ? AND status = "pending"');
    $stmt->execute([$user_id, $code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired code']);
        exit;
    }

    // Check if code is expired
    if (new DateTime() > new DateTime($result['expires_at'])) {
        $stmt = $pdo->prepare('UPDATE verification_codes SET status = "expired" WHERE user_id = ? AND code = ?');
        $stmt->execute([$user_id, $code]);
        http_response_code(400);
        echo json_encode(['error' => 'Code has expired']);
        exit;
    }

    // Mark code as verified
    $stmt = $pdo->prepare('UPDATE verification_codes SET status = "verified" WHERE user_id = ? AND code = ?');
    $stmt->execute([$user_id, $code]);

    // Update user verification status
    $stmt = $pdo->prepare('UPDATE users SET is_verified = TRUE WHERE user_id = ?');
    $stmt->execute([$user_id]);

    echo json_encode(['message' => 'Phone verified successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>