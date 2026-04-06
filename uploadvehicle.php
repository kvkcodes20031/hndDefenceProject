<?php
session_start();
header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized: User not logged in']]);
    exit;
}

$vehicleType = $_POST['vehicle_type'] ?? null;
$vehicleCapacity = $_POST['vehicle_capacity'] ?? null;
$region = $_POST['region'] ?? null;
$subdivision = $_POST['subdivision'] ?? null;

if (!$vehicleType || !$vehicleCapacity || !$region || !$subdivision) {
    echo json_encode(['success' => false, 'errors' => ['Missing required fields']]);
    exit;
}

// Handle optional image upload
$photoPath = null;

if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/vehicles/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid('v_', true) . '_' . basename($_FILES['vehicle_image']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $targetPath)) {
        $photoPath = $targetPath;
    }
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("
        INSERT INTO company_vehicles
        (provider_id, vehicle_type, vehicle_capacity, region, subdivision, sample_image, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $vehicleType,
        $vehicleCapacity,
        $region,
        $subdivision,
        $photoPath
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}