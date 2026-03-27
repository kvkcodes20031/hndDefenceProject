<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$userIds = $_POST['user_ids'] ?? [];
if (empty($userIds)) {
    echo json_encode(['success' => false, 'errors' => ['No users selected']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify leadership
    $coopStmt = $connect->prepare("SELECT cooperative_id FROM cooperatives WHERE leader_id = ? LIMIT 1");
    $coopStmt->execute([$_SESSION['user_id']]);
    $coopId = $coopStmt->fetchColumn();

    if (!$coopId) {
        echo json_encode(['success' => false, 'errors' => ['Unauthorized leadership']]);
        exit;
    }

    $connect->beginTransaction();
    $insertStmt = $connect->prepare("INSERT INTO cooperative_members (cooperative_id, user_id, joined_at) VALUES (?, ?, NOW())");
    
    foreach ($userIds as $uid) {
        $insertStmt->execute([$coopId, $uid]);
    }

    $connect->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>