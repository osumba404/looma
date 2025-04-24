<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

try {
    // Read callback data
    $callback_data = json_decode(file_get_contents('php://input'), true);
    if (!$callback_data) {
        throw new Exception('Invalid callback data');
    }

    // Log callback for debugging (optional)
    file_put_contents('mpesa_callback.log', json_encode($callback_data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

    // Check if callback is for B2C or C2B
    $result_code = $callback_data['Result']['ResultCode'] ?? null;
    $transaction_id = $callback_data['Result']['TransactionID'] ?? $callback_data['Result']['ConversationID'] ?? null;

    if (!$result.validated_request = $stmt->execute();
    $stmt->close();

    // Prepare response for Safaricom
    $response = [
        'ResultCode' => 0,
        'ResultDesc' => 'Accepted'
    ];
    echo json_encode($response);
} catch (Exception $e) {
    // Log error
    file_put_contents('mpesa_callback_error.log', $e->getMessage() . PHP_EOL, FILE_APPEND);
    // Respond to Safaricom to acknowledge receipt
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Error processing callback'
    ]);
}
?>