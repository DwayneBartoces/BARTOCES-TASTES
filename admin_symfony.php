<?php

require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: public/index.php/admin');
exit;
