<?php
require_once 'includes/db.php';

// Log the raw callback for debugging
$callback_data = file_get_contents('php://input');
error_log("B2C Callback - Raw Data: " . $callback_data);

// Decode the callback JSON
$data = json_decode($callback_data, true);

if (!$data || !isset($data['Result'])) {
    error_log("B2C Callback - Invalid or missing Result data");
    http_response_code(400);
    echo json_encode(['ResultDesc' => 'Invalid callback data']);
    exit();
}

$result = $data['Result'];
$transaction_id = $result['TransactionID'] ?? '';
$result_code = $result['ResultCode'] ?? '';
$result_desc = $result['ResultDesc'] ?? '';
$originator_conversation_id = $result['OriginatorConversationID'] ?? '';

// Find the transaction in the database using OriginatorConversationID
$stmt = $conn->prepare('SELECT id, user_id, amount FROM transactions WHERE transaction_id = ? AND type = "withdrawal"');
$stmt->bind_param('s', $originator_conversation_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    error_log("B2C Callback - Transaction not found for OriginatorConversationID: $originator_conversation_id");
    http_response_code(404);
    echo json_encode(['ResultDesc' => 'Transaction not found']);
    exit();
}

$transaction_id_db = $transaction['id'];
$user_id = $transaction['user_id'];
$amount = $transaction['amount'];

// Update transaction status based on ResultCode
// ResultCode 0 = Success, anything else = Failure
$status = $result_code == 0 ? 'completed' : 'failed';

$stmt = $conn->prepare('UPDATE transactions SET status = ?, mpesa_transaction_id = ?, updated_at = NOW() WHERE id = ?');
$stmt->bind_param('ssi', $status, $transaction_id, $transaction_id_db);
$stmt->execute();
$stmt->close();

// If the transaction was successful, deduct the amount from the user's wallet
if ($result_code == 0) {
    $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = NOW() WHERE user_id = ?');
    $stmt->bind_param('di', $amount, $user_id);
    $stmt->execute();
    $stmt->close();
    error_log("B2C Callback - Transaction $originator_conversation_id completed, wallet updated");
} else {
    error_log("B2C Callback - Transaction $originator_conversation_id failed: $result_desc");
}

// Respond to M-Pesa to acknowledge receipt
http_response_code(200);
echo json_encode(['ResultDesc' => 'Callback received successfully']);
?>