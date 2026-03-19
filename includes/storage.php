<?php

require_once __DIR__ . '/db_connect.php';

if (!function_exists('storage_json_decode_array')) {
    function storage_json_decode_array(?string $json): array
    {
        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('storage_now')) {
    function storage_now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('storage_has_database')) {
    function storage_has_database(): bool
    {
        return db_has_connection();
    }
}

if (!function_exists('storage_bootstrap')) {
    function storage_bootstrap(): void
    {
        static $ready = false;

        if ($ready || !storage_has_database()) {
            return;
        }

        $db = db_connection();

        $db->query(
            "CREATE TABLE IF NOT EXISTS app_registrations (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                created_at DATETIME NOT NULL,
                ip_address VARCHAR(45) DEFAULT '',
                user_agent VARCHAR(255) DEFAULT '',
                title VARCHAR(50) DEFAULT '',
                first_name VARCHAR(120) DEFAULT '',
                last_name VARCHAR(120) DEFAULT '',
                email VARCHAR(190) DEFAULT '',
                phone VARCHAR(50) DEFAULT '',
                kingschat_username VARCHAR(190) DEFAULT '',
                affiliation_type VARCHAR(50) DEFAULT '',
                zone_name VARCHAR(190) DEFAULT '',
                network_name VARCHAR(190) DEFAULT '',
                manual_network VARCHAR(190) DEFAULT '',
                group_name VARCHAR(190) DEFAULT '',
                church_name VARCHAR(190) DEFAULT '',
                selected_days_json LONGTEXT NULL,
                sessions_json LONGTEXT NULL,
                onsite_participation VARCHAR(20) DEFAULT '',
                online_participation VARCHAR(20) DEFAULT '',
                feedback TEXT NULL,
                language_preference VARCHAR(20) DEFAULT '',
                KEY idx_registrations_created_at (created_at),
                KEY idx_registrations_email (email),
                KEY idx_registrations_kc (kingschat_username),
                KEY idx_registrations_zone (zone_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS app_kingschat_outbox (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME NOT NULL,
                origin VARCHAR(64) DEFAULT '',
                registration_id VARCHAR(64) DEFAULT '',
                username VARCHAR(190) DEFAULT '',
                name VARCHAR(255) DEFAULT '',
                message MEDIUMTEXT NULL,
                status VARCHAR(64) DEFAULT '',
                error_text TEXT NULL,
                KEY idx_outbox_registration_id (registration_id),
                KEY idx_outbox_status (status),
                KEY idx_outbox_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value LONGTEXT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        storage_import_legacy_json();
        $ready = true;
    }
}

if (!function_exists('storage_import_legacy_json')) {
    function storage_import_legacy_json(): void
    {
        static $imported = false;

        if ($imported || !storage_has_database()) {
            return;
        }

        $imported = true;
        $root = dirname(__DIR__);

        if (registration_storage_count() === 0) {
            $path = $root . '/secure_data/registrations.json';
            if (is_file($path)) {
                $rows = storage_json_decode_array((string) @file_get_contents($path));
                foreach ($rows as $row) {
                    if (is_array($row)) {
                        registration_storage_insert($row);
                    }
                }
            }
        }

        if (outbox_storage_count() === 0) {
            $path = $root . '/secure_data/kingschat_outbox.json';
            if (is_file($path)) {
                $rows = storage_json_decode_array((string) @file_get_contents($path));
                foreach ($rows as $row) {
                    if (is_array($row)) {
                        outbox_storage_append($row);
                    }
                }
            }
        }

        if (settings_storage_get_raw('kc_config') === null) {
            $path = $root . '/secure_data/kc_config.json';
            if (is_file($path)) {
                $raw = @file_get_contents($path);
                if (is_string($raw) && trim($raw) !== '') {
                    settings_storage_set_raw('kc_config', $raw);
                }
            }
        }
    }
}

if (!function_exists('registration_storage_count')) {
    function registration_storage_count(): int
    {
        if (!storage_has_database()) {
            return 0;
        }

        $db = db_connection();
        $result = $db->query('SELECT COUNT(*) AS total FROM app_registrations');
        $row = $result ? $result->fetch_assoc() : null;
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('registration_storage_row_to_record')) {
    function registration_storage_row_to_record(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'timestamp' => (string) ($row['created_at'] ?? ''),
            'ip_address' => (string) ($row['ip_address'] ?? ''),
            'user_agent' => (string) ($row['user_agent'] ?? ''),
            'personal_info' => [
                'title' => (string) ($row['title'] ?? ''),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'kingschat_username' => (string) ($row['kingschat_username'] ?? ''),
            ],
            'church_info' => [
                'affiliation_type' => (string) ($row['affiliation_type'] ?? ''),
                'zone' => (string) ($row['zone_name'] ?? ''),
                'network' => (string) ($row['network_name'] ?? ''),
                'manual_network' => (string) ($row['manual_network'] ?? ''),
                'group' => (string) ($row['group_name'] ?? ''),
                'church' => (string) ($row['church_name'] ?? ''),
            ],
            'event_info' => [
                'selected_days' => storage_json_decode_array($row['selected_days_json'] ?? null),
                'sessions' => storage_json_decode_array($row['sessions_json'] ?? null),
                'onsite_participation' => (string) ($row['onsite_participation'] ?? ''),
                'online_participation' => (string) ($row['online_participation'] ?? ''),
            ],
            'additional_info' => [
                'feedback' => (string) ($row['feedback'] ?? ''),
            ],
            'language_preference' => (string) ($row['language_preference'] ?? ''),
        ];
    }
}

if (!function_exists('registration_storage_all')) {
    function registration_storage_all(): array
    {
        if (!storage_has_database()) {
            return [];
        }

        storage_bootstrap();
        $db = db_connection();
        $result = $db->query('SELECT * FROM app_registrations ORDER BY created_at DESC, id DESC');
        $records = [];

        while ($row = $result->fetch_assoc()) {
            $records[] = registration_storage_row_to_record($row);
        }

        return $records;
    }
}

if (!function_exists('registration_storage_find_by_id')) {
    function registration_storage_find_by_id(string $id): ?array
    {
        if (!storage_has_database()) {
            return null;
        }

        storage_bootstrap();
        $db = db_connection();
        $stmt = $db->prepare('SELECT * FROM app_registrations WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? registration_storage_row_to_record($row) : null;
    }
}

if (!function_exists('registration_storage_insert')) {
    function registration_storage_insert(array $registration): void
    {
        if (!storage_has_database()) {
            throw new RuntimeException('Database connection unavailable');
        }

        storage_bootstrap();
        $db = db_connection();
        $stmt = $db->prepare(
            'INSERT INTO app_registrations (
                id, created_at, ip_address, user_agent, title, first_name, last_name, email, phone,
                kingschat_username, affiliation_type, zone_name, network_name, manual_network,
                group_name, church_name, selected_days_json, sessions_json, onsite_participation,
                online_participation, feedback, language_preference
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                created_at = VALUES(created_at),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                title = VALUES(title),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                email = VALUES(email),
                phone = VALUES(phone),
                kingschat_username = VALUES(kingschat_username),
                affiliation_type = VALUES(affiliation_type),
                zone_name = VALUES(zone_name),
                network_name = VALUES(network_name),
                manual_network = VALUES(manual_network),
                group_name = VALUES(group_name),
                church_name = VALUES(church_name),
                selected_days_json = VALUES(selected_days_json),
                sessions_json = VALUES(sessions_json),
                onsite_participation = VALUES(onsite_participation),
                online_participation = VALUES(online_participation),
                feedback = VALUES(feedback),
                language_preference = VALUES(language_preference)'
        );

        $personal = is_array($registration['personal_info'] ?? null) ? $registration['personal_info'] : [];
        $church = is_array($registration['church_info'] ?? null) ? $registration['church_info'] : [];
        $event = is_array($registration['event_info'] ?? null) ? $registration['event_info'] : [];
        $additional = is_array($registration['additional_info'] ?? null) ? $registration['additional_info'] : [];

        $id = (string) ($registration['id'] ?? uniqid('reg_', true));
        $createdAt = (string) ($registration['timestamp'] ?? storage_now());
        $ip = (string) ($registration['ip_address'] ?? '');
        $userAgent = substr((string) ($registration['user_agent'] ?? ''), 0, 255);
        $title = (string) ($personal['title'] ?? '');
        $firstName = (string) ($personal['first_name'] ?? '');
        $lastName = (string) ($personal['last_name'] ?? '');
        $email = (string) ($personal['email'] ?? '');
        $phone = (string) ($personal['phone'] ?? '');
        $kingschatUsername = (string) ($personal['kingschat_username'] ?? '');
        $affiliationType = (string) ($church['affiliation_type'] ?? '');
        $zone = (string) ($church['zone'] ?? '');
        $network = (string) ($church['network'] ?? '');
        $manualNetwork = (string) ($church['manual_network'] ?? '');
        $group = (string) ($church['group'] ?? '');
        $churchName = (string) ($church['church'] ?? '');
        $selectedDays = json_encode($event['selected_days'] ?? [], JSON_UNESCAPED_UNICODE);
        $sessions = json_encode($event['sessions'] ?? [], JSON_UNESCAPED_UNICODE);
        $onsite = (string) ($event['onsite_participation'] ?? '');
        $online = (string) ($event['online_participation'] ?? '');
        $feedback = (string) ($additional['feedback'] ?? '');
        $language = (string) ($registration['language_preference'] ?? '');

        $stmt->bind_param(
            'ssssssssssssssssssssss',
            $id,
            $createdAt,
            $ip,
            $userAgent,
            $title,
            $firstName,
            $lastName,
            $email,
            $phone,
            $kingschatUsername,
            $affiliationType,
            $zone,
            $network,
            $manualNetwork,
            $group,
            $churchName,
            $selectedDays,
            $sessions,
            $onsite,
            $online,
            $feedback,
            $language
        );
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('registration_storage_delete')) {
    function registration_storage_delete(string $id): bool
    {
        if (!storage_has_database()) {
            return false;
        }

        storage_bootstrap();
        $db = db_connection();
        $stmt = $db->prepare('DELETE FROM app_registrations WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }
}

if (!function_exists('registration_storage_duplicate_status')) {
    function registration_storage_duplicate_status(string $email, string $kingschatUsername = ''): array
    {
        $status = ['email' => false, 'kingschat' => false];
        if (!storage_has_database()) {
            return $status;
        }

        storage_bootstrap();
        $db = db_connection();

        $email = strtolower(trim($email));
        if ($email !== '') {
            $stmt = $db->prepare('SELECT id FROM app_registrations WHERE LOWER(email) = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $status['email'] = (bool) ($result && $result->fetch_assoc());
            $stmt->close();
        }

        $kingschatUsername = strtolower(ltrim(trim($kingschatUsername), '@'));
        if ($kingschatUsername !== '') {
            $stmt = $db->prepare("SELECT id FROM app_registrations WHERE LOWER(TRIM(LEADING '@' FROM kingschat_username)) = ? LIMIT 1");
            $stmt->bind_param('s', $kingschatUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            $status['kingschat'] = (bool) ($result && $result->fetch_assoc());
            $stmt->close();
        }

        return $status;
    }
}

if (!function_exists('registration_storage_directory_data')) {
    function registration_storage_directory_data(): array
    {
        if (!storage_has_database()) {
            return ['zones' => [], 'groupsByZone' => [], 'churchesByZone' => []];
        }

        storage_bootstrap();
        $db = db_connection();
        $result = $db->query('SELECT zone_name, group_name, church_name FROM app_registrations');

        $zones = [];
        $groupsByZone = [];
        $churchesByZone = [];

        while ($row = $result->fetch_assoc()) {
            $zone = trim((string) ($row['zone_name'] ?? ''));
            $group = trim((string) ($row['group_name'] ?? ''));
            $church = trim((string) ($row['church_name'] ?? ''));

            if ($zone !== '') {
                $zones[] = $zone;
                $groupsByZone[$zone] = $groupsByZone[$zone] ?? [];
                $churchesByZone[$zone] = $churchesByZone[$zone] ?? [];
            }
            if ($zone !== '' && $group !== '') {
                $groupsByZone[$zone][] = $group;
            }
            if ($zone !== '' && $church !== '') {
                $churchesByZone[$zone][] = $church;
            }
        }

        return [
            'zones' => $zones,
            'groupsByZone' => $groupsByZone,
            'churchesByZone' => $churchesByZone,
        ];
    }
}

if (!function_exists('outbox_storage_count')) {
    function outbox_storage_count(): int
    {
        if (!storage_has_database()) {
            return 0;
        }

        $db = db_connection();
        $result = $db->query('SELECT COUNT(*) AS total FROM app_kingschat_outbox');
        $row = $result ? $result->fetch_assoc() : null;
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('outbox_storage_append')) {
    function outbox_storage_append(array $entry): void
    {
        if (!storage_has_database()) {
            throw new RuntimeException('Database connection unavailable');
        }

        storage_bootstrap();
        $db = db_connection();
        $stmt = $db->prepare(
            'INSERT INTO app_kingschat_outbox (created_at, origin, registration_id, username, name, message, status, error_text)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $createdAt = (string) ($entry['timestamp'] ?? storage_now());
        $origin = (string) ($entry['origin'] ?? '');
        $registrationId = (string) ($entry['registration_id'] ?? '');
        $username = (string) ($entry['username'] ?? '');
        $name = (string) ($entry['name'] ?? '');
        $message = (string) ($entry['message'] ?? '');
        $status = (string) ($entry['status'] ?? '');
        $errorText = (string) ($entry['error'] ?? '');

        $stmt->bind_param('ssssssss', $createdAt, $origin, $registrationId, $username, $name, $message, $status, $errorText);
        $stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('outbox_storage_latest_status_by_registration')) {
    function outbox_storage_latest_status_by_registration(): array
    {
        if (!storage_has_database()) {
            return [];
        }

        storage_bootstrap();
        $db = db_connection();
        $result = $db->query(
            'SELECT registration_id, status
             FROM app_kingschat_outbox
             WHERE registration_id <> \'\'
             ORDER BY created_at DESC, id DESC'
        );

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $registrationId = (string) ($row['registration_id'] ?? '');
            $status = (string) ($row['status'] ?? '');
            if ($registrationId === '') {
                continue;
            }
            if (!isset($map[$registrationId]) || $status === 'sent') {
                $map[$registrationId] = $status;
            }
        }

        return $map;
    }
}

if (!function_exists('settings_storage_get_raw')) {
    function settings_storage_get_raw(string $key): ?string
    {
        if (!storage_has_database()) {
            return null;
        }

        storage_bootstrap();
        $db = db_connection();
        $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return is_array($row) ? (string) ($row['setting_value'] ?? '') : null;
    }
}

if (!function_exists('settings_storage_set_raw')) {
    function settings_storage_set_raw(string $key, string $value): void
    {
        if (!storage_has_database()) {
            throw new RuntimeException('Database connection unavailable');
        }

        storage_bootstrap();
        $db = db_connection();
        $updatedAt = storage_now();
        $stmt = $db->prepare(
            'INSERT INTO app_settings (setting_key, setting_value, updated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)'
        );
        $stmt->bind_param('sss', $key, $value, $updatedAt);
        $stmt->execute();
        $stmt->close();
    }
}
