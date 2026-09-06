<?php
session_start();
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_email'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
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

$ident = $_GET['email'] ?? '';

if (!empty($ident) && $ident !== $_SESSION['student_email']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: cannot view another user profile']);
    exit();
}

$stmt = $conn->prepare("SELECT id, full_name, email, student_id, mobile, course, department, year_semester, profile_pic FROM students WHERE id = ?");
$stmt->bind_param("i", $_SESSION['student_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $profile = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
}

$stmt->close();
$conn->close();
?>