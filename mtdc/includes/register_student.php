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

    $fullname = trim($_POST['fullname'] ?? '');
    $studentId = trim($_POST['studentId'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    // Validate inputs
    if (empty($fullname) || empty($studentId) || empty($email) || empty($mobile) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit();
    }

    // Validate mobile
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number']);
        exit();
    }

    // Validate password
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
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

    // Check if student ID already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID already registered']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Check if mobile already exists
    $stmt = $conn->prepare("SELECT id FROM students WHERE mobile = ?");
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

    // Verify mobile OTP (sent before submission)
    $entered_otp = trim($_POST['otp'] ?? '');
    $saved_otp = $_SESSION['reg_otp'] ?? '';
    $saved_expires = (int) ($_SESSION['reg_otp_expires'] ?? 0);
    $saved_mobile = $_SESSION['reg_mobile'] ?? '';
    $saved_type = $_SESSION['reg_type'] ?? '';

    if ($saved_type !== 'students' || $saved_mobile !== $mobile || empty($entered_otp) || $entered_otp !== $saved_otp) {
        echo json_encode(['success' => false, 'message' => 'Please verify your mobile OTP first']);
        $conn->close();
        exit();
    }

    if (time() > $saved_expires) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new OTP.']);
        $conn->close();
        exit();
    }

    // OTP verified - clear session data
    unset($_SESSION['reg_mobile'], $_SESSION['reg_type'], $_SESSION['reg_otp'], $_SESSION['reg_otp_expires']);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert student into database
    $stmt = $conn->prepare("INSERT INTO students (full_name, student_id, email, mobile, course, department, year_semester, password, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssssssss", $fullname, $studentId, $email, $mobile, $course, $department, $year, $hashedPassword);

    if ($stmt->execute()) {

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'id' => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Registration failed: ' . $stmt->error
        ]);

    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>