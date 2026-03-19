<?php

require_once __DIR__ . '/env.php';

if (!function_exists('db_config_from_url')) {
    function db_config_from_url(string $databaseUrl): ?array
    {
        $parts = parse_url($databaseUrl);
        if (!is_array($parts) || (($parts['scheme'] ?? '') !== 'mysql')) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        $dbName = ltrim($path, '/');

        return [
            'host' => (string) ($parts['host'] ?? '127.0.0.1'),
            'port' => (int) ($parts['port'] ?? 3306),
            'name' => $dbName !== '' ? $dbName : 'rhapaton',
            'user' => urldecode((string) ($parts['user'] ?? 'root')),
            'pass' => urldecode((string) ($parts['pass'] ?? '')),
            'charset' => 'utf8mb4',
        ];
    }
}

if (!function_exists('db_config')) {
    function db_config(): array
    {
        $databaseUrl = (string) app_env('DATABASE_URL', '');
        if ($databaseUrl !== '') {
            $parsed = db_config_from_url($databaseUrl);
            if (is_array($parsed)) {
                $charset = (string) app_env('DB_CHARSET', '');
                if ($charset !== '') {
                    $parsed['charset'] = $charset;
                }
                return $parsed;
            }
        }

        return [
            'host' => app_env('DB_HOST', '127.0.0.1'),
            'port' => (int) app_env('DB_PORT', '3306'),
            'name' => app_env('DB_NAME', 'rhapaton'),
            'user' => app_env('DB_USER', 'root'),
            'pass' => app_env('DB_PASS', ''),
            'charset' => app_env('DB_CHARSET', 'utf8mb4'),
        ];
    }
}

if (!function_exists('db_connection')) {
    function db_connection(): mysqli
    {
        static $connection = null;

        if ($connection instanceof mysqli) {
            return $connection;
        }

        $config = db_config();

        if (!class_exists('mysqli')) {
            throw new RuntimeException('PHP mysqli extension is not installed');
        }

        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        $connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name'],
            $config['port']
        );
        $connection->set_charset($config['charset']);

        return $connection;
    }
}

if (!function_exists('db_has_connection')) {
    function db_has_connection(): bool
    {
        try {
            db_connection();
            return true;
        } catch (Throwable $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }
}
