<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

// Log callback for debugging (optional)
$callback_data = file_get_contents('php://input');
file_put_contents('mpesa_callback_log.txt', date('Y-m-d H:i:s') . ': ' . $callback_data . "\n", FILE_APPEND);

$data = json_decode($callback_data, true);

// Check if callback contains STK Push result
if (!isset($data['Body']['stkCallback']['ResultCode'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid callback data']);
    exit;
}

$checkout_request_id = $data['Body']['stkCallback']['CheckoutRequestID'];
$result_code = $data['Body']['stkCallback']['ResultCode'];
$result_desc = $data['Body']['stkCallback']['ResultDesc'];

try {
    // Find transaction
    $stmt = $pdo->prepare('SELECT transaction_id, user_id FROM transactions WHERE mpesa_code = ?');
    $stmt->execute([$checkout_request_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    if ($result_code == 0) {
        // Payment successful
        $stmt = $pdo->prepare('UPDATE transactions SET status = "completed" WHERE transaction_id = ?');
        $stmt->execute([$transaction['transaction_id']]);

        // Activate user account
        $stmt = $pdo->prepare('UPDATE users SET is_activated = TRUE WHERE user_id = ?');
        $stmt->execute([$transaction['user_id']]);

        echo json_encode(['message' => 'Payment processed, account activated']);
    } else {
        // Payment failed
        $stmt = $pdo->prepare('UPDATE transactions SET status = "failed" WHERE transaction_id = ?');
        $stmt->execute([$transaction['transaction_id']]);
        echo json_encode(['message' => 'Payment failed', 'details' => $result_desc]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>