<?php

require_once __DIR__ . '/includes/env.php';

$host = app_env('DB_HOST', '127.0.0.1');
$port = (int) app_env('DB_PORT', '3306');
$name = app_env('DB_NAME', 'rhapaton');
$user = app_env('DB_USER', 'root');
$pass = app_env('DB_PASS', '');
$charset = app_env('DB_CHARSET', 'utf8mb4');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: text/plain; charset=utf-8');

try {
    $conn = new mysqli($host, $user, $pass, '', $port);
    $conn->set_charset($charset);
    $conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($name) . "` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
    $conn->select_db($name);

    $conn->query(
        "CREATE TABLE IF NOT EXISTS registrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            registration_id VARCHAR(64) NOT NULL UNIQUE,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(64) DEFAULT NULL,
            country VARCHAR(128) DEFAULT NULL,
            zone VARCHAR(128) DEFAULT NULL,
            language VARCHAR(64) DEFAULT NULL,
            kingschat_username VARCHAR(128) DEFAULT NULL,
            payload LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS kingschat_outbox (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            status VARCHAR(64) NOT NULL DEFAULT 'pending',
            meta LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}"
    );

    echo "Database ready: {$name}\n";
    echo "Tables ready: registrations, kingschat_outbox\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Database setup failed: " . $e->getMessage() . "\n";
}
