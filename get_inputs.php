<?php
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch products, newest first
    $sql = "SELECT * FROM product 

            -- join product_listing pl on p.product_id = pl.product_id
            -- join userstable u on pl.user_id = u.user_id

            where type = 'input' ";

            
    $stmt = $connect->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}