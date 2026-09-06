<?php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

// Staff login required
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login as staff first']);
    exit();
}

$staff_id = $_SESSION['staff_id'];
$is_admin = !empty($_SESSION['staff_is_admin']);

// ---- APPROVE / REJECT (admin only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['approve', 'reject'])) {

    if (!$is_admin) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        $conn->close();
        exit();
    }

    $paper_id = intval($_POST['paper_id'] ?? 0);
    $status = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';

    if ($paper_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid paper ID']);
        $conn->close();
        exit();
    }

    $stmt = $conn->prepare("UPDATE question_papers SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $paper_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => ($status === 'approved' ? 'Question paper approved' : 'Question paper rejected')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Question paper not found']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// ---- DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $paper_id = intval($_POST['paper_id'] ?? 0);

    if ($paper_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid paper ID']);
        exit();
    }

    // Owner or admin can delete
    $sql = $is_admin
        ? "SELECT file_path FROM question_papers WHERE id = ?"
        : "SELECT file_path FROM question_papers WHERE id = ? AND uploaded_by = ?";

    $stmt = $conn->prepare($sql);
    if ($is_admin) {
        $stmt->bind_param("i", $paper_id);
    } else {
        $stmt->bind_param("ii", $paper_id, $staff_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $file_path = $row['file_path'];
        $absolute_path = __DIR__ . '/../' . $file_path;
        if (is_file($absolute_path)) {
            unlink($absolute_path);
        }
        $stmt->close();

        $del = $conn->prepare($is_admin
            ? "DELETE FROM question_papers WHERE id = ?"
            : "DELETE FROM question_papers WHERE id = ? AND uploaded_by = ?");
        if ($is_admin) {
            $del->bind_param("i", $paper_id);
        } else {
            $del->bind_param("ii", $paper_id, $staff_id);
        }
        $del->execute();
        $del->close();

        echo json_encode(['success' => true, 'message' => 'Question paper deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Question paper not found or you do not have permission']);
        $stmt->close();
    }
    $conn->close();
    exit();
}

// ---- LIST MY PAPERS ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {

    $sql = $is_admin
        ? "SELECT qp.*, s.full_name AS uploaded_by_name FROM question_papers qp LEFT JOIN staff s ON qp.uploaded_by = s.id ORDER BY qp.created_at DESC"
        : "SELECT qp.*, s.full_name AS uploaded_by_name FROM question_papers qp LEFT JOIN staff s ON qp.uploaded_by = s.id WHERE qp.uploaded_by = ? ORDER BY qp.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$is_admin) {
        $stmt->bind_param("i", $staff_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $papers = [];
    while ($row = $result->fetch_assoc()) {
        $papers[] = $row;
    }

    echo json_encode([
        'success' => true,
        'papers' => $papers
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

// ---- UPLOAD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['paper']) && ($_POST['action'] ?? '') === 'upload') {

    $title = trim($_POST['title'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $file = $_FILES['paper'];

    // Validate inputs
    if (empty($title) || empty($course) || empty($semester) || empty($year)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed: ' . $file['error']]);
        exit();
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
        exit();
    }

    if ($file['size'] > 100 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 100MB']);
        exit();
    }

    // Create upload directory if not exists
    $upload_dir = __DIR__ . '/../uploads/papers';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid('paper_') . '_' . time() . '.pdf';
    $filepath = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {

        $relative_path = 'uploads/papers/' . $filename;

        $stmt = $conn->prepare("INSERT INTO question_papers (title, course, semester, year, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $course, $semester, $year, $relative_path, $staff_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Question paper uploaded successfully',
                'paper' => [
                    'id' => $stmt->insert_id,
                    'title' => $title,
                    'course' => $course,
                    'semester' => $semester,
                    'year' => $year,
                    'file_path' => $relative_path
                ]
            ]);
        } else {
            unlink($filepath);
            echo json_encode(['success' => false, 'message' => 'Failed to save question paper: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
    $conn->close();
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
$conn->close();
?>