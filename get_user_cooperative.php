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

    // Fetch cooperative where the logged-in user is the leader
    $stmt = $connect->prepare("SELECT cooperative_id, name, description, region FROM cooperatives WHERE created_by = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cooperative = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cooperative) {
        echo json_encode(['success' => true, 'cooperative' => $cooperative]);
    } else {
        echo json_encode(['success' => false, 'errors' => ['No cooperative found for this user.']]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>