<?php
session_start();
header("Content-Type: application/json");

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'errors'=>['User not logged in']]);
    exit;
}

try{
$connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase","root","");
$connect->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

$sql="SELECT * FROM userstable WHERE user_id = ?";

$stmt = $connect->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'errors' => ['User not found']]);
    exit;
}

echo json_encode($user);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>
