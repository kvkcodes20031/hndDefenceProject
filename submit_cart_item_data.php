<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'errors' => ['Login in first'],
        'redirect' => 'login.html'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$status = $_POST['status'] ?? 'Active';

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Determine cart id
    if (!empty($_POST['cart_id'])) {
        $cartId = $_POST['cart_id'];
    } else {
        $cartStmt = $connect->prepare("SELECT id FROM cart WHERE user_id = ? AND status = 'Active' LIMIT 1");
        $cartStmt->execute([$userId]);
        $cart = $cartStmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {
            echo json_encode(['success' => false, 'errors' => ['No active cart found']]);
            exit;
        }

        $cartId = $cart['id'];
    }

    // Receive correct POST fields (MATCHES YOUR JS)
    $productId = $_POST['product_id'] ?? null;
    $quantity  = $_POST['quantity'] ?? null;
    $price     = $_POST['price_at_time'] ?? null;

    // Proper validation with exit
    if ($productId === null || $productId === '') {
        echo json_encode(['success' => false, 'errors' => ['Product ID is required']]);
        exit;
    }

    if ($quantity === null || $quantity === '') {
        echo json_encode(['success' => false, 'errors' => ['Quantity is required']]);
        exit;
    }

    if ($price === null || $price === '') {
        echo json_encode(['success' => false, 'errors' => ['Price is required']]);
        exit;
    }

    // Check if item already exists
    $checkStmt = $connect->prepare("SELECT quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $checkStmt->execute([$cartId, $productId]);
    $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;
        $updateStmt = $connect->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
        $updateStmt->execute([$newQuantity, $cartId, $productId]);
    } else {
        $itemStmt = $connect->prepare("INSERT INTO cart_items(cart_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        $itemStmt->execute([$cartId, $productId, $quantity, $price]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cart_id' => $cartId
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'errors' => ['Database error: ' . $e->getMessage()]
    ]);
}
?>