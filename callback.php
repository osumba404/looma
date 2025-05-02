<?php
require_once 'includes/db.php';

// Handle M-Pesa callback
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['ResultCode']) && $data['ResultCode'] === '0') {
    $transaction_id = isset($data['TransactionID']) ? $data['TransactionID'] : (isset($data['Result']['ConversationID']) ? $data['Result']['ConversationID'] : '');
    $amount = floatval(isset($data['Amount']) ? $data['Amount'] : (isset($data['Result']['Amount']) ? $data['Result']['Amount'] : 0));
    $phone = isset($data['MSISDN']) ? $data['MSISDN'] : (isset($data['Result']['PhoneNumber']) ? $data['Result']['PhoneNumber'] : '');

    if (!$transaction_id || !$amount || !$phone) {
        error_log('Invalid callback data: ' . json_encode($data));
        echo json_encode(['Result' => ['ResultCode' => 1, 'ResultDesc' => 'Invalid data']]);
        exit();
    }

    $stmt = $conn->prepare('SELECT user_id, type, amount FROM transactions WHERE transaction_id = ? AND status = "pending"');
    $stmt->bind_param('s', $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    $stmt->close();

    if ($transaction) {
        $user_id = $transaction['user_id'];
        $type = $transaction['type'];
        $requested_amount = floatval($transaction['amount']);

        if ($amount !== $requested_amount) {
            error_log("Amount mismatch: Requested $requested_amount, received $amount for transaction $transaction_id");
            echo json_encode(['Result' => ['ResultCode' => 1, 'ResultDesc' => 'Amount mismatch']]);
            exit();
        }

        $conn->begin_transaction();
        try {
            // Update transaction status
            $stmt = $conn->prepare('UPDATE transactions SET status = "completed", updated_at = NOW() WHERE transaction_id = ?');
            $stmt->bind_param('s', $transaction_id);
            $stmt->execute();
            $stmt->close();

            // Update wallet based on transaction type
            if ($type === 'deposit') {
                $stmt = $conn->prepare('UPDATE wallet SET balance = balance + ?, last_interact = NOW() WHERE user_id = ?');
                $stmt->bind_param('di', $amount, $user_id);
                $stmt->execute();
                $stmt->close();
            } elseif ($type === 'withdrawal') {
                $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = NOW() WHERE user_id = ?');
                $stmt->bind_param('di', $amount, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            error_log("Transaction $transaction_id completed successfully for user $user_id");
        } catch (Exception $e) {
            $conn->rollback();
            error_log('Callback error: ' . $e->getMessage());
            echo json_encode(['Result' => ['ResultCode' => 1, 'ResultDesc' => 'Database error']]);
            exit();
        }
    } else {
        error_log("No pending transaction found for $transaction_id");
    }
} else {
    error_log('Callback failed or invalid ResultCode: ' . json_encode($data));
}

// Return success response to M-Pesa
echo json_encode(['Result' => ['ResultCode' => 0, 'ResultDesc' => 'Accepted']]);
?>