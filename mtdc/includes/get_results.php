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

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

//
// STUDENT: view own results
//
if ($action === 'my' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['student_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit();
    }

    $student_db_id = $_SESSION['student_id'];

    $stmt = $conn->prepare("SELECT id, subject, exam_type, marks_obtained, max_marks, semester, course, remark, created_at FROM results WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $student_db_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode(['success' => true, 'results' => $results]);
    $stmt->close();
    $conn->close();
    exit();
}

// Everything below is admin-only
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

//
// ADMIN: list all results with student info
//
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query(
        "SELECT r.id, r.subject, r.exam_type, r.marks_obtained, r.max_marks, r.semester, r.course, r.remark, r.created_at,
                s.full_name, s.student_id
         FROM results r
         JOIN students s ON s.id = r.student_id
         ORDER BY r.created_at DESC"
    );

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $results = [];
    while ($row = $stmt->fetch_assoc()) {
        $results[] = $row;
    }

    echo json_encode(['success' => true, 'results' => $results]);
    $conn->close();
    exit();
}

// Convenience student lookup for the admin form (by student_id or email)
if ($action === 'find_student' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $term = trim($_GET['term'] ?? '');

    if (empty($term)) {
        echo json_encode(['success' => false, 'message' => 'Search term required']);
        exit();
    }

    $like = '%' . $term . '%';
    $stmt = $conn->prepare("SELECT id, full_name, email, student_id, course FROM students WHERE full_name LIKE ? OR student_id LIKE ? OR email LIKE ? OR course LIKE ? LIMIT 20");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode(['success' => true, 'students' => $students]);
    $stmt->close();
    $conn->close();
    exit();
}

//
// ADMIN: delete a result
//
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result_id = intval($input['result_id'] ?? 0);

    if ($result_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid result ID']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $result_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Result deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Result not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();
?>