<?php
header("content-type: application/json");

try {
    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $errors = [];

    // Required fields check (EMPTY, not isset)
     $first_name = trim($_POST['first_name'] ?? '');
    if ($first_name=='') {
        $errors[] = "First name is required";
    }

    $last_name = trim($_POST['last_name'] ?? '');
    if ($last_name=='') {
        $errors[] = "Last name is required";
    }

    
    if (empty($_POST['phone_number'])) {
        $errors[] = "Phone number is required";
    }




    // Email validation
    $email = trim($_POST['email'] ?? '');
    if ($email=='') {
        $errors[] = "Email is required";
    }

   else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            $errors[] = "Please enter a valid email";
        }
    }

    // Password
    if (empty($_POST['password_hash'])) {
        $errors[] = "Password is required";
    }

    // Role
    if (empty($_POST['role'])) {
        $errors[] = "Role is required";
    }

    // display errors if any
    if ($errors) {
        foreach ($errors as $error) {
             echo json_encode(['success'=>false,'errors'=>$errors]);
        }
        exit; 
    }

    //Insert only if validation passed
    $sql = $connect->prepare("
        INSERT INTO userstable 
        (first_name, last_nam, phone_number, email, password_hash, role) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([
        $first_name,
        $last_name,
        $_POST['phone_number'],
        $email,
        password_hash($_POST['password_hash'], PASSWORD_BCRYPT),
        $_POST['role']
    ]);

    echo "New record created successfully";

} catch (PDOException $e) {
    echo json_encode(["Connection failed: " . $e->getMessage()]);
}
    ?>
