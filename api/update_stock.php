<?php
// ============================================================
// API: update_stock.php
// Updates stock and threshold for menu items using PDO
// Strictly adheres to Week 7: PHP Forms, Validation & Database CRUD
// ============================================================
require_once __DIR__ . '/../config.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

// Admin only
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied. Admins only.'], 403);
}

// Get raw POST data
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['id']) || !isset($data['stock'])) {
    json_response(['success' => false, 'message' => 'Invalid data: id and stock are required.'], 400);
}

$id                 = intval($data['id']);
$stock              = intval($data['stock']);
$lowStockThreshold  = isset($data['low_stock_threshold']) ? intval($data['low_stock_threshold']) : 5;

if ($stock < 0) {
    json_response(['success' => false, 'message' => 'Stock cannot be negative.'], 400);
}

if ($lowStockThreshold < 0) {
    json_response(['success' => false, 'message' => 'Low stock threshold cannot be negative.'], 400);
}

try {
    $pdo = db();
    $sql = 'UPDATE `menu_items` SET stock = :stock, low_stock_threshold = :threshold WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':stock',     $stock, PDO::PARAM_INT);
    $stmt->bindValue(':threshold', $lowStockThreshold, PDO::PARAM_INT);
    $stmt->bindValue(':id',        $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() >= 0) {
        json_response([
            'success' => true,
            'message' => 'Stock updated successfully.'
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Item not found.'], 404);
    }
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}