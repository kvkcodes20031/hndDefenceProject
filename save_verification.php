<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized access']]);
    exit;
}

$errors = [];
$id_type = trim($_POST['id_type'] ?? '');
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

if ($id_type === '') $errors[] = "ID type is required.";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $checkStmt = $connect->prepare("SELECT * FROM identity_verification WHERE user_id = ?");
    $checkStmt->execute([$_SESSION['user_id']]);
    while ($checkStmt->fetch()) {
        $updateStmt = $connect->prepare("UPDATE identity_verification SET document_image = ?, id_type = ?, verification_status = 'Pending', verified_at = NOW() WHERE user_id = ?");
        $updateStmt->execute([$id_doc_path, $id_type, $_SESSION['user_id']]);

        $updatenotificationStmt = $connect->prepare("INSERT INTO notification (user_id, notification_message, created_at) VALUES (?, ?, NOW())");
        $updatenotificationStmt->execute([1, "Identity verification updated by user ID: " . $_SESSION['user_id']]); // Assuming  has user_id = 1   
        echo json_encode(['success' => true, 'user_id' => $_SESSION['user_id']]);
        exit;
    }

    // Store the file path in the user profile or a separate verifications table
    $stmt = $connect->prepare("INSERT INTO identity_verification (document_image, id_type, verification_status, user_id, verified_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$id_doc_path, $id_type, "Pending", $_SESSION['user_id']]);

    // notification logic to alert admins of new verification submissions
    $notificationStmt = $connect->prepare("INSERT INTO notification (user_id, notification_message, created_at) VALUES (?, ?, NOW())");
    $notificationStmt->execute([1, "New identity verification submitted by user ID: " . $_SESSION['user_id']]); 

    echo json_encode(['success' => true, 'user_id' => $_SESSION['user_id']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
?>