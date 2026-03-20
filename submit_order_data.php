    <?php
    ini_set('display_errors', 0);
    error_reporting(0);

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
    $orderReference = 'ORD-' . uniqid();

    $stmt = $connect->prepare(
    "INSERT INTO orders (user_id,order_references,total_amount,order_status)
    VALUES (?,?,?,?)"
    );

    $stmt->execute([$userId, $orderReference, $totalAmount, $orderStatus]);

    $orderId = $connect->lastInsertId();

    $itemStmt = $connect->prepare(
    "INSERT INTO order_items (order_id,listing_id,qauntity,agreed_price)
    VALUES (?,?,?,?)"
    );

    // Prepare statements for notifications
    $buyerStmt = $connect->prepare("SELECT first_name, last_name FROM userstable WHERE user_id = ?");
    $buyerStmt->execute([$userId]);
    $buyerData = $buyerStmt->fetch(PDO::FETCH_ASSOC);
    $buyerName = $buyerData ? $buyerData['first_name'] . ' ' . $buyerData['last_name'] : 'A Buyer';

    $sellerStmt = $connect->prepare("SELECT seller_id FROM product_listing WHERE listing_id = ?");
    $notifStmt = $connect->prepare("INSERT INTO notification (user_id,notification_message, is_read) VALUES (?, ?, 0)");
    $notifiedSellers = []; 

    foreach($items as $item){

    $itemStmt->execute([
        $orderId,
        $item['id'],
        $item['quantity'],
        $item['price']
    ]);

    // Notification Logic: Identify seller and notify
    $sellerStmt->execute([$item['id']]);
    $sellerId = $sellerStmt->fetchColumn();

    if ($sellerId && !in_array($sellerId, $notifiedSellers)) {
        $message = "You have an order from " . $buyerName . ". Please confirm it.";
        $notifStmt->execute([$sellerId, $message]);
        $notifiedSellers[] = $sellerId;
    }

    }

    $connect->commit();

    echo json_encode([
        'success'=>true,
        'order_id'=>$orderId
    ]);

    }catch(PDOException $e){

   

    echo json_encode([
        'success'=>false,
        'errors'=>['Database error: '.$e->getMessage()]
    ]);

    }
    ?>