<?php
session_start();
require_once 'twilio_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$mobile = trim($input['mobile'] ?? '');
$type = trim($input['type'] ?? 'student');
$table = $type === 'staff' ? 'staff' : 'students';

if (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number']);
    exit();
}

// Database connection
$host = "localhost";
$dbname = "mother_theresa_college";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, 3307);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$conn->set_charset("utf8mb4");

// Mobile must NOT already be registered
$stmt = $conn->prepare("SELECT id FROM $table WHERE mobile = ?");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This mobile number is already registered. Please login.']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Generate server-side OTP (used as fallback when SMS is unavailable)
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Store in session
$_SESSION['reg_mobile'] = $mobile;
$_SESSION['reg_type'] = $table;
$_SESSION['reg_otp'] = $otp;
$_SESSION['reg_otp_expires'] = time() + 300;

// Try to send OTP via Twilio SMS
$otpResult = sendTwilioOTP($mobile);

if ($otpResult['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent to +91 ' . $mobile
    ]);
} else {
    // SMS not available (e.g. Twilio trial unverified number).
    // Keep the flow working by returning the demo OTP for display.
    echo json_encode([
        'success' => true,
        'message' => 'SMS delivery failed (' . $otpResult['message'] . ')',
        'demo_otp' => $otp,
        'demo_mode' => true
    ]);
}

$conn->close();
?>