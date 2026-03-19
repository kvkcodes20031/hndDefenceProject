<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['You must be logged in to define a product.']]);
    exit;
}

$productName = trim($_POST['product_name'] ?? '');
$category = trim($_POST['category'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$description = trim($_POST['description'] ?? '');

$errors = [];
if (empty($productName)) $errors[] = "Product name is required";
if (empty($category)) $errors[] = "Caregory is required";
if (empty($unit)) $errors[] = "Unit is required";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);

    exit;
}


    
try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $checkuser = $connect->prepare("SELECT role FROM userstable WHERE user_id = ?");
    $checkuser->execute([$_SESSION['user_id']]);
    $user = $checkuser->fetch(PDO::FETCH_ASSOC);
    if ( $user['role'] == 'input supplier') {
        $stmt = $connect->prepare("INSERT INTO product ( product_name, category, unit, description, type) VALUES ( ?, ?, ?, ?, ?)");
        $stmt->execute([
        $productName,
        $category,
        $unit,
        $description,
        'input'
    ]);
       
    }
     if ( $user['role'] == 'farmer') {
        
    $stmt = $connect->prepare("INSERT INTO product ( product_name, category, unit, description, type) VALUES ( ?, ?, ?, ?, ?)");
    $stmt->execute([
        $productName,
        $category,
        $unit,
        $description,
        'produce'
    ]);
     }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}