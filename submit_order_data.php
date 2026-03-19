    <?php
    ini_set('display_errors',1);
    error_reporting(E_ALL);

    session_start();
    header("Content-Type: application/json");

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success'=>false,'errors'=>['User not logged in']]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $totalAmount = $data['total_amount'] ?? null;
    $orderStatus = $data['order_status'] ?? 'Pending';
    $items = $data['items'] ?? [];

    if (!$totalAmount || empty($items)) {
        echo json_encode(['success'=>false,'errors'=>['Invalid order data']]);
        exit;
    }

    try{

    $connect = new PDO("mysql:host=localhost;dbname=farmglobedatabase","root","");
    $connect->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

    $connect->beginTransaction();

    $userId = $_SESSION['user_id'];

    $stmt = $connect->prepare(
    "INSERT INTO orders (user_id,total_amount,order_status)
    VALUES (?,?,?)"
    );

    $stmt->execute([$userId,$totalAmount,$orderStatus]);

    $orderId = $connect->lastInsertId();

    $itemStmt = $connect->prepare(
    "INSERT INTO order_items (order_id,listing_id,qauntity,agreed_price)
    VALUES (?,?,?,?)"
    );

    foreach($items as $item){

    $itemStmt->execute([
        $orderId,
        $item['id'],
        $item['quantity'],
        $item['price']
    ]);

    }

    $connect->commit();

    echo json_encode([
        'success'=>true,
        'order_id'=>$orderId
    ]);

    }catch(PDOException $e){

    $connect->rollBack();

    echo json_encode([
        'success'=>false,
        'errors'=>['Database error: '.$e->getMessage()]
    ]);

    }
    ?>