<?php
require_once 'includes/db.php';

// Log the raw callback for debugging
$callback_data = file_get_contents('php://input');
error_log("C2B Callback - Raw Data: " . $callback_data);

// Decode the callback JSON
$data = json_decode($callback_data, true);

if (!$data || !isset($data['Body']['stkCallback'])) {
    error_log("C2B Callback - Invalid or missing stkCallback data");
    http_response_code(400);
    echo json_encode(['ResultDesc' => 'Invalid callback data']);
    exit();
}

$stk_callback = $data['Body']['stkCallback'];
$checkout_request_id = $stk_callback['CheckoutRequestID'] ?? '';
$result_code = $stk_callback['ResultCode'] ?? '';
$result_desc = $stk_callback['ResultDesc'] ?? '';

// Find the transaction in the database using CheckoutRequestID
$stmt = $conn->prepare('SELECT id, user_id, amount FROM transactions WHERE transaction_id = ? AND type = "deposit"');
$stmt->bind_param('s', $checkout_request_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    error_log("C2B Callback - Transaction not found for CheckoutRequestID: $checkout_request_id");
    http_response_code(404);
    echo json_encode(['ResultDesc' => 'Transaction not found']);
    exit();
}

$transaction_id = $transaction['id'];
$user_id = $transaction['user_id'];
$amount = $transaction['amount'];

// Update transaction status based on ResultCode
// ResultCode 0 = Success, anything else = Failure
$status = $result_code == 0 ? 'completed' : 'failed';

$stmt = $conn->prepare('UPDATE transactions SET status = ?, updated_at = NOW() WHERE id = ?');
$stmt->bind_param('si', $status, $transaction_id);
$stmt->execute();
$stmt->close();

// If the transaction was successful, add the amount to the user's wallet
if ($result_code == 0) {
    $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ?, last_interact = NOW() WHERE user_id = ?');
    $stmt->bind_param('di', $amount, $user_id);
    $stmt->execute();
    $stmt->close();
    error_log("C2B Callback - Transaction $checkout_request_id completed, wallet updated");
} else {
    error_log("C2B Callback - Transaction $checkout_request_id failed: $result_desc");
}

// Respond to M-Pesa to acknowledge receipt
http_response_code(200);
echo json_encode(['ResultDesc' => 'Callback received successfully']);
?>