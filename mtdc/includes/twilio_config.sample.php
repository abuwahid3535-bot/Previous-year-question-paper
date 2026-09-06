<?php
// Twilio Configuration (SAMPLE - replace with your own credentials)
// Copy this file to twilio_config.php and fill in your Twilio values.
define('TWILIO_ACCOUNT_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_AUTH_TOKEN', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_SERVICE_SID', 'VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_FROM_NUMBER', '+1xxxxxxxxxx'); // Your Twilio SMS-capable number

function sendTwilioOTP($mobile) {
    $url = "https://verify.twilio.com/v2/Services/" . TWILIO_SERVICE_SID . "/Verifications";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'To' => '+91' . $mobile,
        'Channel' => 'sms'
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode == 201 || $httpCode == 200) {
        return ['success' => true, 'message' => 'OTP sent successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send OTP: ' . ($result['message'] ?? 'Unknown error')];
    }
}

function verifyTwilioOTP($mobile, $otp) {
    $url = "https://verify.twilio.com/v2/Services/" . TWILIO_SERVICE_SID . "/VerificationCheck";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'To' => '+91' . $mobile,
        'Code' => $otp
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode == 200 && $result['status'] === 'approved') {
        return ['success' => true, 'message' => 'OTP verified successfully'];
    } else {
        return ['success' => false, 'message' => 'Invalid OTP'];
    }
}
?>