<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized: User not logged in']]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['order_id'] ?? null;
$sellerId = $_SESSION['user_id'];
$newStatus = $data['status'] ?? null;

if (!$orderId || !$newStatus) {
    echo json_encode(['success' => false, 'errors' => ['Missing order ID or status']]);
    exit;
}else {


// Validate new status
$allowedStatuses = ['Accepted', 'Rejected'];

if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'errors' => ['Invalid status provided']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connect->beginTransaction();
    
    
   
    $verifyStmt = $connect->prepare("
        SELECT o.user_id AS buyer_id, p.product_name
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN product_listing pl ON oi.listing_id = pl.listing_id
        JOIN product p ON pl.product_id = p.product_id
        WHERE o.order_id = ? AND pl.seller_id = ?
        LIMIT 1
    ");
    $verifyStmt->execute([$orderId, $sellerId]); 
    $orderInfo = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderInfo) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'errors' => ['Unauthorized: You are not the seller of this order or order does not exist.']]);
        exit;
    }

    // Update the order status
    $stmt = $connect->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // Send notification to the buyer
    
    $notificationMessage = "Your order #".$orderId. " for " . $orderInfo['product_name'] . " has been " . strtolower($newStatus) . " by the seller.";
    $notificationStmt = $connect->prepare("INSERT INTO notification (user_id, notification_message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $notificationStmt->execute([$orderInfo['buyer_id'], $notificationMessage]);

    $connect->commit();

    echo json_encode(['success' => true, 'message' => 'Order status updated and buyer notified.']);
} catch (PDOException $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}}
?>