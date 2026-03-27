<?php
session_start();
header("Content-Type: application/json");

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'errors' => ['User ID missing']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("SELECT u.first_name, u.last_name, u.email, u.role, p.id_document FROM userstable u JOIN profiletable p ON u.user_id = p.user_id WHERE u.user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) echo json_encode(['success' => true, 'user' => $user]);
    else echo json_encode(['success' => false, 'errors' => ['User document not found']]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>