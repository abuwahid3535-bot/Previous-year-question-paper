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
    $result = $conn->query(
        "SELECT d.id, d.document_name, d.document_type, d.file_path, d.file_size, d.status, d.uploaded_at,
                s.full_name AS staff_name
         FROM documents d
         JOIN staff s ON s.id = d.staff_id
         ORDER BY d.status = 'pending' DESC, d.uploaded_at DESC"
    );

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        $conn->close();
        exit();
    }

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    echo json_encode(['success' => true, 'documents' => $documents]);
    $conn->close();
    exit();
}

if ($action === 'ping' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true]);
    $conn->close();
    exit();
}

if (($_SERVER['REQUEST_METHOD'] === 'POST') && in_array($action, ['approve', 'reject'], true)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $doc_id = intval($input['document_id'] ?? 0);

    if ($doc_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit();
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';

    $stmt = $conn->prepare("UPDATE documents SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $doc_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Document ' . ($action === 'approve' ? 'approved' : 'rejected')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $doc_id = intval($input['document_id'] ?? 0);

    if ($doc_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit();
    }

    $stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $file_path = $row['file_path'];
        $base_path = __DIR__ . '/../';
        if (!empty($file_path) && file_exists($base_path . $file_path)) {
            unlink($base_path . $file_path);
        }

        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
$conn->close();
?>