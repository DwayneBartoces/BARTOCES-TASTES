<?php
// ============================================================
// BARTOCES TASTES - DATABASE CONFIGURATION & PDO CONNECTION
// Based on Week 7: PHP Forms, Validation & Database CRUD
// ============================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');          // XAMPP default: no password
define('DB_NAME',    'bartoces_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 * Strict adherence to PDO initialization in course module Week 7.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = DB_HOST;
        $db   = DB_NAME;
        $user = DB_USER;
        $pass = DB_PASS;
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=" . DB_CHARSET, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}

/**
 * Sanitize a string value for safe output/storage (PDF Week 7: trim, strip_tags, htmlspecialchars).
 */
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe redirect helper.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Ensure the user is logged in (PHP session). 
 */
function require_login(bool $api = false): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user'])) {
        if ($api) {
            json_response(['success' => false, 'message' => 'Unauthorized. Please log in.'], 401);
        } else {
            redirect('login.php');
        }
    }
    return $_SESSION['user'];
}

/**
 * Ensure logged-in user is admin.
 */
function require_admin(bool $api = false): array {
    $user = require_login($api);
    if ($user['role'] !== 'admin') {
        if ($api) {
            json_response(['success' => false, 'message' => 'Access denied. Admins only.'], 403);
        } else {
            redirect('index.php');
        }
    }
    return $user;
}

/**
 * Generate a unique order number.
 */
function generate_order_number(): string {
    $year = date('Y');
    $random = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "#BT-{$year}-{$random}";
}
