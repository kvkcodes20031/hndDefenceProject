<?php
session_start();
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // This assumes a system_errors table exists for logging application errors
    $stmt = $connect->prepare("SELECT error_message, created_at FROM system_errors ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $errors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'system_errors' => $errors]);
} catch (PDOException $e) {
    echo json_encode(['success' => true, 'system_errors' => [], 'message' => 'Logs table not found.']);
}
?>