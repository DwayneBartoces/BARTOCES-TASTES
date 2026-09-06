<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();
    $sql = 'SELECT id, name, price, category, image, badge, stock, low_stock_threshold, is_active FROM `menu_items` ORDER BY category, name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $inventory
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch inventory: ' . $e->getMessage()
    ]);
}