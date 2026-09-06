<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "mother_theresa_college";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, 3307);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['mobile'] ?? '');
    $staff_id = trim($input['staff_id'] ?? '');
    $department = trim($input['department'] ?? '');
    $designation = trim($input['designation'] ?? '');
    $qualification = trim($input['qualification'] ?? '');
    $password = trim($input['password'] ?? '');
    $confirm_password = trim($input['confirmPassword'] ?? '');

    // Validate input
    if (empty($full_name) || empty($email) || empty($mobile) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }

    // Validate mobile (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        echo json_encode(['success' => false, 'message' => 'Invalid mobile number']);
        exit();
    }

    // Validate password length
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM staff WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Check if mobile already exists
    $stmt = $conn->prepare("SELECT id FROM staff WHERE mobile = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile number already registered']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert staff into database (status = pending until admin approves)
    $stmt = $conn->prepare("INSERT INTO staff (full_name, email, mobile, staff_id, department, designation, qualification, password, is_verified, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending')");
    $stmt->bind_param("ssssssss", $full_name, $email, $mobile, $staff_id, $department, $designation, $qualification, $hashed_password);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Registration submitted! An administrator will approve your account before you can login.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Registration failed: ' . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();
}
?>