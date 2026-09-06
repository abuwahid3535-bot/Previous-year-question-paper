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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    $full_name = trim($input['full_name'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $course = trim($input['course'] ?? '');
    $department = trim($input['department'] ?? '');
    $year_semester = trim($input['year_semester'] ?? '');

    if (empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit();
    }

    // Validate mobile
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        echo json_encode(['success' => false, 'message' => 'Invalid mobile number']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE students SET full_name = ?, mobile = ?, course = ?, department = ?, year_semester = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $full_name, $mobile, $course, $department, $year_semester, $_SESSION['student_id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update profile: ' . $stmt->error
        ]);
    }

    $stmt->close();
}

$conn->close();
?>