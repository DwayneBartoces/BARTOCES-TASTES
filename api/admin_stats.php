<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: ADMIN STATS
// ==============================================================================
// Provides real-time dashboard analytics and counters.
// Connected to Docker MySQL container (webdev_b_mysql on Port 3307).
// ==============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../src/Service/DatabaseService.php';

use App\Service\DatabaseService;

$db = new DatabaseService();
$stats = $db->getDashboardStats();

json_response([
    'success' => true,
    'data'    => $stats,
]);