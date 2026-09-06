<?php
session_start();
header('Content-Type: application/json');

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

// Only admin can add results
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$student_db_id = intval($input['student_db_id'] ?? 0);
$subject = trim($input['subject'] ?? '');
$exam_type = trim($input['exam_type'] ?? 'Internal');
$marks_obtained = floatval($input['marks_obtained'] ?? 0);
$max_marks = floatval($input['max_marks'] ?? 0);
$semester = trim($input['semester'] ?? '');
$course = trim($input['course'] ?? '');
$remark = trim($input['remark'] ?? '');

if ($student_db_id <= 0 || empty($subject) || $marks_obtained < 0 || $max_marks <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields correctly']);
    exit();
}

if ($marks_obtained > $max_marks) {
    echo json_encode(['success' => false, 'message' => 'Marks obtained cannot exceed maximum marks']);
    exit();
}

// Verify student exists
$stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
$stmt->bind_param("i", $student_db_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

$updated_by = $_SESSION['staff_id'];

$stmt = $conn->prepare("INSERT INTO results (student_id, subject, exam_type, marks_obtained, max_marks, semester, course, remark, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issddsssi", $student_db_id, $subject, $exam_type, $marks_obtained, $max_marks, $semester, $course, $remark, $updated_by);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Result added successfully', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add result: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>