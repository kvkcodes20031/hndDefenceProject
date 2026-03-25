<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'errors' => ['Missing order_id']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find active logistic operators
    $stmt = $connect->prepare("SELECT user_id FROM userstable WHERE role IN ('logistic operator', 'Logistics Agent', 'logistics provider')");
    $stmt->execute();
    $logisticsUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($logisticsUsers)) {
        echo json_encode(['success' => false, 'errors' => ['No logistics providers found']]);
        exit;
    }

    $notifStmt = $connect->prepare("INSERT INTO notification(user_id, notification_message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $message = "New Shipment Opportunity for Order #$orderId. <br><button onclick=\"acceptShipment($orderId, this)\" class='bg-orange-500 text-white text-xs px-2 py-1 rounded mt-1'>Accept Shipment</button>";

    foreach ($logisticsUsers as $uid) {
        $notifStmt->execute([$uid, $message]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>