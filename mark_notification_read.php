<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Not logged in']]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$notifId = $data['id'] ?? null;

if (!$notifId) {
    echo json_encode(['success' => false, 'errors' => ['Missing ID']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("UPDATE notification SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notifId, $_SESSION['user_id']]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>