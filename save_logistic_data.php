<?php
session_start();
header("Content-Type: application/json");

// Ensure user is logged in and role is 'logistic_operator'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'logistic_operator') {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized access']]);
    exit;
}

// Collect POST inputs
$company_name = trim($_POST['company_name'] ?? '');
$vehicle_capacity = trim($_POST['vehicle_capacity'] ?? '');
$vehicle_type = trim($_POST['vehicle_type'] ?? '');

$errors = [];
if ($company_name === '') $errors[] = "Company name is required";
if ($vehicle_capacity === '' || !is_numeric($vehicle_capacity)) $errors[] = "Vehicle capacity must be a number";
if ($vehicle_type === '') $errors[] = "Vehicle type is required";

if (!empty($errors)) {
    echo json_encode(['success'=>false,'errors'=>$errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert data into logistic_operators table
    $stmt = $connect->prepare("
        INSERT INTO logistics_providers (provider_id, company_name, vehicle_capacity, vehicle_type)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $company_name,
        $vehicle_capacity,
        $vehicle_type
    ]);

    // Update userstable to mark role as completed
    $connect->prepare("
        UPDATE userstable SET role_completed = 1 WHERE user_id = ?
    ")->execute([$_SESSION['user_id']]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Failed to save logistic operator data: ' . $e->getMessage()]]);
    
}
