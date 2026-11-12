<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: SAVE CONTACT MESSAGE
// ==============================================================================
// Handles customer concierge inquiries and messages.
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

$name    = sanitize($data['name']    ?? '');
$email   = trim($data['email']       ?? '');
$subject = sanitize($data['subject'] ?? 'General Inquiry');
$message = sanitize($data['message'] ?? '');
$userId  = $_SESSION['user']['id']   ?? null;

if (empty($name) || empty($email) || empty($message)) {
    json_response(['success' => false, 'message' => 'Please fill in all required fields.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.'], 400);
}

$db = new DatabaseService();
$result = $db->createContactMessage([
    'user_id' => $userId,
    'name'    => $name,
    'email'   => $email,
    'subject' => $subject,
    'message' => $message,
]);

json_response($result, $result['success'] ? 200 : 400);
