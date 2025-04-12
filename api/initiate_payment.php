<?php
require_once '../includes/db.php';
require_once '../includes/mpesa.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id']);
    exit;
}

try {
    // Check if user exists and is verified but not activated
    $stmt = $pdo->prepare('SELECT phone, is_verified, is_activated FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if (!$user['is_verified']) {
        http_response_code(400);
        echo json_encode(['error' => 'Phone not verified']);
        exit;
    }

    if ($user['is_activated']) {
        http_response_code(400);
        echo json_encode(['error' => 'Account already activated']);
        exit;
    }

    // Create transaction
    $amount = 100; // Activation fee (e.g., KES 100)
    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, amount, type, status) VALUES (?, ?, "deposit", "pending")');
    $stmt->execute([$user_id, $amount]);
    $transaction_id = $pdo->lastInsertId();

    // Initiate STK Push
    $mpesa = new MpesaAPI();
    $response = $mpesa->initiateSTKPush($user['phone'], $amount, $transaction_id);

    if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
        // Store M-Pesa checkout request ID
        $stmt = $pdo->prepare('UPDATE transactions SET mpesa_code = ? WHERE transaction_id = ?');
        $stmt->execute([$response['CheckoutRequestID'], $transaction_id]);
        echo json_encode(['message' => 'Payment initiated, check your phone']);
    } else {
        $stmt = $pdo->prepare('UPDATE transactions SET status = "failed" WHERE transaction_id = ?');
        $stmt->execute([$transaction_id]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to initiate payment', 'details' => $response]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>