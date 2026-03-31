<?php
session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['coop_id'])) {
    $_SESSION['current_coop_id'] = $data['coop_id'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'errors' => ['No cooperative ID provided']]);
}
?>