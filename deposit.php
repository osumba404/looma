<?php
require_once 'includes/db.php';
require 'vendor/autoload.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize variables
$user_id = $_SESSION['user_id'];
$csrf_token = $_SESSION['csrf_token'];

// Fetch user phone
try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    $stmt = $conn->prepare('SELECT phone FROM users WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log('Error in deposit.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '. Please try again or contact support.']);
    exit();
}

// Handle deposit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    header('Content-Type: application/json');
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            error_log('CSRF token mismatch. Session: ' . $_SESSION['csrf_token'] . ', Post: ' . $_POST['csrf_token']);
            throw new Exception('Invalid CSRF token');
        }

        $amount = floatval($_POST['amount']);
        $phone = trim($_POST['phone']);

        if ($amount < 1) {
            throw new Exception('Minimum deposit amount is Ksh 1');
        }
        if (!preg_match('/^\+2547[0-9]{8}$/', $phone)) {
            throw new Exception('Invalid phone number. Use format: +2547XXXXXXXX');
        }

        $access_token = getAccessToken();
        $stk_response = initiateSTKPush($access_token, $amount, $phone, 'Deposit to Looma');

        if ($stk_response['ResponseCode'] === '0') {
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, phone_number, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $type = 'deposit';
            $transaction_id = $stk_response['CheckoutRequestID'];
            $status = 'pending';
            $stmt->bind_param('isdsss', $user_id, $type, $amount, $phone, $transaction_id, $status);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['status' => 'success', 'message' => 'Deposit request of Ksh ' . number_format($amount, 2) . ' initiated. Please check your phone to complete the payment.']);
        } else {
            throw new Exception('Failed to initiate deposit: ' . ($stk_response['errorMessage'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        error_log('Error in deposit.php: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit();
}

// Get access token for M-Pesa API
function getAccessToken() {
    $url = $_ENV['MPESA_ENVIRONMENT'] === 'sandbox' ? 
           $_ENV['MPESA_SANDBOX_URL'] . '/oauth/v1/generate?grant_type=client_credentials' : 
           $_ENV['MPESA_PRODUCTION_URL'] . '/oauth/v1/generate?grant_type=client_credentials';
    
    $credentials = base64_encode($_ENV['MPESA_CONSUMER_KEY'] . ':' . $_ENV['MPESA_CONSUMER_SECRET']);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $start_time = microtime(true);
    $response = curl_exec($curl);
    $end_time = microtime(true);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    error_log('M-Pesa getAccessToken took ' . ($end_time - $start_time) . ' seconds');
    if ($http_code !== 200 || $error) {
        throw new Exception('Failed to get access token: ' . ($error ?: 'HTTP ' . $http_code));
    }
    
    $data = json_decode($response, true);
    return $data['access_token'];
}

// Initiate STK Push for C2B
function initiateSTKPush($access_token, $amount, $phone, $description) {
    $url = $_ENV['MPESA_ENVIRONMENT'] === 'sandbox' ? 
           $_ENV['MPESA_SANDBOX_URL'] . '/mpesa/stkpush/v1/processrequest' : 
           $_ENV['MPESA_PRODUCTION_URL'] . '/mpesa/stkpush/v1/processrequest';
    
    $timestamp = date('YmdHis');
    $password = base64_encode($_ENV['MPESA_C2B_SHORTCODE'] . $_ENV['MPESA_PASSKEY'] . $timestamp);
    
    $payload = [
        'BusinessShortCode' => $_ENV['MPESA_C2B_SHORTCODE'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $_ENV['MPESA_C2B_SHORTCODE'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $_ENV['MPESA_RESULT_URL'],
        'AccountReference' => 'LoomaDeposit_' . time(),
        'TransactionDesc' => $description
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $start_time = microtime(true);
    $response = curl_exec($curl);
    $end_time = microtime(true);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    error_log('M-Pesa initiateSTKPush took ' . ($end_time - $start_time) . ' seconds');
    if ($http_code !== 200 || $error) {
        throw new Exception('STK Push request failed: ' . ($error ?: 'HTTP ' . $http_code));
    }
    
    $data = json_decode($response, true);
    if (isset($data['errorCode'])) {
        throw new Exception('STK Push error: ' . $data['errorMessage']);
    }
    
    return $data;
}
?>

<!-- Deposit Form Modal -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depositModalLabel">Deposit Funds</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" role="dialog" aria-describedby="depositModalDescription">
                <p id="depositModalDescription" class="sr-only">Form to deposit funds into your Looma wallet.</p>
                <form method="POST" id="depositForm" class="deposit-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="deposit_amount" class="form-label">Amount (Ksh)</label>
                        <input type="number" class="form-control" id="deposit_amount" name="amount" min="1" step="0.01" autocomplete="off" required>
                        <small class="form-text text-muted">Minimum deposit: Ksh 1</small>
                    </div>
                    <div class="mb-3">
                        <label for="deposit_phone" class="form-label">M-Pesa Phone Number</label>
                        <input type="text" class="form-control" id="deposit_phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" autocomplete="tel" required>
                        <small class="form-text text-muted">Format: +2547XXXXXXXX</small>
                    </div>
                    <button type="submit" name="deposit" class="btn btn-primary w-100">Submit Deposit</button>
                    <div class="loading-spinner" id="depositLoadingSpinner">
                        <i class="fas fa-spinner fa-spin"></i> Processing...
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>