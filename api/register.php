<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$full_name = trim($input['full_name'] ?? '');
$username = trim($input['username'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($full_name) || empty($username) || empty($phone) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate phone (Kenyan format: +254 or 07/01)
if (!preg_match('/^(\+254|0)[17]\d{8}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone number']);
    exit;
}

// Normalize phone to +254 format
if (substr($phone, 0, 1) === '0') {
    $phone = '+254' . substr($phone, 1);
}

// Validate email if provided
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Generate unique referral code
$referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

// Generate 6-digit verification code
$verification_code = sprintf("%06d", mt_rand(0, 999999));

try {
    // Check for duplicates
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR phone = ? OR email = ?');
    $stmt->execute([$username, $phone, $email ?: null]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Username, phone, or email already exists']);
        exit;
    }

    // Insert user
    $stmt = $pdo->prepare('INSERT INTO users (full_name, username, phone, email, password, referral_code) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$full_name, $username, $phone, $email ?: null, $password_hash, $referral_code]);

    $user_id = $pdo->lastInsertId();

    // Create wallet
    $stmt = $pdo->prepare('INSERT INTO wallet (user_id, balance) VALUES (?, 0.00)');
    $stmt->execute([$user_id]);

    // Store verification code (expires in 10 minutes)
    $stmt = $pdo->prepare('INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
    $stmt->execute([$user_id, $verification_code]);

    // Send SMS via Africa's Talking
    $sms_response = sendVerificationSMS($phone, $verification_code);

    if ($sms_response['status'] === 'success') {
        echo json_encode(['message' => 'Registration successful, SMS sent', 'user_id' => $user_id]);
    } else {
        // Roll back user creation if SMS fails (optional)
        $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$user_id]);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send SMS']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// Helper function to send SMS
function sendVerificationSMS($phone, $code) {
    $api_key = 'your_africas_talking_api_key'; // Replace with actual key
    $username = 'your_africas_talking_username'; // Replace with actual username
    $from = 'PROMO_SITE'; // Your shortcode or sender ID
    $message = "Your verification code is $code. It expires in 10 minutes.";

    $url = 'https://api.africastalking.com/version1/messaging';
    $data = [
        'username' => $username,
        'to' => $phone,
        'message' => $message,
        'from' => $from
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'apiKey: ' . $api_key
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        return ['status' => 'success', 'response' => json_decode($response, true)];
    }
    return ['status' => 'error', 'response' => json_decode($response, true)];
}
?>