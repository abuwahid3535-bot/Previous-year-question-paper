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

// Admin-only
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC");
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
    $conn->close();
    exit();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message_id = intval($input['message_id'] ?? 0);

    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message_id = intval($input['message_id'] ?? 0);

    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();
?>