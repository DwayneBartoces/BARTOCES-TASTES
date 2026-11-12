<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: SAVE RESERVATION
// ==============================================================================
// Handles table booking requests from the customer dining interface.
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

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

$name    = sanitize($data['name']             ?? '');
$phone   = sanitize($data['phone']            ?? '');
$date    = sanitize($data['reservation_date'] ?? '');
$time    = sanitize($data['reservation_time'] ?? '');
$guests  = sanitize($data['guests']           ?? '');
$seating = sanitize($data['seating']          ?? 'Indoor Main Dining');
$notes   = sanitize($data['special_notes']    ?? '');
$userId  = $_SESSION['user']['id']            ?? null;

if (empty($name) || empty($phone) || empty($date) || empty($time) || empty($guests)) {
    json_response(['success' => false, 'message' => 'Please fill in all required fields.'], 400);
}

$db = new DatabaseService();
$result = $db->createReservation([
    'user_id'          => $userId,
    'name'             => $name,
    'phone'            => $phone,
    'reservation_date' => $date,
    'reservation_time' => $time,
    'guests'           => $guests,
    'seating'          => $seating,
    'special_notes'    => $notes,
]);

json_response($result, $result['success'] ? 200 : 400);
