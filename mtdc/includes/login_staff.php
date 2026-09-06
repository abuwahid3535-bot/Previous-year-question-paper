<?php
session_start();
require_once '../db/connect.php';
require_once 'twilio_config.php';

header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['staffEmail'] ?? '');
    $password = trim($_POST['staffPassword'] ?? '');

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter both email/staff ID and password']);
        exit();
    }

    // Check if email or staff ID exists in staff table
    $stmt = $conn->prepare("SELECT id, full_name, email, mobile, password, is_verified, status, is_admin FROM staff WHERE email = ? OR staff_id = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {

            // Check account status (admin approval required)
            if ($user['status'] === 'pending') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Your account is pending administrator approval. Please wait for approval before logging in.'
                ]);
                exit();
            }

            if ($user['status'] === 'rejected') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Your account has been rejected by the administrator. Please contact the office.'
                ]);
                exit();
            }

            // Login directly
            $_SESSION['staff_id'] = $user['id'];
            $_SESSION['staff_email'] = $user['email'];
            $_SESSION['staff_name'] = $user['full_name'];
            $_SESSION['staff_is_admin'] = intval($user['is_admin']);
            $_SESSION['logged_in'] = true;

            // Prevent session fixation
            session_regenerate_id(true);

            echo json_encode([
                'status' => 'success',
                'name' => $user['full_name'],
                'email' => $user['email'],
                'staff_id' => $user['id'],
                'is_admin' => intval($user['is_admin'])
            ]);

        } else {
            // Wrong password
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email or password'
            ]);
        }

    } else {
        // Email not found
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password'
        ]);
    }

    $stmt->close();
    $conn->close();
}
?>