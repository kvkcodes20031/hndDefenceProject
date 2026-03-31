<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the leader's cooperative ID
    $coopStmt = $connect->prepare("SELECT cooperative_id FROM cooperatives WHERE created_by = ? LIMIT 1");
    $coopStmt->execute([$_SESSION['user_id']]);
    $coop = $coopStmt->fetch(PDO::FETCH_ASSOC);

    if (!$coop) {
        echo json_encode(['success' => false, 'errors' => ['Cooperative not found or you are not a leader']]);
        exit;
    }

    $coopId = $coop['cooperative_id'];

    // Select farmers who aren't the leader and aren't already in this coop
    $stmt = $connect->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email 
        FROM userstable u 
        WHERE (u.role = 'farmer' OR u.role = 'Farmer') 
        AND u.user_id != ? 
        AND u.user_id NOT IN (SELECT farmer_id FROM cooperative_member WHERE cooperative_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $coopId]);
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>