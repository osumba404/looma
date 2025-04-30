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

// Fetch user phone and balance
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

    $stmt = $conn->prepare('SELECT balance FROM wallet WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $balance = $wallet ? floatval($wallet['balance']) : 0.00;
    $stmt->close();
} catch (Exception $e) {
    error_log('Error in withdraw.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . htmlspecialchars($e->getMessage()) . '. Please try again or contact support.']);
    exit();
}

// Handle withdrawal form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    header('Content-Type: application/json');
    try {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            error_log('CSRF token mismatch. Session: ' . $_SESSION['csrf_token'] . ', Post: ' . $_POST['csrf_token']);
            throw new Exception('Invalid CSRF token');
        }

        $amount = floatval($_POST['amount']);
        $phone = trim($_POST['phone']);

        if ($amount < 50) {
            throw new Exception('Minimum withdrawal amount is Ksh 50');
        }
        if ($amount > $balance) {
            throw new Exception('Insufficient balance');
        }
        if (!preg_match('/^\+2547[0-9]{8}$/', $phone)) {
            throw new Exception('Invalid phone number. Use format: +2547XXXXXXXX');
        }

        $access_token = getAccessToken();
        $b2c_response = initiateB2C($access_token, $amount, $phone, 'Withdrawal from Looma');

        if ($b2c_response['ResponseCode'] === '0') {
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, phone_number, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $type = 'withdrawal';
            $transaction_id = $b2c_response['ConversationID'];
            $status = 'pending';
            $stmt->bind_param('isdsss', $user_id, $type, $amount, $phone, $transaction_id, $status);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = NOW() WHERE user_id = ?');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('di', $amount, $user_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['status' => 'success', 'message' => 'Withdrawal request of Ksh ' . number_format($amount, 2) . ' initiated. You will receive a confirmation soon.']);
        } else {
            throw new Exception('Failed to initiate withdrawal: ' . ($b2c_response['errorMessage'] ?? 'Unknown error'));
        }
    } catch (Exception $e) {
        error_log('Error in withdraw.php: ' . $e->getMessage());
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

// Initiate B2C transaction
function initiateB2C($access_token, $amount, $phone, $remarks) {
    $url = $_ENV['MPESA_ENVIRONMENT'] === 'sandbox' ? 
           $_ENV['MPESA_SANDBOX_URL'] . '/mpesa/b2c/v3/paymentrequest' : 
           $_ENV['MPESA_PRODUCTION_URL'] . '/mpesa/b2c/v3/paymentrequest';
    
    $payload = [
        'InitiatorName' => $_ENV['MPESA_INITIATOR_NAME'],
        'SecurityCredential' => $_ENV['MPESA_SECURITY_CREDENTIAL'],
        'CommandID' => 'BusinessPayment',
        'Amount' => $amount,
        'PartyA' => $_ENV['MPESA_B2C_SHORTCODE'],
        'PartyB' => $phone,
        'Remarks' => $remarks,
        'QueueTimeOutURL' => $_ENV['MPESA_TIMEOUT_URL'],
        'ResultURL' => $_ENV['MPESA_RESULT_URL'],
        'Occasion' => 'Looma Withdrawal'
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
    
    error_log('M-Pesa initiateB2C took ' . ($end_time - $start_time) . ' seconds');
    if ($http_code !== 200 || $error) {
        throw new Exception('B2C request failed: ' . ($error ?: 'HTTP ' . $http_code));
    }
    
    $data = json_decode($response, true);
    if (isset($data['errorCode'])) {
        throw new Exception('B2C error: ' . $data['errorMessage']);
    }
    
    return $data;
}
?>

<!-- Withdrawal Form Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="withdrawModalLabel">Withdraw Funds</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" role="dialog" aria-describedby="withdrawModalDescription">
                <p id="withdrawModalDescription" class="sr-only">Form to withdraw funds from your Looma wallet.</p>
                <form method="POST" id="withdrawForm" class="withdrawal-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (Ksh)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="50" step="0.01" autocomplete="off" required>
                        <small class="form-text text-muted">Minimum withdrawal: Ksh 50</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">M-Pesa Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" autocomplete="tel" required>
                        <small class="form-text text-muted">Format: +2547XXXXXXXX</small>
                    </div>
                    <button type="submit" name="withdraw" class="btn btn-primary w-100">Submit Withdrawal</button>
                    <div class="loading-spinner" id="withdrawLoadingSpinner">
                        <i class="fas fa-spinner fa-spin"></i> Processing...
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>