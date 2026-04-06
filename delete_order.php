<?php


session_start();  

  if(!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized: User not logged in"]);
    exit; 
  }

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $order_id = $data['order_id'] ?? $_POST["order_id"] ?? null;
    
    $pdo = new PDO("mysql:host=localhost;dbname=farmglobedatabase", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    $sql = "DELETE FROM orders WHERE order_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    echo json_encode(["success" => true, "message" => "Order deleted successfully"]);
  }
  catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
    exit;
  }

    
