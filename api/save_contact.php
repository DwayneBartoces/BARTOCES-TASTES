<?php
// ============================================================
// API: save_contact.php
// Saves a contact message to MySQL using PDO
// Strictly adheres to Week 7: PHP Forms, Validation & Database CRUD
// ============================================================
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

// PDF Week 7: trim & filter_var validation
$name    = sanitize($data['name']    ?? '');
$email   = trim($data['email']       ?? '');
$subject = sanitize($data['subject'] ?? '');
$message = sanitize($data['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    json_response(['success' => false, 'message' => 'Name, email, and message are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
}

try {
    $pdo = db();
    $sql = 'INSERT INTO `contacts` (`name`, `email`, `subject`, `message`) VALUES (:name, :email, :subject, :message)';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':name',    $name);
    $stmt->bindValue(':email',   $email);
    $stmt->bindValue(':subject', $subject);
    $stmt->bindValue(':message', $message);
    $stmt->execute();

    json_response([
        'success' => true,
        'message' => "Thank you, {$name}! Your message has been received. We'll get back to you soon."
    ]);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Failed to save message: ' . $e->getMessage()], 500);
}
