<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: UPDATE STOCK
// ==============================================================================
// Updates inventory count and low-stock alert thresholds.
// Connected to Docker MySQL container (webdev_b_mysql on Port 3307).
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

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied. Admins only.'], 403);
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

$id        = intval($data['id']                  ?? 0);
$stock     = max(0, intval($data['stock']        ?? 0));
$threshold = max(0, intval($data['threshold']    ?? 5));

if ($id <= 0) {
    json_response(['success' => false, 'message' => 'Invalid menu item ID.'], 400);
}

$db = new DatabaseService();
$ok = $db->updateStock($id, $stock, $threshold);

if ($ok) {
    json_response([
        'success'   => true,
        'message'   => "Stock for item #{$id} updated to {$stock} units.",
        'stock'     => $stock,
        'threshold' => $threshold,
    ]);
} else {
    json_response(['success' => false, 'message' => 'Failed to update stock.'], 500);
}