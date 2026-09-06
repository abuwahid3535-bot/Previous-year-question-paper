<?php
session_start();

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

header('Content-Type: application/json');

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$staff_id = $_SESSION['staff_id'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {

    $document_name = trim($_POST['document_name'] ?? '');
    $document_type = trim($_POST['document_type'] ?? '');
    $file = $_FILES['document'];

    // Validate inputs
    if (empty($document_name) || empty($document_type)) {
        echo json_encode(['success' => false, 'message' => 'Document name and type are required']);
        exit();
    }

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed: ' . $file['error']]);
        exit();
    }

    // Allowed file types
    $allowed_types = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    // Allowed extensions
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx'];

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Reject obvious non-document files (double extension / .php/.html/.svg tricks)
    $basename = strtolower(pathinfo($file['name'], PATHINFO_FILENAME));
    if (preg_match('/\.(php|php[0-9]|phtml|pht|html|htm|svg|js|sh|exe)$/', $basename)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit();
    }

    // Both the client MIME and the extension must be allowed
    if (!in_array($file['type'], $allowed_types) || !in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'File type not allowed. Allowed: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX']);
        exit();
    }

    // Max file size (100MB)
    if ($file['size'] > 100 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 100MB']);
        exit();
    }

    // Create upload directory for this staff
    $upload_dir = __DIR__ . '/../uploads/staff_' . $staff_id;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid('doc_') . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . '/' . $filename;

    // Move file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {

        // Store relative path for web access
        $relative_path = 'uploads/staff_' . $staff_id . '/' . $filename;

        // Save to database
        $stmt = $conn->prepare("INSERT INTO documents (staff_id, document_name, document_type, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $staff_id, $document_name, $document_type, $relative_path, $file['size']);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => [
                    'id' => $stmt->insert_id,
                    'name' => $document_name,
                    'type' => $document_type,
                    'filename' => $filename,
                    'size' => $file['size']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save document: ' . $stmt->error]);
        }

        $stmt->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }

}

// Handle get documents
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {

    $stmt = $conn->prepare("SELECT * FROM documents WHERE staff_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }

    echo json_encode([
        'success' => true,
        'documents' => $documents
    ]);

    $stmt->close();
}

// Handle student count
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'student_count') {

    $result = $conn->query("SELECT COUNT(*) as count FROM students");
    $row = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'count' => intval($row['count'])
    ]);
}

// Handle delete document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {

    $doc_id = intval($_POST['document_id'] ?? 0);

    if ($doc_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit();
    }

    // Get document info
    $stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ? AND staff_id = ?");
    $stmt->bind_param("ii", $doc_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $file_path = $row['file_path'];

        // Delete file from filesystem
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete from database
        $stmt2 = $conn->prepare("DELETE FROM documents WHERE id = ? AND staff_id = ?");
        $stmt2->bind_param("ii", $doc_id, $staff_id);
        $stmt2->execute();
        $stmt2->close();

        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
    }

    $stmt->close();
}

$conn->close();
?>