<?php
require_once '../db/connect.php';

header('Content-Type: application/json');

$course = trim($_GET['course'] ?? '');
$semester = trim($_GET['semester'] ?? '');
$year = trim($_GET['year'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = "SELECT id, title, course, semester, year, file_path, created_at FROM question_papers WHERE status = 'approved'";
$params = [];
$types = '';

if (!empty($course)) { $sql .= " AND course = ?"; $params[] = $course; $types .= 's'; }
if (!empty($semester)) { $sql .= " AND semester = ?"; $params[] = $semester; $types .= 's'; }
if (!empty($year)) { $sql .= " AND year = ?"; $params[] = $year; $types .= 's'; }
if (!empty($q)) { $sql .= " AND title LIKE ?"; $params[] = '%' . $q . '%'; $types .= 's'; }

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$papers = [];
while ($row = $result->fetch_assoc()) {
    $papers[] = $row;
}

// Pagination
$total = count($papers);
$limit = max(1, intval($_GET['limit'] ?? 6));
$page = max(1, intval($_GET['page'] ?? 1));
$total_pages = max(1, (int) ceil($total / $limit));
$page = min($page, $total_pages);

$offset = ($page - 1) * $limit;
$page_papers = array_slice($papers, $offset, $limit);

echo json_encode([
    'success' => true,
    'count' => $total,
    'total_pages' => $total_pages,
    'page' => $page,
    'limit' => $limit,
    'papers' => $page_papers
]);

$stmt->close();
$conn->close();
?>