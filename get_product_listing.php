<?php
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT 
    pl.listing_id,
    pl.price_per_unit,
    pl.quantity_available,
    pl.harvest_date,
    pl.status,
    pl.image_path,

    p.product_name,
    p.category,
    p.unit,

    f.farm_name
    

FROM product_listing pl

JOIN product p 
    ON pl.product_id = p.product_id


JOIN famers f 
    ON pl.farmer_id = f.farmer_id

WHERE pl.status = 'Available';
";
            
    $stmt = $connect->query($sql);
    echo json_encode(['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
