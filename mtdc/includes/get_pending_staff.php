<?php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

// Only admin can view pending staff
if (!isset($_SESSION['staff_id']) || empty($_SESSION['staff_is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

$stmt = $conn->prepare("SELECT id, full_name, email, mobile, staff_id, department, designation, qualification, created_at, status FROM staff ORDER BY CASE status WHEN 'pending' THEN 0 ELSE 1 END, created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

$staff_list = [];
while ($row = $result->fetch_assoc()) {
    $staff_list[] = $row;
}

echo json_encode([
    'success' => true,
    'staff' => $staff_list
]);

$stmt->close();
$conn->close();
?>