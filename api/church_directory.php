<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function fetchRemoteJson(string $url): ?array
{
    $payload = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($response !== false && $status >= 200 && $status < 300) {
            $payload = $response;
        }
    }

    if ($payload === null && ini_get('allow_url_fopen')) {
        $response = @file_get_contents($url);
        if ($response !== false) {
            $payload = $response;
        }
    }

    if ($payload === null) {
        return null;
    }

    $decoded = json_decode($payload, true);
    return is_array($decoded) ? $decoded : null;
}

function normalizeText(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function uniqueSorted(array $values): array
{
    $seen = [];
    foreach ($values as $value) {
        $text = normalizeText((string) $value);
        if ($text === '') {
            continue;
        }
        $key = mb_strtolower($text, 'UTF-8');
        if (!isset($seen[$key])) {
            $seen[$key] = $text;
        }
    }

    natcasesort($seen);
    return array_values($seen);
}

function flattenRemoteDirectory(array $source): array
{
    $zones = [];
    $groupsByZone = [];

    foreach ($source as $regionName => $regionData) {
        if (!is_array($regionData)) {
            continue;
        }

        foreach ($regionData as $zoneKey => $zoneData) {
            $zoneKeyText = (string) $zoneKey;
            if (str_starts_with($zoneKeyText, '_') || strtolower($zoneKeyText) === 'groups' || !is_array($zoneData)) {
                continue;
            }

            $zoneName = normalizeText((string) ($zoneData['name'] ?? $zoneKey));
            if ($zoneName === '') {
                continue;
            }

            $zones[] = $zoneName;
            if (!isset($groupsByZone[$zoneName])) {
                $groupsByZone[$zoneName] = [];
            }

            foreach (($zoneData['groups'] ?? []) as $group) {
                if (!is_array($group)) {
                    continue;
                }
                $groupName = normalizeText((string) ($group['name'] ?? ''));
                if ($groupName !== '') {
                    $groupsByZone[$zoneName][] = $groupName;
                }
            }
        }
    }

    return [$zones, $groupsByZone];
}

$remoteUrl = 'https://order.rorglobalpartnershipdepartment.org/zones.json';
$remoteData = fetchRemoteJson($remoteUrl);

[$zones, $groupsByZone] = flattenRemoteDirectory(is_array($remoteData) ? $remoteData : []);

$fallbackPath = __DIR__ . '/../data/zones.json';
if (is_file($fallbackPath)) {
    $fallback = json_decode((string) file_get_contents($fallbackPath), true);
    foreach (($fallback['zones'] ?? []) as $zone) {
        $zoneName = normalizeText((string) $zone);
        if ($zoneName !== '') {
            $zones[] = $zoneName;
            $groupsByZone[$zoneName] = $groupsByZone[$zoneName] ?? [];
        }
    }
}

$churchesByZone = [];
$storagePath = __DIR__ . '/../secure_data/registrations.json';
if (is_file($storagePath)) {
    $saved = json_decode((string) file_get_contents($storagePath), true);
    if (is_array($saved)) {
        foreach ($saved as $registration) {
            $churchInfo = is_array($registration['church_info'] ?? null) ? $registration['church_info'] : [];
            $zone = normalizeText((string) ($churchInfo['zone'] ?? ''));
            $group = normalizeText((string) ($churchInfo['group'] ?? ''));
            $church = normalizeText((string) ($churchInfo['church'] ?? ''));

            if ($zone !== '') {
                $zones[] = $zone;
                $groupsByZone[$zone] = $groupsByZone[$zone] ?? [];
            }
            if ($zone !== '' && $group !== '') {
                $groupsByZone[$zone][] = $group;
            }
            if ($zone !== '' && $church !== '') {
                $churchesByZone[$zone] = $churchesByZone[$zone] ?? [];
                $churchesByZone[$zone][] = $church;
            }
        }
    }
}

$zones = uniqueSorted($zones);

foreach ($zones as $zone) {
    $groupsByZone[$zone] = uniqueSorted($groupsByZone[$zone] ?? []);
    $churchesByZone[$zone] = uniqueSorted($churchesByZone[$zone] ?? []);
}

echo json_encode([
    'success' => true,
    'source' => $remoteData ? 'remote' : 'fallback',
    'zones' => $zones,
    'groupsByZone' => $groupsByZone,
    'churchesByZone' => $churchesByZone,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
