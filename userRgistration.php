<?php
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $errors = [];

    $phone = trim($_POST['phone_number'] ?? '');
    $email_raw = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($phone === '') $errors[] = "Phone number is required";
    if ($email_raw === '') $errors[] = "Email is required";
    if ($password === '') $errors[] = "Password is required";
    if ($confirm === '') $errors[] = "Confirm password is required";
    if ($password !== '' && $password !== $confirm) $errors[] = "Passwords do not match";
    if ($role === '') $errors[] = "Role is required";

    $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
    if ($email === false) $errors[] = "Invalid email";

    if (!empty($errors)) {
        echo json_encode(['success'=>false,'errors'=>$errors]);
        exit;
    }

    // Check if email or phone already exists
    $checkStmt = $connect->prepare("SELECT user_id FROM userstable WHERE email = ? OR phone_number = ? LIMIT 1");
    $checkStmt->execute([$email, $phone]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success'=>false,'errors'=>['Account with this email or phone number already exists']]);
        exit;
    }

    // Insert ONLY account data
    $stmt = $connect->prepare("
        INSERT INTO userstable (phone_number, email, password_hash, role)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $phone,
        $email,
        password_hash($password, PASSWORD_BCRYPT),
        $role
    ]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false,'errors'=>['Account creation failed']]);
}
