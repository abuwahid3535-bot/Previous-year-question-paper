<?php
session_start();

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

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);
    $mobile = trim($input['mobile'] ?? '');
    $new_password = trim($input['password'] ?? '');

    // Check if OTP was verified
    $otp_verified = $_SESSION['reset_otp_verified'] ?? false;
    $stored_mobile = $_SESSION['reset_mobile'] ?? '';
    $reset_type = $_SESSION['reset_type'] ?? 'students';
    $table = $reset_type === 'staff' ? 'staff' : 'students';

    if (!$otp_verified || $mobile !== $stored_mobile) {
        echo json_encode(['success' => false, 'message' => 'OTP not verified. Please start over.']);
        exit();
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in database
    $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE mobile = ?");
    $stmt->bind_param("ss", $hashed_password, $mobile);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {

        // Clear all reset session data
        unset($_SESSION['reset_mobile']);
        unset($_SESSION['reset_type']);
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_otp_expires']);
        unset($_SESSION['reset_otp_verified']);
        unset($_SESSION['reset_user_id']);

        echo json_encode([
            'success' => true,
            'message' => 'Password reset successful'
        ]);

    } else {

        // Check if user exists
        $check = $conn->prepare("SELECT id FROM $table WHERE mobile = ?");
        $check->bind_param("s", $mobile);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // User exists but update didn't work - return error
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update password. Please try again.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        $check->close();
    }

    $stmt->close();
    $conn->close();
}
?>