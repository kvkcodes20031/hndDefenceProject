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

    // Fetch cooperatives where user is leader OR member
    $stmt = $connect->prepare("
        SELECT DISTINCT c.cooperative_id, c.name, c.region 
        FROM cooperatives c 
        LEFT JOIN cooperative_member cm 
        ON c.cooperative_id = cm.cooperative_id 
        WHERE c.created_by = ? OR cm.farmer_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'cooperatives' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>