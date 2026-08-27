<?php
// auth/logout.php - Session Terminator
require_once __DIR__ . '/../config/config.php';

// Unset user session
unset($_SESSION['user']);
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'You have been successfully signed out.');
header("Location: " . BASE_URL . "/index.php");
exit;
