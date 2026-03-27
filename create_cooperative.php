<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$coopName = trim($_POST['coop_name'] ?? '');
$coopDesc = trim($_POST['coop_description'] ?? '');
$region = trim($_POST['region'] ?? '');

if ($coopName === '') {
    echo json_encode(['success' => false, 'errors' => ['Cooperative name is required']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the cooperative. We assume a table 'cooperatives' exists with leader_id.
    $stmt = $connect->prepare("INSERT INTO cooperatives (name, description, region, leader_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$coopName, $coopDesc, $region, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'coop_name' => $coopName]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>