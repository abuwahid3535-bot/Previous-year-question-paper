<?php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

// Only admin can approve/reject staff
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$staff_id = intval($input['staff_id'] ?? 0);
$action = trim($input['action'] ?? '');

if ($staff_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
    exit();
}

if ($action !== 'approve' && $action !== 'reject') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Do not allow admin to change own status
if ($staff_id === $_SESSION['staff_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot change your own status']);
    exit();
}

$status = $action === 'approve' ? 'approved' : 'rejected';

$stmt = $conn->prepare("UPDATE staff SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $staff_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $message = $action === 'approve' ? 'Staff member approved successfully' : 'Staff member rejected successfully';

    // Best-effort SMS notification to the staff member (Twilio trial may block)
    $stmt->close();
    $stmt = $conn->prepare("SELECT mobile, full_name FROM staff WHERE id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['mobile']) && file_exists(__DIR__ . '/twilio_config.php')) {
            require_once __DIR__ . '/twilio_config.php';
            $notifyText = $action === 'approve'
                ? 'Dear ' . $row['full_name'] . ', your Mother Theresa Degree College staff account has been APPROVED. You can now login.'
                : 'Dear ' . $row['full_name'] . ', your Mother Theresa Degree College staff account has been REJECTED. Please contact the office.';
            @sendNotificationSMS($row['mobile'], $notifyText);
        }
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff member not found']);
}

$conn->close();
?>