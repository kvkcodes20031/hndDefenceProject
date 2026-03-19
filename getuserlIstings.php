<?php
header("Content-Type: application/json");
if (!isset($_SESSION['user_id'])) {
    session_start();
}
try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT p.*, PL.* FROM product_listing PL
          JOIN product p on PL.product_id = p.product_id
        WHERE PL.seller_id = ?
       ORDER BY PL.listing_id DESC
";
   
    $stmt = $connect->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
