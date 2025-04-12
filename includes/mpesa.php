<?php
class MpesaAPI {
    private $consumer_key = 'your_consumer_key'; // Replace with actual key
    private $consumer_secret = 'your_consumer_secret'; // Replace with actual secret
    private $shortcode = 'your_shortcode'; // Your M-Pesa shortcode
    private $passkey = 'your_passkey'; // Lipa na M-Pesa passkey
    private $callback_url = 'https://your-domain.com/api/mpesa_callback.php';
    private $access_token = null;

    // Get OAuth access token
    public function getAccessToken() {
        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'; // Use sandbox for testing
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $data = json_decode($response, true);
            $this->access_token = $data['access_token'];
            return $this->access_token;
        }
        throw new Exception('Failed to get access token: ' . $response);
    }

    // Initiate STK Push
    public function initiateSTKPush($phone, $amount, $transaction_id) {
        if (!$this->access_token) {
            $this->getAccessToken();
        }

        $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // Sandbox URL
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone, // Phone number (e.g., +254712345678)
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callback_url,
            'AccountReference' => 'Activation_' . $transaction_id,
            'TransactionDesc' => 'Account activation fee'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }
}
?>