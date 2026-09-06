<?php
// ============================================================
// API: place_order.php
// Accepts cart + customer info via POST, saves to MySQL using PDO
// Strictly adheres to Week 7: PHP Forms, Validation & Database CRUD
// ============================================================
require_once __DIR__ . '/../config.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

// Require login
if (empty($_SESSION['user'])) {
    json_response(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
}

// Parse incoming JSON body
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

// Validate & sanitize fields (PDF Week 7: trim, htmlspecialchars, strip_tags)
$customerName  = sanitize($data['customer_name']  ?? '');
$customerPhone = sanitize($data['customer_phone'] ?? '');
$orderType     = sanitize($data['order_type']     ?? 'dinein');
$paymentMethod = sanitize($data['payment_method'] ?? 'Cash');
$tableNumber   = sanitize($data['table_number']   ?? '');
$deliveryAddr  = sanitize($data['delivery_address'] ?? '');
$specialNotes  = sanitize($data['special_notes']  ?? '');
$cartItems     = $data['items'] ?? [];
$subtotal      = floatval($data['subtotal']    ?? 0);
$deliveryFee   = floatval($data['delivery_fee'] ?? 0);
$totalAmount   = floatval($data['total_amount'] ?? 0);
$userId        = $_SESSION['user']['id'] ?? null;

if (empty($customerName) || empty($customerPhone) || empty($cartItems)) {
    json_response(['success' => false, 'message' => 'Missing required order fields.'], 400);
}

// Generate unique order number
$orderNumber = generate_order_number();

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Insert into orders table using PDO prepared statement (PDF Week 7 slide 13)
    $orderSql = 'INSERT INTO `orders` 
        (`order_number`, `user_id`, `customer_name`, `customer_phone`, `order_type`, 
         `table_number`, `delivery_address`, `payment_method`, `subtotal`, `delivery_fee`, 
         `total_amount`, `special_notes`, `status`)
        VALUES 
        (:order_number, :user_id, :customer_name, :customer_phone, :order_type, 
         :table_number, :delivery_address, :payment_method, :subtotal, :delivery_fee, 
         :total_amount, :special_notes, "pending")';

    $stmt = $pdo->prepare($orderSql);
    $stmt->bindValue(':order_number',     $orderNumber);
    $stmt->bindValue(':user_id',          $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':customer_name',    $customerName);
    $stmt->bindValue(':customer_phone',   $customerPhone);
    $stmt->bindValue(':order_type',       $orderType);
    $stmt->bindValue(':table_number',     $tableNumber);
    $stmt->bindValue(':delivery_address', $deliveryAddr);
    $stmt->bindValue(':payment_method',   $paymentMethod);
    $stmt->bindValue(':subtotal',         $subtotal);
    $stmt->bindValue(':delivery_fee',     $deliveryFee);
    $stmt->bindValue(':total_amount',     $totalAmount);
    $stmt->bindValue(':special_notes',    $specialNotes);
    $stmt->execute();

    $orderId = (int)$pdo->lastInsertId();

    // Insert order items
    $itemSql = 'INSERT INTO `order_items` (`order_id`, `item_id`, `item_name`, `unit_price`, `quantity`, `addons`, `subtotal`)
        VALUES (:order_id, :item_id, :item_name, :unit_price, :quantity, :addons, :subtotal)';
    $stmtItem = $pdo->prepare($itemSql);

    // Stock update statement
    $stockSql = 'UPDATE `menu_items` SET stock = GREATEST(0, stock - :qty) WHERE id = :item_id';
    $stmtStock = $pdo->prepare($stockSql);

    foreach ($cartItems as $item) {
        $itemId    = intval($item['id']       ?? 0);
        $itemName  = sanitize($item['name']   ?? '');
        $unitPrice = floatval($item['price']  ?? 0);
        $qty       = intval($item['qty']      ?? 1);
        $addons    = sanitize($item['addons'] ?? '');
        $itemSub   = $unitPrice * $qty;

        $stmtItem->bindValue(':order_id',   $orderId, PDO::PARAM_INT);
        $stmtItem->bindValue(':item_id',    $itemId ?: null, $itemId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtItem->bindValue(':item_name',  $itemName);
        $stmtItem->bindValue(':unit_price', $unitPrice);
        $stmtItem->bindValue(':quantity',   $qty, PDO::PARAM_INT);
        $stmtItem->bindValue(':addons',     $addons);
        $stmtItem->bindValue(':subtotal',   $itemSub);
        $stmtItem->execute();

        // Decrement stock if itemId is valid
        if ($itemId > 0) {
            $stmtStock->bindValue(':qty',     $qty, PDO::PARAM_INT);
            $stmtStock->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $stmtStock->execute();
        }
    }

    $pdo->commit();

    json_response([
        'success'      => true,
        'order_number' => $orderNumber,
        'order_id'     => $orderId,
        'message'      => 'Order placed successfully!'
    ]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()], 500);
}
