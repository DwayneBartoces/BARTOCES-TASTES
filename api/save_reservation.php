<?php
// ============================================================
// API: save_reservation.php
// Saves a table reservation to MySQL using PDO
// Strictly adheres to Week 7: PHP Forms, Validation & Database CRUD
// ============================================================
require_once __DIR__ . '/../config.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (empty($_SESSION['user'])) {
    json_response(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

// PDF Week 7: Sanitization and validation
$name    = sanitize($data['name']     ?? '');
$phone   = sanitize($data['phone']    ?? '');
$date    = sanitize($data['date']     ?? '');
$time    = sanitize($data['time']     ?? '');
$guests  = sanitize($data['guests']   ?? '');
$seating = sanitize($data['seating']  ?? 'Indoor Main Dining');
$notes   = sanitize($data['notes']    ?? '');
$userId  = $_SESSION['user']['id']    ?? null;

if (empty($name) || empty($phone) || empty($date) || empty($time) || empty($guests)) {
    json_response(['success' => false, 'message' => 'Missing required reservation fields.'], 400);
}

try {
    $pdo = db();
    $sql = 'INSERT INTO `reservations` 
        (`user_id`, `name`, `phone`, `reservation_date`, `reservation_time`, `guests`, `seating`, `special_notes`, `status`)
        VALUES 
        (:user_id, :name, :phone, :reservation_date, :reservation_time, :guests, :seating, :special_notes, "pending")';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id',          $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $stmt->bindValue(':name',             $name);
    $stmt->bindValue(':phone',            $phone);
    $stmt->bindValue(':reservation_date', $date);
    $stmt->bindValue(':reservation_time', $time);
    $stmt->bindValue(':guests',           $guests);
    $stmt->bindValue(':seating',          $seating);
    $stmt->bindValue(':special_notes',    $notes);
    $stmt->execute();

    $resId = (int)$pdo->lastInsertId();

    json_response([
        'success'        => true,
        'reservation_id' => $resId,
        'message'        => "Table reserved for {$guests} on {$date} at {$time}. We'll see you soon!"
    ]);
} catch (PDOException $e) {
    json_response(['success' => false, 'message' => 'Failed to save reservation: ' . $e->getMessage()], 500);
}
