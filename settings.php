<?php
declare(strict_types=1);

const CHRISTMAS_TARGET = '2026-12-25T00:00:00+07:00';
const COUNTDOWN_SETTINGS_FILE = __DIR__ . '/data/countdown.json';

function remote_countdown_storage_enabled(): bool
{
        return (bool)(getenv('SUPABASE_URL') && (getenv('SUPABASE_SECRET_KEY') || getenv('SUPABASE_SERVICE_ROLE_KEY')));
}

function remote_countdown_request(string $method, ?array $body = null): ?array
{
    $baseUrl = rtrim((string)getenv('SUPABASE_URL'), '/');
    $key = (string)(getenv('SUPABASE_SECRET_KEY') ?: getenv('SUPABASE_SERVICE_ROLE_KEY'));
    if (!function_exists('curl_init')) return null;
    $curl = curl_init($baseUrl . '/rest/v1/countdown_settings');
    $headers = ['apikey: ' . $key, 'Authorization: Bearer ' . $key, 'Content-Type: application/json'];
    if ($method === 'GET') {
        curl_setopt($curl, CURLOPT_URL, $baseUrl . '/rest/v1/countdown_settings?id=eq.main&select=target');
    } else {
        $headers[] = 'Prefer: resolution=merge-duplicates,return=representation';
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if (!is_string($response) || $status < 200 || $status >= 300) return null;
    $data = json_decode($response, true);
    if ($method === 'GET') return is_array($data) && isset($data[0]) ? $data[0] : ['target' => CHRISTMAS_TARGET];
    return is_array($data) && isset($data[0]) ? $data[0] : null;
}

function countdown_settings(): array
{
    if (remote_countdown_storage_enabled()) {
        return remote_countdown_request('GET') ?? ['target' => CHRISTMAS_TARGET];
    }
    if (!is_file(COUNTDOWN_SETTINGS_FILE)) {
        return ['target' => CHRISTMAS_TARGET];
    }
    $json = file_get_contents(COUNTDOWN_SETTINGS_FILE);
    $settings = is_string($json) ? json_decode($json, true) : null;
    return is_array($settings) && isset($settings['target'])
        ? $settings
        : ['target' => CHRISTMAS_TARGET];
}

function save_countdown_target(string $target): bool
{
    if (remote_countdown_storage_enabled()) {
        return remote_countdown_request('POST', ['id' => 'main', 'target' => $target]) !== null;
    }
    if (getenv('VERCEL')) {
        return false;
    }
    $directory = dirname(COUNTDOWN_SETTINGS_FILE);
    if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
        return false;
    }
    $payload = json_encode(['target' => $target], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $payload !== false && @file_put_contents(COUNTDOWN_SETTINGS_FILE, $payload . PHP_EOL, LOCK_EX) !== false;
}

function countdown_target(): DateTimeImmutable
{
    try {
        return new DateTimeImmutable((string)countdown_settings()['target']);
    } catch (Throwable) {
        return new DateTimeImmutable(CHRISTMAS_TARGET);
    }
}
