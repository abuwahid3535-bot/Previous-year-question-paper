<?php
session_start();
header('Content-Type: application/json');

// Check if student is logged in
if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_email'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {

    $file = $_FILES['profile_pic'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit();
    }

    // Detect the actual image type server-side (never trust client MIME/extension)
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP images allowed']);
        exit();
    }

    $allowed_images = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp'
    ];

    $detected_type = $image_info[2];

    if (!isset($allowed_images[$detected_type])) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP images allowed']);
        exit();
    }

    $file_ext = $allowed_images[$detected_type];

    // Max file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit();
    }

    // Create uploads directory if not exists
    $upload_dir = __DIR__ . '/../uploads/profile_pics';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $filename = 'profile_' . uniqid() . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . '/' . $filename;

    // Move file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {

        // Store relative path for web access
        $relative_path = 'uploads/profile_pics/' . $filename;

        // Update database
        $stmt = $conn->prepare("UPDATE students SET profile_pic = ? WHERE id = ?");
        $stmt->bind_param("si", $relative_path, $_SESSION['student_id']);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'profile_pic' => $relative_path
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database: ' . $stmt->error]);
        }

        $stmt->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }

}

$conn->close();
?>