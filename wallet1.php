<?php
session_start();
require_once 'includes/db.php';
require 'vendor/autoload.php'; // Composer autoload for phpdotenv
use Dotenv\Dotenv;

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize variables with defaults
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Unknown';
$error = '';
$success = '';
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
$user = null;
$balance = 0.00;
$points = 0;
$referral_count = 0;

try {
    // Verify database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Fetch user data
    $stmt = $conn->prepare('SELECT full_name, username, referral_code, phone FROM users WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_destroy();
        header('Location: login.php?error=User not found');
        exit();
    }

    // Fetch wallet balance
    $stmt = $conn->prepare('SELECT balance FROM wallet WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
        // Create wallet record if none exists
        $stmt = $conn->prepare('INSERT INTO wallet (user_id, balance, last_interact) VALUES (?, 0.00, NOW())');
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        $balance = 0.00;
    } else {
        $balance = floatval($wallet['balance']);
    }

    // Fetch total points
    $stmt = $conn->prepare('SELECT SUM(points) as total_points FROM points WHERE user_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $points_result = $stmt->get_result()->fetch_assoc();
    $points = $points_result['total_points'] ? intval($points_result['total_points']) : 0;
    $stmt->close();

    // Count referrals
    $stmt = $conn->prepare('SELECT COUNT(*) as referral_count FROM referrals WHERE referrer_id = ?');
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $referral_count = $stmt->get_result()->fetch_assoc()['referral_count'];
    $stmt->close();

    // Handle deposit form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token');
        }

        $amount = floatval($_POST['amount']);
        $phone = trim($_POST['phone']);

        // Validation
        if ($amount < 1) {
            throw new Exception('Minimum deposit amount is Ksh 1');
        }
        if (!preg_match('/^\+2547[0-9]{8}$/', $phone)) {
            throw new Exception('Invalid phone number. Use format: +2547XXXXXXXX');
        }

        // Initiate C2B STK Push
        $access_token = getAccessToken();
        $stk_response = initiateSTKPush($access_token, $amount, $phone, 'Deposit to Looma');

        if ($stk_response['ResponseCode'] === '0') {
            // Insert transaction as pending
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

            $success = '<script>showSuccessModal("Deposit request of Ksh ' . number_format($amount, 2) . ' initiated. Please check your phone to complete the payment.");</script>';
        } else {
            throw new Exception('Failed to initiate deposit: ' . ($stk_response['errorMessage'] ?? 'Unknown error'));
        }
    }

    // Handle withdrawal form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token');
        }

        $amount = floatval($_POST['amount']);
        $phone = trim($_POST['phone']);

        // Validation
        if ($amount < 50) {
            throw new Exception('Minimum withdrawal amount is Ksh 50');
        }
        if ($amount > $balance) {
            throw new Exception('Insufficient balance');
        }
        if (!preg_match('/^\+2547[0-9]{8}$/', $phone)) {
            throw new Exception('Invalid phone number. Use format: +2547XXXXXXXX');
        }

        // Initiate B2C transaction
        $access_token = getAccessToken();
        $b2c_response = initiateB2C($access_token, $amount, $phone, 'Withdrawal from Looma');

        if ($b2c_response['ResponseCode'] === '0') {
            // Insert transaction as pending
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

            // Deduct amount from wallet
            $stmt = $conn->prepare('UPDATE wallet SET balance = balance - ?, last_interact = NOW() WHERE user_id = ?');
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param('di', $amount, $user_id);
            $stmt->execute();
            $stmt->close();

            $success = '<script>showSuccessModal("Withdrawal request of Ksh ' . number_format($amount, 2) . ' initiated. You will receive a confirmation soon.");</script>';
        } else {
            throw new Exception('Failed to initiate withdrawal: ' . ($b2c_response['errorMessage'] ?? 'Unknown error'));
        }
    }
} catch (Exception $e) {
    error_log('Error in wallet1.php: ' . $e->getMessage());
    $error = '<script>showErrorModal("' . htmlspecialchars($e->getMessage()) . '");</script>';
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
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
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
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($http_code !== 200 || $error) {
        throw new Exception('STK Push request failed: ' . ($error ?: 'HTTP ' . $http_code));
    }
    
    $data = json_decode($response, true);
    if (isset($data['errorCode'])) {
        throw new Exception('STK Push error: ' . $data['errorMessage']);
    }
    
    return $data;
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
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($http_code !== 200 || $error) {
        throw new Exception('B2C request failed: ' . ($error ?: 'HTTP ' . $http_code));
    }
    
    $data = json_decode($response, true);
    if (isset($data['errorCode'])) {
        throw new Exception('B2C error: ' . $data['errorMessage']);
    }
    
    return $data;
}

// Get user initials
$initials = '';
if ($user) {
    $name_parts = explode(' ', $user['full_name']);
    if (count($name_parts) >= 1) {
        $initials .= strtoupper(substr($name_parts[0], 0, 1));
        if (count($name_parts) > 1) {
            $initials .= strtoupper(substr($name_parts[1], 0, 1));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Looma | Earnings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .card-icon.primary { color: #6c5ce7; }
        .card-icon.success { color: #00cec9; }
        .card-icon.accent { color: #fd79a8; }
        .card-value {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .card-title {
            font-size: 1rem;
            color: #666;
        }
        .animate-fadeIn {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }
        .animate-fadeIn.animate {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-1 { transition-delay: 0.2s; }
        .delay-2 { transition-delay: 0.4s; }
        .withdrawal-form, .deposit-form {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background: #6c5ce7;
            color: white;
        }
        @media (max-width: 576px) {
            .dashboard-card {
                padding: 15px;
            }
            .card-value {
                font-size: 1.5rem;
            }
            .withdrawal-form, .deposit-form {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Desktop Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>LOOMA</h2>
            <p>Earn While You Play</p>
        </div>
        <nav class="nav flex-column">
            <a href="index1.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="games.php" class="nav-link">
                <i class="fas fa-gamepad"></i>
                <span>Games</span>
            </a>
            <a href="wallet1.php" class="nav-link active">
                <i class="fas fa-chart-line"></i>
                <span>Earnings</span>
            </a>
            <a href="referrals.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Referrals</span>
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log out</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <p>© 2025 Looma</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <h2>LOOMA</h2>
            <div class="user-profile">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?></div>
                </div>
            </div>
        </div>

        <!-- Content Container -->
        <div class="content-container">
            <?php if ($error && !$user): ?>
                <div class="alert alert-danger text-center">
                    Unable to load wallet data. Please try again or contact support.
                </div>
            <?php endif; ?>
            <?php echo $error; ?>
            <?php echo $success; ?>

            <!-- Wallet Stats -->
            <div class="row animate-fadeIn">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <div class="card-icon primary">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($points); ?></div>
                        <div class="card-title">Points Earned</div>
                        <a href="#" class="btn btn-sm btn-outline-primary">View History</a>
                    </div>
                </div>
                <div class="col-md-4 delay-1">
                    <div class="dashboard-card">
                        <div class="card-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-value">Ksh<?php echo htmlspecialchars(number_format($balance, 2)); ?></div>
                        <div class="card-title">Available Balance</div>
                        <button class="btn btn-sm btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#depositModal">Deposit</button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#withdrawModal" <?php echo $balance < 50 ? 'disabled title="Insufficient balance (Min Ksh 50)"' : ''; ?>>Withdraw</button>
                    </div>
                </div>
                <div class="col-md-4 delay-2">
                    <div class="dashboard-card">
                        <div class="card-icon accent">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-value"><?php echo htmlspecialchars($referral_count); ?></div>
                        <div class="card-title">Referrals</div>
                        <a href="referrals.php" class="btn btn-sm btn-outline-danger">Invite Friends</a>
                    </div>
                </div>
            </div>

            <!-- Deposit Form Modal -->
            <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="depositModalLabel">Deposit Funds</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="depositForm" class="deposit-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="deposit_amount" class="form-label">Amount (Ksh)</label>
                                    <input type="number" class="form-control" id="deposit_amount" name="amount" min="1" step="0.01" required>
                                    <small class="form-text text-muted">Minimum deposit: Ksh 1</small>
                                </div>
                                <div class="mb-3">
                                    <label for="deposit_phone" class="form-label">M-Pesa Phone Number</label>
                                    <input type="text" class="form-control" id="deposit_phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    <small class="form-text text-muted">Format: +2547XXXXXXXX</small>
                                </div>
                                <button type="submit" name="deposit" class="btn btn-primary w-100">Submit Deposit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Form Modal -->
            <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="withdrawModalLabel">Withdraw Funds</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" id="withdrawForm" class="withdrawal-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount (Ksh)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" min="50" step="0.01" required>
                                    <small class="form-text text-muted">Minimum withdrawal: Ksh 50</small>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">M-Pesa Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    <small class="form-text text-muted">Format: +2547XXXXXXXX</small>
                                </div>
                                <button type="submit" name="withdraw" class="btn btn-primary w-100">Submit Withdrawal</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Modal -->
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="successModalLabel">Success!</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="successMessage">
                            Transaction initiated successfully.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Modal -->
            <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="errorModalLabel">Error</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="errorMessage">
                            An error occurred.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Overview -->
            <div class="card mt-4 animate-fadeIn">
                <div class="card-body">
                    <h3 class="card-title">Wallet Overview</h3>
                    <p>Your current balance is <strong>Ksh<?php echo htmlspecialchars(number_format($balance, 2)); ?></strong>. Below is a summary of your recent transactions.</p>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare('SELECT created_at, type, amount, status FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
                            if ($stmt === false) {
                                error_log('Prepare failed for transactions: ' . $conn->error);
                                echo '<tr><td colspan="4" class="text-center">Unable to load transactions.</td></tr>';
                            } else {
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $description = $row['type'] === 'withdrawal' ? 'Withdrawal to M-Pesa' : 'Deposit via M-Pesa';
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) . '</td>';
                                        echo '<td>' . htmlspecialchars($description) . '</td>';
                                        echo '<td>Ksh' . htmlspecialchars(number_format($row['amount'], 2)) . '</td>';
                                        echo '<td>' . htmlspecialchars(ucfirst($row['status'])) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No transactions yet.</td></tr>';
                                }
                                $stmt->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="index1.php" class="mobile-nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="games.php" class="mobile-nav-item active">
            <i class="fas fa-gamepad"></i>
            <span>Games</span>
        </a>
        <a href="wallet1.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Earnings</span>
        </a>
        <a href="referrals.php" class="mobile-nav-item">
            <i class="fas fa-users"></i>
            <span>Refer</span>
        </a>
        <a href="settings.php" class="mobile-nav-item">
            <i class="fas fa-user"></i>
            <span>Account</span>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log out</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('main-content-expanded');
        }

        // Responsive navigation
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                mainContent.classList.remove('main-content-expanded');
            } else {
                sidebar.classList.add('active');
                mainContent.classList.remove('main-content-expanded');
            }
        }
        window.addEventListener('resize', handleResize);
        document.addEventListener('DOMContentLoaded', handleResize);

        // Animation observer
        const animateElements = document.querySelectorAll('.animate-fadeIn');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, { threshold: 0.1 });
        animateElements.forEach(element => observer.observe(element));

        // Modal functions
        function showSuccessModal(message) {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            document.getElementById('successMessage').innerText = message;
            modal.show();
        }

        function showErrorModal(message) {
            const modal = new bootstrap.Modal(document.getElementById('errorModal'));
            document.getElementById('errorMessage').innerText = message;
            modal.show();
        }
    </script>
</body>
</html>