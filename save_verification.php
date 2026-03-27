<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized access']]);
    exit;
}

$errors = [];
$id_doc_path = null;

if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/verifications/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileInfo = pathinfo($_FILES['id_document']['name']);
    $extension = strtolower($fileInfo['extension']);
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = "Only PDF and Image files are allowed.";
    } else {
        $fileName = 'id_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
        $target = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['id_document']['tmp_name'], $target)) {
            $id_doc_path = $target;
        } else {
            $errors[] = "Failed to save the uploaded file.";
        }
    }
} else {
    $errors[] = "Please select a file to upload.";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Store the file path in the user profile or a separate verifications table
    $stmt = $connect->prepare("UPDATE identity_verification SET document_image = ? WHERE user_id = ?");
    $stmt->execute([$id_doc_path, $_SESSION['user_id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>