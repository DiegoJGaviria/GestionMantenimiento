<?php
/**
 * Configuration file for database and application settings
 * This file should NOT be committed to version control
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '140226'); // WARNING: Change this in production!
define('DB_NAME', 'sistema_arreglo_computadores');

// Application configuration
define('APP_NAME', 'Sistema de Arreglo de Computadores');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // Change to 'production' in production

// Security settings
define('SESSION_LIFETIME', 10); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// Error reporting (disable in production)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Bogota');
?>