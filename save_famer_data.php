<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized']]);
    exit;
}

$farm_name = trim($_POST['farm_name'] ?? '');
$farming_type = trim($_POST['farming_type'] ?? '');
$experienced_year = trim($_POST['experienced_year'] ?? '');

$errors = [];

if ($farm_name === '') $errors[] = "Farm name is required";
if ($farming_type === '') $errors[] = "Farming type is required";
if ($experienced_year === '') $errors[] = "Years of experience is required";

if (!empty($errors)) {
    echo json_encode(['success'=>false,'errors'=>$errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connect->prepare("
        INSERT INTO farmers (user_id, farm_name, farming_type, experienced_year)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $farm_name,
        $farming_type,
        $experienced_year
    ]);

    $connect->prepare("
        UPDATE userstable SET role_completed = 1 WHERE user_id = ?
    ")->execute([$_SESSION['user_id']]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Failed to save farmer data']]);
}
