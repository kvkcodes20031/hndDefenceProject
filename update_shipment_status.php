<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$shipmentId = $data['shipment_id'] ?? null;
$status = $data['status'] ?? null; // e.g., 'In Transit' (Accepted) or 'Rejected'

if (!$shipmentId || !$status) {
    echo json_encode(['success' => false, 'errors' => ['Missing parameters']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connect->beginTransaction();

    // Update shipment status
    $stmt = $connect->prepare("UPDATE shipments SET delivery_status = ? WHERE shipment_id = ? AND provider_id = ?");
    $stmt->execute([$status, $shipmentId, $_SESSION['user_id']]);

    // If accepted, we update the main order status as well
    if ($status === 'In Transit') {
        $orderStmt = $connect->prepare("SELECT order_id FROM shipments WHERE shipment_id = ?");
        $orderStmt->execute([$shipmentId]);
        $orderId = $orderStmt->fetchColumn();
        
        if ($orderId) {
            $updOrder = $connect->prepare("UPDATE orders SET order_status = 'In Transit' WHERE order_id = ?");
            $updOrder->execute([$orderId]);
            
            // Notify Buyer
            $buyerIdStmt = $connect->prepare("SELECT user_id FROM orders WHERE order_id = ?");
            $buyerIdStmt->execute([$orderId]);
            $buyerId = $buyerIdStmt->fetchColumn();
            if ($buyerId) {
                $connect->prepare("INSERT INTO notification (user_id, notification_message, created_at) VALUES (?, ?, NOW())")
                        ->execute([$buyerId, "Logistics provider has accepted your shipment for Order #$orderId."]);
            }
        }
    }

    $connect->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($connect->inTransaction()) $connect->rollBack();
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>