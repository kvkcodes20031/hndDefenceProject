<?php
session_start();
header("Content-Type: application/json");

$role = isset($_SESSION['role']) ? trim($_SESSION['role']) : '';
if (!isset($_SESSION['user_id']) || strtolower($role) !== 'buyer') {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized']]);
    exit;
}

$business_name = trim($_POST['business_name'] ?? '');
$buyer_type = trim($_POST['buyer_type'] ?? '');

$errors = [];
if ($business_name === '') $errors[] = "Business name is required";
if ($buyer_type === '') $errors[] = "Buyer type is required";

if (!empty($errors)) {
    echo json_encode(['success'=>false,'errors'=>$errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("
        INSERT INTO buyers (buyer_id, business_name, buyer_type)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $business_name,
        $buyer_type
    ]);

    $connect->prepare("
        UPDATE userstable SET role_completed = 1 WHERE user_id = ?
    ")->execute([$_SESSION['user_id']]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Failed to save buyer data'], 'pdoError'=>$e->getMessage()]);
}
