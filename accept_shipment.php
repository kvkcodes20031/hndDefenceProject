<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$providerId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'errors' => ['Missing order_id']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connect->beginTransaction();

    // Check if shipment exists
    $checkStmt = $connect->prepare("SELECT shipment_id FROM shipments WHERE order_id = ?");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->fetch()) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'errors' => ['Shipment already assigned to another provider. Just stay alert for more updates']]);
        exit;
    }

    // Get Seller Location (Pickup)
    $locStmt = $connect->prepare("
        SELECT p.location FROM order_items oi
        JOIN product_listing pl ON oi.listing_id = pl.listing_id
        JOIN profiletable p ON pl.seller_id = p.user_id
        WHERE oi.order_id = ? LIMIT 1
    ");
    $locStmt->execute([$orderId]);
    $pickupLocation = $locStmt->fetchColumn() ?: 'Unknown';

    // Create Shipment
    $shipStmt = $connect->prepare("INSERT INTO shipments (order_id, provider_id, pickup_location, delivery_location,delivery_status, delivery_cost, created_at) VALUES (?, ?, ?, 'Pending Pickup', 5000 "); 
    $shipStmt->execute([$orderId, $providerId, $pickupLocation]);

    // Update Order Status
    $connect->prepare("UPDATE orders SET order_status = 'In Transit' WHERE order_id = ?")->execute([$orderId]);

    // Notify Buyer
    $buyerStmt = $connect->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $buyerStmt->execute([$orderId]);
    $buyerId = $buyerStmt->fetchColumn();
    if ($buyerId) { 
        $connect->prepare("INSERT INTO notification (user_id, notification_message, is_read, created_at) VALUES (?, ?, 0, NOW())")->execute([$buyerId, "Your order #$orderId is now under delivery."]);
    }

    $connect->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>