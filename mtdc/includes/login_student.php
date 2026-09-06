<?php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter both email/student ID and password']);
        exit();
    }

    // Check if email or student ID exists in students table
    $stmt = $conn->prepare("SELECT id, full_name, email, student_id, password, is_verified FROM students WHERE email = ? OR student_id = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {

            // Login directly without OTP
            $_SESSION['student_id'] = $user['id'];
            $_SESSION['student_email'] = $user['email'];
            $_SESSION['student_name'] = $user['full_name'];
            $_SESSION['logged_in'] = true;

            // Prevent session fixation
            session_regenerate_id(true);

            echo json_encode([
                'status' => 'success',
                'name' => $user['full_name'],
                'email' => $user['email'],
                'student_id' => $user['student_id']
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