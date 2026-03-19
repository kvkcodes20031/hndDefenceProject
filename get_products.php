<?php
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch products, newest first
    $sql = "SELECT pl.*, pd.product_name, pd.unit, pd.category, f.farm_name 
            FROM product_listing pl 
            LEFT JOIN product_definitions pd ON pl.product_id = pd.product_id
            LEFT JOIN famers f ON pl.farmer_id = f.farmer_id 
            ORDER BY pl.product_id DESC";
            
    $stmt = $connect->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'products' => $products]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}