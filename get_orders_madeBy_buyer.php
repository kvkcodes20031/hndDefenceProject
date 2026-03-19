<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['User not logged in']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['user_id'];

    // Fetch items bought by this user
    $sql = "SELECT 
                oi.order_id,
                oi.qauntity,
                o.total_amount,
                oi.agreed_price,
                o.order_date,
                o.order_status,
                p.product_name,
                p.unit,
                pl.image_path,
                concat(s.first_name, ' ', s.last_name) as seller_name,
                s.phone_number as seller_phone
            FROM order_items oi
            JOIN product_listing pl ON oi.listing_id = pl.listing_id
            JOIN product p ON pl.product_id = p.product_id
            JOIN orders o ON oi.order_id = o.order_id
            JOIN userstable s ON pl.seller_id = s.user_id

            WHERE o.user_id = ?
            ORDER BY o.order_date DESC
            ";

    $stmt = $connect->prepare($sql);
    $stmt->execute([$userId]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'orders' => $sales]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>