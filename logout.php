<?php
// This file handles user logout
// It destroys the session and redirects the user back to the homepage or admin page
session_start();

// Check if the user came from the admin page
$redirectParam = $_GET['redirect'] ?? '';

// Clear all session data
session_unset();
session_destroy();

// Redirect to homepage
header('Location: index.php');
exit;
