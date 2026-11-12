<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: PLACE ORDER
// ==============================================================================
// Handles customer order placement for Dine In, Take Out, and Delivery.
// Connected to Docker MySQL container (webdev_b_mysql on Port 3307).
// Decrements stock levels and generates #BT-2026-XXXXX order reference.
// ==============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../src/Service/DatabaseService.php';

use App\Service\DatabaseService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (empty($_SESSION['user'])) {
    json_response(['success' => false, 'message' => 'Unauthorized. Please sign in.'], 401);
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

$customerName  = sanitize($data['customer_name']  ?? '');
$customerPhone = sanitize($data['customer_phone'] ?? '');
$orderType     = sanitize($data['order_type']     ?? 'dinein');
$paymentMethod = sanitize($data['payment_method'] ?? 'Cash');
$tableNumber   = null;
$deliveryAddr  = sanitize($data['delivery_address'] ?? '');
$specialNotes  = sanitize($data['special_notes']  ?? '');
$cardNumber    = sanitize($data['card_number']    ?? '');
$cartItems     = $data['items'] ?? [];
$subtotal      = floatval($data['subtotal']    ?? 0);
$deliveryFee   = floatval($data['delivery_fee'] ?? 0);
$totalAmount   = floatval($data['total_amount'] ?? 0);
$userId        = $_SESSION['user']['id'] ?? null;

if (!empty($cardNumber)) {
    $masked = '•••• ' . substr($cardNumber, -4);
    $paymentMethod .= " ({$masked})";
}

if (empty($customerName) || empty($cartItems)) {
    json_response(['success' => false, 'message' => 'Missing required order fields.'], 400);
}

if (empty($customerPhone) && !empty($_SESSION['user']['phone'])) {
    $customerPhone = sanitize($_SESSION['user']['phone']);
}

$db = new DatabaseService();
$result = $db->placeOrder([
    'user_id'          => $userId,
    'customer_name'    => $customerName,
    'customer_phone'   => $customerPhone,
    'order_type'       => $orderType,
    'table_number'     => $tableNumber,
    'delivery_address' => $deliveryAddr,
    'payment_method'   => $paymentMethod,
    'subtotal'         => $subtotal,
    'delivery_fee'     => $deliveryFee,
    'total_amount'     => $totalAmount,
    'special_notes'    => $specialNotes,
], $cartItems);

json_response($result, $result['success'] ? 200 : 400);
