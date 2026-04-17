<?php
/**
 * Database connection file
 * Uses configuration from config.php for security
 */

require_once 'config.php';

/**
 * Establishes a secure database connection
 * @return mysqli Database connection object
 * @throws Exception If connection fails
 */
function getDatabaseConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        // Log the error instead of displaying it
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Error interno del servidor. Intente nuevamente.");
    }

    // Set charset to prevent encoding issues
    $conn->set_charset("utf8mb4");

    // Enable strict mode for better data integrity
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES'");

    return $conn;
}

// Create global connection for backward compatibility
// TODO: Refactor to use dependency injection
try {
    $conn = getDatabaseConnection();
} catch (Exception $e) {
    die($e->getMessage());
}
?>
