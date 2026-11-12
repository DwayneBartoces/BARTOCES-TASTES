<?php
// ==============================================================================
// BARTOCES TASTES - SYMFONY API: AUTH CHECK
// ==============================================================================
// Returns current authenticated session status and user data.
// ==============================================================================

require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;

json_response([
    'success'  => true,
    'loggedIn' => !empty($user),
    'user'     => $user,
]);
