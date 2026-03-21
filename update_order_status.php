<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$orderId = $data['order_id'] ?? null;
$status = $data['status'] ?? null;

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'errors' => ['Missing order_id or status']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update Status
    $stmt = $connect->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$status, $orderId]);

    // Notify Buyer
    $buyerStmt = $connect->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $buyerStmt->execute([$orderId]);
    $buyerId = $buyerStmt->fetchColumn();

    if ($buyerId) {
        $message = "Your order #$orderId status is now $status.";
        
        // If Accepted, add Pay Now buttons
        if ($status === 'Accepted') {
            $message .= " Please pay now. <br><a href='paymentpage.html?order_id=$orderId' class='inline-block bg-green-600 text-white text-xs px-2 py-1 rounded mt-1'>Pay Now</a> <a href='buyer_dashboard.html' class='inline-block bg-gray-500 text-white text-xs px-2 py-1 rounded mt-1 ml-1'>View Orders</a>";
        }

        $notifStmt = $connect->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $notifStmt->execute([$buyerId, $message]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>