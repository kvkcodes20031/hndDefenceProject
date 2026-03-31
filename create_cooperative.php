<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'farmer') {
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

    $connect->beginTransaction();

    // Create the cooperative. We assume a table 'cooperatives' exists with created_by.
    $stmt = $connect->prepare("INSERT INTO cooperatives (name, description, region, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$coopName, $coopDesc, $region, $_SESSION['user_id']]);

    $coopId = $connect->lastInsertId();

    // Automatically add the creator as the 'Leader' in the members table
    $memberStmt = $connect->prepare("INSERT INTO cooperative_member (cooperative_id, farmer_id, role_in_coop) VALUES (?, ?, ?)");
    $memberStmt = $connect->prepare("INSERT INTO cooperative_members (cooperative_id, user_id, role_in_coop) VALUES (?, ?, ?)");
    $memberStmt->execute([$coopId, $_SESSION['user_id'], 'Leader']);

    $connect->commit();

    echo json_encode(['success' => true, 'coop_name' => $coopName]);
} catch (PDOException $e) {
    if (isset($connect) && $connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>