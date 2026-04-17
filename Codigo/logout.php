<?php
/**
 * Secure logout implementation
 * Properly destroys session and logs the event
 */

session_start();

// Log logout event before destroying session
if (isset($_SESSION['usuario'])) {
    include_once 'logger.php';
    logAuthEvent('LOGOUT', 'Usuario cerró sesión', $_SESSION['usuario']);
}

// Destroy session completely
$_SESSION = [];
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login
header('Location: login.php');
exit();
?>
