<?php
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Join company_vehicles with logistics_providers to get the company name
    $sql = "SELECT cv.*, lp.company_name 
            FROM company_vehicles cv
            JOIN logistics_providers lp ON cv.provider_id = lp.provider_id
            ORDER BY cv.created_at DESC";
            
    $stmt = $connect->query($sql);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'vehicles' => $vehicles]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>