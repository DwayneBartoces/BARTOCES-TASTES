<?php
// ============================================================
// API: admin_stats.php
// Returns real-time order and reservation counts for admin bar
// ============================================================
require_once __DIR__ . '/../config.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

// Only admin can access stats
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

try {
    $pdo = db();

    // Total orders count
    $orderStmt  = $pdo->query('SELECT COUNT(*) AS cnt FROM `orders`');
    $orderCount = (int)($orderStmt ? $orderStmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0);

    // Total reservations count
    $resStmt  = $pdo->query('SELECT COUNT(*) AS cnt FROM `reservations`');
    $resCount = (int)($resStmt ? $resStmt->fetch(PDO::FETCH_ASSOC)['cnt'] : 0);

    // Recent orders (last 10)
    $ordersStmt = $pdo->prepare(
        'SELECT `order_number`, `customer_name`, `total_amount`, `status`, `created_at`
         FROM `orders` ORDER BY `created_at` DESC LIMIT 10'
    );
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent reservations (upcoming)
    $resUpcomingStmt = $pdo->prepare(
        'SELECT `name`, `reservation_date`, `reservation_time`, `guests`, `status`
         FROM `reservations` ORDER BY `reservation_date` ASC, `reservation_time` ASC LIMIT 10'
    );
    $resUpcomingStmt->execute();
    $recentReservations = $resUpcomingStmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success'             => true,
        'order_count'         => $orderCount,
        'reservation_count'   => $resCount,
        'recent_orders'       => $recentOrders,
        'recent_reservations' => $recentReservations
    ]);
} catch (PDOException $e) {
    json_response([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], 500);
}
