<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: ADMIN INVENTORY
// ==============================================================================
// Returns live menu items inventory list with stock counts.
// Connected to Docker MySQL container (webdev_b_mysql on Port 3307).
// ==============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../src/Service/DatabaseService.php';

use App\Service\DatabaseService;

$db = new DatabaseService();
$items = $db->getInventory();

json_response([
    'success' => true,
    'data'    => $items,
]);