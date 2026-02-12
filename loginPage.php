<?php
session_start();
header("Content-Type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $errors = [];

    // Get & sanitize inputs
    $identifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['loginPassword'] ?? '';

    // Validate identifier
    if ($identifier === '') {
        $errors[] = "Email or Phone number is required";
    }

    // Validate password
    if ($password === '') {
        $errors[] = "Password is required";
    }

    // Return validation errors
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Fetch user

$sql = $connect->prepare("
    SELECT user_id, email, password_hash, profile_completed, role, role_completed
    FROM userstable
    WHERE email = ? OR phone_number = ?
    LIMIT 1
");

    $sql->execute([$identifier, $identifier]);
    $user = $sql->fetch(PDO::FETCH_ASSOC);

    // Verify user exists AND password is correct
    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'errors' => ['Invalid credentials.']]);
        exit;
    }

    // Normalize role (handle potential database inconsistencies)
    $role = trim($user['role']);
    if (strtolower($role) === 'logistic operator') {
        $role = 'logistic_operator';
    }

    // Login success
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true,
        'role' => $role,
        'profile_completed' => $user['profile_completed'],
        'role_completed' => $user['role_completed']
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'errors' => ["A server error occurred."]]);
}
?>
