<?php
session_start();
header("Content-Type: application/json");

$coopId = $_GET['coop_id'] ?? null;
if (!$coopId) {
    echo json_encode(['success' => false, 'errors' => ['Cooperative ID missing']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("SELECT u.first_name, u.last_name, u.email, cm.joined_at 
                               FROM userstable u 
                               JOIN cooperative_member cm ON u.user_id = cm.farmer_id 
                               WHERE cm.cooperative_id = ? 
                               ORDER BY cm.joined_at ASC");
    $stmt->execute([$coopId]);
    echo json_encode(['success' => true, 'members' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>