<?php
session_start();
header("Content-Type: application/json");

$role = isset($_SESSION['role']) ? trim($_SESSION['role']) : '';
if (!isset($_SESSION['user_id']) || ($role !== 'input supplier' && $role !== 'supplier')) {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized']]);
    exit;
}

$supplier_type = $_POST['supplier_type'] ?? '';

$errors = [];
if ($supplier_type === '') $errors[] = "Supplier type is required";

if (!empty($errors)) {
    echo json_encode(['success'=>false,'errors'=>$errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("
        INSERT INTO suppliers (supplier_id, supplier_type)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $supplier_type
    ]);

    $connect->prepare("
        UPDATE userstable SET role_completed = 1 WHERE user_id = ?
    ")->execute([$_SESSION['user_id']]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Failed to save supplier data'], 'pdoError'=>$e->getMessage()]);
}
