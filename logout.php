<?php
// ============================================================
// BARTOCES TASTES - LOGOUT
// Destroys the PHP session and redirects to index.php or admin.php
// ============================================================
session_start();

$redirectParam = $_GET['redirect'] ?? '';
session_unset();
session_destroy();

if ($redirectParam === 'admin' || $redirectParam === 'admin.php') {
    header('Location: admin.php');
} else {
    header('Location: index.php');
}
exit;
