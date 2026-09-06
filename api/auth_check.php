<?php
// ============================================================
// API: auth_check.php
// Returns the current PHP session user as JSON
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

if (!empty($_SESSION['user'])) {
    echo json_encode([
        'loggedIn' => true,
        'user'     => $_SESSION['user']
    ]);
} else {
    echo json_encode([
        'loggedIn' => false,
        'user'     => null
    ]);
}
