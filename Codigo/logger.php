<?php
/**
 * Security logging system
 * Implements logging for security events as per ISO 27001 requirements
 */

require_once 'config.php';

/**
 * Log security events
 * @param string $event Event type
 * @param string $message Log message
 * @param array $context Additional context data
 */
function logSecurityEvent($event, $message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'message' => $message,
        'user' => $_SESSION['tecnico'] ?? 'unknown',
        'user_id' => $_SESSION['idTecnico'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'context' => $context
    ];

    $logLine = json_encode($logEntry) . PHP_EOL;

    // Log to file
    $logFile = __DIR__ . '/logs/security.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

    // In production, also log to system log or external service
    if (APP_ENV === 'production') {
        error_log("SECURITY: $event - $message", 0);
    }
}

/**
 * Log authentication events
 */
function logAuthEvent($event, $message, $user = null) {
    logSecurityEvent('AUTH', $message, ['user' => $user, 'event_type' => $event]);
}

/**
 * Log access control events
 */
function logAccessEvent($event, $message, $resource = null) {
    logSecurityEvent('ACCESS', $message, ['resource' => $resource, 'event_type' => $event]);
}

/**
 * Log data modification events
 */
function logDataEvent($event, $message, $table = null, $record_id = null) {
    logSecurityEvent('DATA', $message, ['table' => $table, 'record_id' => $record_id, 'event_type' => $event]);
}
?>