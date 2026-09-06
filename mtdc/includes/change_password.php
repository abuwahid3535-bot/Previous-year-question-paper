<?php
session_start();
header('Content-Type: application/json');

// Detect logged-in role (student or staff)
$role = 'guest';
if (isset($_SESSION['student_id']) && isset($_SESSION['student_email'])) {
    $role = 'student';
} elseif (isset($_SESSION['staff_id']) && isset($_SESSION['staff_email'])) {
    $role = 'staff';
}

if ($role === 'guest') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
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

$table = $role === 'student' ? 'students' : 'staff';
$id = $role === 'student' ? $_SESSION['student_id'] : $_SESSION['staff_id'];

// Fetch current hash
$stmt = $conn->prepare("SELECT password FROM $table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (!password_verify($current_password, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        $stmt->close();
        $conn->close();
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Account not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Update password
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE $table SET password = ? WHERE id = ?");
$stmt->bind_param("si", $new_hash, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to change password']);
}

$stmt->close();
$conn->close();
?>