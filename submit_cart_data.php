<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Login in first'], 'redirect' => 'login.html']);
    exit;
}

$userId = $_SESSION['user_id'];
$status = $_POST['status'] ?? 'Active';

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if an active cart already exists for the user
    $checkStmt = $connect->prepare("SELECT id FROM cart WHERE user_id = ? AND status = ? LIMIT 1");
    $checkStmt->execute([$userId, $status]);
    $existingCart = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCart) {
        echo json_encode([
            'success' => true, 
            'message' => 'Cart already exists', 
            'cart_id' => $existingCart['id']
            
        ]);
    } else {
        // Create new cart
        $stmt = $connect->prepare("INSERT INTO cart(user_id, status) VALUES (?, ?)");
        $stmt->execute([$userId, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Cart created successfully', 'cart_id' => $connect->lastInsertId()]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}           
