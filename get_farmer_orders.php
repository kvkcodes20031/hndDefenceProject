<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized: User not logged in']]);
    exit;
}

$farmerId = $_SESSION['user_id'];

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch orders where the logged-in user is the seller
    // Join with products and users to get product name and buyer name
    $stmt = $connect->prepare("
        SELECT 
            o.order_id, 
            o.order_status, 
            o.total_amount,
            oi.quantity,
            p.product_name,
            p.unit,
            u.first_name AS buyer_name,
            u.user_id AS buyer_id
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN product_listings p ON oi.listing_id = p.listing_id
        JOIN userstable u ON o.buyer_id = u.user_id
        WHERE p.user_id = ? -- Assuming product_listings has a user_id for the seller
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$farmerId]);
    echo json_encode(['success' => true, 'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>