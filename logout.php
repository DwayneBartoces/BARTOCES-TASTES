<?php
// ============================================================
// BARTOCES TASTES - LOGOUT
// Destroys the PHP session and redirects to login.php
// ============================================================
session_start();
session_unset();
session_destroy();

header('Location: login.php');
exit;
