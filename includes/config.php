<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_DOCKER_HOST', '127.0.0.1');
define('DB_DOCKER_PORT', 3307);
define('DB_LOCAL_PORT',  3306);
define('DB_NAME',        'bartoces_db');
define('DB_USER_ROOT',   'root');
define('DB_PASS_DOCKER', 'root');
define('DB_PASS_EMPTY',  '');
define('DB_CHARSET',     'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $candidates = [
        
        ['host' => DB_DOCKER_HOST, 'port' => DB_DOCKER_PORT, 'user' => DB_USER_ROOT, 'pass' => DB_PASS_DOCKER],
        
        ['host' => DB_DOCKER_HOST, 'port' => DB_DOCKER_PORT, 'user' => 'webdev',       'pass' => 'webdev123'],
        
        ['host' => '127.0.0.1',    'port' => DB_LOCAL_PORT,  'user' => DB_USER_ROOT, 'pass' => DB_PASS_EMPTY],
        
        ['host' => '127.0.0.1',    'port' => DB_LOCAL_PORT,  'user' => DB_USER_ROOT, 'pass' => DB_PASS_DOCKER]
    ];

    foreach ($candidates as $cand) {
        try {
            $dsn = "mysql:host={$cand['host']};port={$cand['port']};charset=" . DB_CHARSET;
            $conn = new PDO($dsn, $cand['user'], $cand['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 2,
            ]);

            
            $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->exec("USE `" . DB_NAME . "`");

            
            $check = $conn->query("SHOW TABLES LIKE 'menu_items'")->fetch();
            if (!$check) {
                $sqlFile = __DIR__ . '/../database/database.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    if ($sql) {
                        $conn->exec($sql);
                    }
                }
            }

            $pdo = $conn;
            return $pdo;
        } catch (PDOException $e) {
            
            continue;
        }
    }

    
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Could not connect to Docker MySQL or Local MySQL database.'
    ]));
}

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

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

function require_admin(bool $api = false): array {
    $user = require_login($api);
    if ($user['role'] !== 'admin') {
        if ($api) {
            json_response(['success' => false, 'message' => 'Access denied. Admins only.'], 403);
        } else {
            redirect('/');
        }
    }
    return $user;
}

function generate_order_number(): string {
    $year = date('Y');
    $random = str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "#BT-{$year}-{$random}";
}
