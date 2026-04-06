<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized']]);
    exit;
}

$providerId = $_SESSION['user_id'];

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch shipments joined with order and product details
    $stmt = $connect->prepare("
        SELECT 
            s.*, 
            p.product_name, 
            oi.qauntity as quantity, 
            GROUP_CONCAT(p.product_name SEPARATOR ', ') as product_name, 
            SUM(oi.qauntity) as quantity,
            o.total_amount,
            p.unit
        FROM shipments s
        JOIN orders o ON s.order_id = o.order_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN product_listing pl ON oi.listing_id = pl.listing_id
        JOIN product p ON pl.product_id = p.product_id
        WHERE s.provider_id = ?
        GROUP BY s.shipment_id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$providerId]);
    echo json_encode(['success' => true, 'shipments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>
