<?php
session_start();
header("Content-Type: application/json");

// Ensure the request comes from an authenticated session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized access']]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$targetUserId = $data['user_id'] ?? null;
$message = $data['message'] ?? '';

if (!$targetUserId || !$message) {
    echo json_encode(['success' => false, 'errors' => ['Missing user ID or message']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert notification with is_read defaulting to 0
    $stmt = $connect->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $stmt->execute([$targetUserId, $message]);

    echo json_encode(['success' => true, 'message' => 'Notification saved']);

} catch (PDOException $e) {
    // If table doesn't exist or other error
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>