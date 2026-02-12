<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'errors'=>['Unauthorized']]);
    exit;
}


$location = trim($_POST['location'] ?? '');
$dob = trim($_POST['date_of_birth'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$profile_type = trim($_POST['profile_type'] ?? '');
$rating = trim($_POST['rating'] ?? '');

$errors = [];

if ($location === '') $errors[] = "Location is required";
if ($dob === '') $errors[] = "Date of birth is required";
if ($gender === '') $errors[] = "Gender is required";

$photo_path = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $fileName = uniqid('p_', true) . '_' . basename($_FILES['profile_photo']['name']);
    $target = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
        $photo_path = $target;
    } else {
        $errors[] = "Failed to upload photo";
    }
}

if (!empty($errors)) {
    echo json_encode(['success'=>false,'errors'=>$errors]);
    exit;
}

try {
$connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
$connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $connect->prepare("
    INSERT INTO profiletable (user_id, profile_type, location, date_of_birth, gender, rating, profile_photo)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([$_SESSION['user_id'], $profile_type, $location, $dob, $gender, $rating, $photo_path]);

$connect->prepare("
    UPDATE userstable SET profile_completed=1 WHERE user_id=?
")->execute([$_SESSION['user_id']]);

// Fetch the role fresh from the database to ensure redirection works
$roleStmt = $connect->prepare("SELECT role FROM userstable WHERE user_id = ?");
$roleStmt->execute([$_SESSION['user_id']]);
$role = $roleStmt->fetchColumn();

echo json_encode(['success'=>true, 'role'=>$role]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Database error: ' . $e->getMessage()]]);
}
