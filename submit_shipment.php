<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['User not logged in']]);
    exit;
}

$userId = $_SESSION['user_id'];

// Prioritize Session but allow POST fallback
$orderId = $_SESSION['order_id'] ?? $_POST['order_id'] ?? null;
$providerId = $_POST['provider_id'] ?? $_POST['provider_id'] ?? null;
$quantityDelivered = $_POST['quantity_delivered'] ?? 0;
$pickup = trim($_POST['pickup_location'] ?? '');
$delivery = trim($_POST['delivery_location'] ?? '');
$deliveryDate = $_POST['delivery_date'] ?? null;
$directions = trim($_POST['meetup_directions'] ?? '');

if (!$orderId) {
    echo json_encode(['success' => false, 'errors' => ['No order selected']]);
    exit;
}

if (!$providerId|| empty($pickup) || empty($delivery) || empty($directions) || !$deliveryDate) {
    echo json_encode(['success' => false, 'errors' => ['Please fill in all required fields.']]);
    exit;
}


try {

    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connect->beginTransaction();

    // Validate that the user is the seller and quantity_delivered is within limits
    $checkStmt = $connect->prepare("
        SELECT oi.qauntity as total_ordered 
        FROM order_items oi
        JOIN product_listing pl ON oi.listing_id = pl.listing_id
        WHERE oi.order_id = ? AND pl.seller_id = ?

    ");
    $checkStmt->execute([$orderId, $userId]);
    $orderInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$orderInfo || $orderInfo['total_ordered'] === null) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'errors' => ['Unauthorized: You are not the seller of this order, or the order does not exist.']]);
        exit;
    }

    if ($quantityDelivered > $orderInfo['total_ordered']) {
        $connect->rollBack();
        echo json_encode(['success' => false, 'errors' => ["Quantity delivered ($quantityDelivered) cannot exceed the total ordered quantity (" . $orderInfo['total_ordered'] . ")."]]);
        exit;
    }

    $sql = "INSERT INTO shipments (order_id, provider_id, pickup_location, delivery_location,  meetup_directions, delivery_date, delivery_status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        $orderId,
        $providerId,
        $pickup,
        $delivery,
        $directions, 
        $deliveryDate
    ]);

    // Notify the Logistics Provider
    $notificationMessage = "New Transport Request: Pickup at $pickup for delivery to $delivery. Check your dashboard for details.";
    $notifStmt = $connect->prepare("INSERT INTO notification (user_id, notification_message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $notifStmt->execute([$providerId, $notificationMessage]);

    $connect->commit();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if (isset($connect) && $connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => ['Database Error: ' . $e->getMessage()]]);
}
?>