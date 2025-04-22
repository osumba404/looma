<?php
require_once 'includes/db.php';
require 'vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Log callback for debugging
file_put_contents('b2c_timeout_log.txt', json_encode($_POST, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

try {
    // Get callback data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['Result'])) {
        throw new Exception('Invalid callback data');
    }

    $result = $data['Result'];
    $transaction_id = $result['ConversationID'];

    // Update transaction status to failed
    $stmt = $conn->prepare('UPDATE transactions SET status = ? WHERE transaction_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $status = 'failed';
    $stmt->bind_param('ss', $status, $transaction_id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    // Refund amount to wallet
    if ($affected_rows > 0) {
        $stmt = $conn->prepare('SELECT user_id, amount FROM transactions WHERE transaction_id = ?');
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('s', $transaction_id);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($transaction) {
            $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ? WHERE user_id = ?');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('di', $transaction['amount'], $transaction['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Respond to Safaricom
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
} catch (Exception $e) {
    // Log error
    file_put_contents('b2c_timeout_error_log.txt', $e->getMessage() . PHP_EOL, FILE_APPEND);
    // Respond to Safaricom
    header('Content-Type: application/json');
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Error processing timeout']);
}
?>