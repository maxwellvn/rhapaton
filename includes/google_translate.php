<?php

require_once __DIR__ . '/env.php';

if (!function_exists('google_language_fallbacks')) {
    function google_language_fallbacks(): array
    {
        return [
            ['language' => 'af', 'name' => 'Afrikaans'],
            ['language' => 'am', 'name' => 'Amharic'],
            ['language' => 'ar', 'name' => 'Arabic'],
            ['language' => 'az', 'name' => 'Azerbaijani'],
            ['language' => 'bg', 'name' => 'Bulgarian'],
            ['language' => 'bn', 'name' => 'Bengali'],
            ['language' => 'cs', 'name' => 'Czech'],
            ['language' => 'cy', 'name' => 'Welsh'],
            ['language' => 'da', 'name' => 'Danish'],
            ['language' => 'de', 'name' => 'German'],
            ['language' => 'el', 'name' => 'Greek'],
            ['language' => 'en', 'name' => 'English'],
            ['language' => 'es', 'name' => 'Spanish'],
            ['language' => 'et', 'name' => 'Estonian'],
            ['language' => 'eu', 'name' => 'Basque'],
            ['language' => 'fa', 'name' => 'Persian'],
            ['language' => 'fi', 'name' => 'Finnish'],
            ['language' => 'fil', 'name' => 'Filipino'],
            ['language' => 'fr', 'name' => 'French'],
            ['language' => 'ga', 'name' => 'Irish'],
            ['language' => 'gu', 'name' => 'Gujarati'],
            ['language' => 'ha', 'name' => 'Hausa'],
            ['language' => 'he', 'name' => 'Hebrew'],
            ['language' => 'hi', 'name' => 'Hindi'],
            ['language' => 'hr', 'name' => 'Croatian'],
            ['language' => 'hu', 'name' => 'Hungarian'],
            ['language' => 'hy', 'name' => 'Armenian'],
            ['language' => 'id', 'name' => 'Indonesian'],
            ['language' => 'ig', 'name' => 'Igbo'],
            ['language' => 'is', 'name' => 'Icelandic'],
            ['language' => 'it', 'name' => 'Italian'],
            ['language' => 'ja', 'name' => 'Japanese'],
            ['language' => 'ka', 'name' => 'Georgian'],
            ['language' => 'kk', 'name' => 'Kazakh'],
            ['language' => 'km', 'name' => 'Khmer'],
            ['language' => 'kn', 'name' => 'Kannada'],
            ['language' => 'ko', 'name' => 'Korean'],
            ['language' => 'lo', 'name' => 'Lao'],
            ['language' => 'lt', 'name' => 'Lithuanian'],
            ['language' => 'lv', 'name' => 'Latvian'],
            ['language' => 'mk', 'name' => 'Macedonian'],
            ['language' => 'ml', 'name' => 'Malayalam'],
            ['language' => 'mr', 'name' => 'Marathi'],
            ['language' => 'ms', 'name' => 'Malay'],
            ['language' => 'mt', 'name' => 'Maltese'],
            ['language' => 'my', 'name' => 'Myanmar (Burmese)'],
            ['language' => 'ne', 'name' => 'Nepali'],
            ['language' => 'nl', 'name' => 'Dutch'],
            ['language' => 'no', 'name' => 'Norwegian'],
            ['language' => 'or', 'name' => 'Odia'],
            ['language' => 'pa', 'name' => 'Punjabi'],
            ['language' => 'pl', 'name' => 'Polish'],
            ['language' => 'pt', 'name' => 'Portuguese'],
            ['language' => 'ro', 'name' => 'Romanian'],
            ['language' => 'ru', 'name' => 'Russian'],
            ['language' => 'sk', 'name' => 'Slovak'],
            ['language' => 'sl', 'name' => 'Slovenian'],
            ['language' => 'so', 'name' => 'Somali'],
            ['language' => 'sq', 'name' => 'Albanian'],
            ['language' => 'sr', 'name' => 'Serbian'],
            ['language' => 'sv', 'name' => 'Swedish'],
            ['language' => 'sw', 'name' => 'Swahili'],
            ['language' => 'ta', 'name' => 'Tamil'],
            ['language' => 'te', 'name' => 'Telugu'],
            ['language' => 'th', 'name' => 'Thai'],
            ['language' => 'tr', 'name' => 'Turkish'],
            ['language' => 'uk', 'name' => 'Ukrainian'],
            ['language' => 'ur', 'name' => 'Urdu'],
            ['language' => 'uz', 'name' => 'Uzbek'],
            ['language' => 'vi', 'name' => 'Vietnamese'],
            ['language' => 'xh', 'name' => 'Xhosa'],
            ['language' => 'yo', 'name' => 'Yoruba'],
            ['language' => 'zh', 'name' => 'Chinese'],
            ['language' => 'zu', 'name' => 'Zulu'],
        ];
    }
}

if (!function_exists('google_translate_api_key')) {
    function google_translate_api_key(): string
    {
        return (string) app_env('GOOGLE_TRANSLATE_API_KEY', '');
    }
}

if (!function_exists('google_translate_request')) {
    function google_translate_request(string $path, array $payload = [], string $method = 'POST'): array
    {
        $apiKey = google_translate_api_key();
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'GOOGLE_TRANSLATE_API_KEY is not configured'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'PHP cURL extension is missing'];
        }

        $url = 'https://translation.googleapis.com' . $path;
        $query = ['key' => $apiKey];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 20,
        ];

        if ($method === 'GET') {
            $query = array_merge($query, $payload);
        } else {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($payload, '', '&');
        }

        $url .= '?' . http_build_query($query, '', '&');
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }

        $json = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            return ['ok' => false, 'error' => 'Translation request failed', 'status' => $status, 'raw' => $body];
        }

        return ['ok' => true, 'data' => $json];
    }
}

if (!function_exists('google_translate_many')) {
    function google_translate_many(array $texts, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $texts = array_values(array_map(static fn ($text) => (string) $text, $texts));
        if ($texts === []) {
            return ['ok' => true, 'texts' => []];
        }

        $payload = [
            'q' => $texts,
            'target' => $targetLanguage,
            'format' => 'text',
        ];

        if ($sourceLanguage) {
            $payload['source'] = $sourceLanguage;
        }

        $response = google_translate_request('/language/translate/v2', $payload, 'POST');
        if (!$response['ok']) {
            return $response;
        }

        $translations = $response['data']['data']['translations'] ?? [];
        if (!is_array($translations) || count($translations) !== count($texts)) {
            return ['ok' => false, 'error' => 'Unexpected translation response'];
        }

        return [
            'ok' => true,
            'texts' => array_map(
                static fn ($item) => html_entity_decode((string) ($item['translatedText'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $translations
            ),
        ];
    }
}

if (!function_exists('google_translate_text')) {
    function google_translate_text(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        $response = google_translate_many([$text], $targetLanguage, $sourceLanguage);
        if (!$response['ok']) {
            return $response;
        }

        return ['ok' => true, 'text' => $response['texts'][0] ?? ''];
    }
}

if (!function_exists('google_supported_languages')) {
    function google_supported_languages(string $displayLanguage = 'en'): array
    {
        $response = google_translate_request(
            '/language/translate/v2/languages',
            ['target' => $displayLanguage],
            'GET'
        );
        if (!$response['ok']) {
            return ['ok' => true, 'languages' => google_language_fallbacks(), 'fallback' => true];
        }

        $languages = $response['data']['data']['languages'] ?? [];
        if (!is_array($languages)) {
            return ['ok' => true, 'languages' => google_language_fallbacks(), 'fallback' => true];
        }

        usort($languages, static function (array $a, array $b): int {
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return ['ok' => true, 'languages' => $languages];
    }
}
