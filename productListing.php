<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['You must be logged in to list a product.']]);
    exit;
}


// if (!$user || strtolower($user['role']) !== 'farmer') {
//         echo json_encode([

//             "success" => false,
//             "errors" => ["You are not eligible to list a product."]
//         ]);
//         exit;
//     }

$productDefinitionId = trim($_POST['product_id'] ?? '');
$pricePerUnit = trim($_POST['price_per_unit'] ?? '');
$quantityAvailable = trim($_POST['quantity_available'] ?? '');
$harvestDate = trim($_POST['harvest_date'] ?? '');
$status = trim($_POST['status'] ?? 'Available');
$sellerNote = trim($_POST['seller_note'] ?? '');

$errors = [];
if (empty($productDefinitionId)) $errors[] = "Please select a product to list";
if (empty($pricePerUnit) || !is_numeric($pricePerUnit)) $errors[] = "Valid price is required";
if (empty($quantityAvailable) || !is_numeric($quantityAvailable)) $errors[] = "Valid quantity is required";

$imagePath = null;
if (isset($_FILES['productPicture']) && $_FILES['productPicture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['productPicture']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('prod_', true) . '.' . $fileExtension;
    $target = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['productPicture']['tmp_name'], $target)) {
        $imagePath = $target;
    } else {
        $errors[] = "Failed to upload image";
    }
} else {
    $errors[] = "Product image is required";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("INSERT INTO product_listing(seller_id, product_id, price_per_unit, quantity_available, harvest_date, status, sellernote, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $productDefinitionId,
        $pricePerUnit,
        $quantityAvailable,
        $harvestDate ?: null,
        $status,
        $sellerNote,
        $imagePath
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}