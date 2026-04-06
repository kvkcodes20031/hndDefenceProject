<?php
header("Content-Type: application/json");
$provider_id = $_GET['provider_id'] ?? null;

if (!$provider_id) {
    echo json_encode(['success' => false, 'errors' => ['Provider ID is required']]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch provider name
    $lpStmt = $connect->prepare("SELECT company_name FROM logistics_providers WHERE provider_id = ?");
    $lpStmt->execute([$provider_id]);
    $provider = $lpStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all vehicles for this provider
    $stmt = $connect->prepare("SELECT * FROM company_vehicles WHERE provider_id = ? ORDER BY created_at DESC");
    $stmt->execute([$provider_id]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'company_name' => $provider ? $provider['company_name'] : 'Independent Carrier',
        'vehicles' => $vehicles
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
?>