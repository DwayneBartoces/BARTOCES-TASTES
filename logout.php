<?php
// ============================================================
// BARTOCES TASTES - LOGOUT
// Destroys the PHP session and redirects to index.php (homepage)
// ============================================================
session_start();
session_unset();
session_destroy();

header('Location: index.php');
exit;
