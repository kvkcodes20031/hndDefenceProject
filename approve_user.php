<?php
session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$userId = $data['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'errors' => ['Missing User ID']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connect->beginTransaction();

    // 1. Update verification status
    $stmt = $connect->prepare("UPDATE userstable SET role_completed = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);

    // 2. Send Notification
    $msg = "Congratulations! Your identity has been verified. You can now list products and use all premium features.";
    $notif = $connect->prepare("INSERT INTO notification (user_id, notification_message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $notif->execute([$userId, $msg]);

    $connect->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>