<?php
session_start();
require_once 'twilio_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    $entered_otp = trim($input['otp'] ?? '');
    $mobile = trim($input['mobile'] ?? '');

    $stored_mobile = $_SESSION['reset_mobile'] ?? '';

    if (empty($entered_otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter the OTP']);
        exit();
    }

    if ($mobile !== $stored_mobile) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please start over.']);
        exit();
    }

    // Verify server-stored OTP (demo fallback when SMS was not delivered)
    $saved_otp = $_SESSION['reset_otp'] ?? '';
    $saved_expires = (int) ($_SESSION['reset_otp_expires'] ?? 0);
    $expired = !empty($saved_otp) && time() > $saved_expires;

    if (!empty($saved_otp) && $entered_otp === $saved_otp && !$expired) {

        // Mark OTP as verified in session
        $_SESSION['reset_otp_verified'] = true;
        unset($_SESSION['reset_otp'], $_SESSION['reset_otp_expires']);

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
        exit();
    }

    // Fall back to Twilio verification (covers codes delivered by real SMS)
    $verifyResult = verifyTwilioOTP($mobile, $entered_otp);

    if ($verifyResult['success']) {

        // Mark OTP as verified in session
        $_SESSION['reset_otp_verified'] = true;

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => $expired ? 'OTP has expired. Please request a new one.' : $verifyResult['message']
        ]);

    }
}
?>