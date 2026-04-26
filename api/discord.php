<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/api_helpers.php";

api_bootstrap();
api_handle_preflight();
api_require_post();

$input = api_get_input();
$db = getDB();
$ip = $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
$rate = api_consume_rate_limit($db, $ip);
api_set_rate_limit_headers($rate);

if (!$rate['allowed']) {
    api_respond([
        'success' => false,
        'valid' => false,
        'status' => 'rate_limited',
        'code' => 'RATE_LIMITED',
        'error' => 'Rate limit exceeded.',
    ], 429);
}

$result = api_validate_license($db, $input, $ip);
$payload = $result['payload'];
$webhookUrl = trim($input['webhook_url'] ?? '');
$userId = trim($input['user_id'] ?? 'Unknown');
$username = trim($input['username'] ?? 'Unknown');
$guildName = trim($input['guild_name'] ?? '');

if ($webhookUrl !== '' && filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
    $embed = [
        'title' => !empty($payload['valid']) ? 'License Validation Successful' : 'License Validation Failed',
        'color' => !empty($payload['valid']) ? 65280 : 16711680,
        'fields' => [
            ['name' => 'License Key', 'value' => '`' . strtoupper(trim($input['license_key'] ?? '')) . '`', 'inline' => true],
            ['name' => 'User', 'value' => $username . ' (ID: ' . $userId . ')', 'inline' => true],
            ['name' => 'Status', 'value' => $payload['status'] ?? 'unknown', 'inline' => true],
        ],
    ];

    if ($guildName !== '') {
        $embed['fields'][] = ['name' => 'Guild', 'value' => $guildName, 'inline' => true];
    }

    if (!empty($payload['valid'])) {
        $data = $payload['data'] ?? [];
        $embed['fields'][] = ['name' => 'Product', 'value' => ($data['product'] ?? '-') . ' v' . ($data['latest_version'] ?? '-'), 'inline' => true];
        $embed['fields'][] = ['name' => 'Activations', 'value' => ($data['activations']['current'] ?? 0) . '/' . ($data['activations']['max'] ?? 0), 'inline' => true];
        if (!empty($data['expires_at'])) {
            $embed['fields'][] = ['name' => 'Expires At', 'value' => $data['expires_at'], 'inline' => true];
        }
    } else {
        $embed['description'] = $payload['error'] ?? 'Unknown error.';
    }

    $webhookPayload = [
        'embeds' => [$embed],
        'username' => 'Licensfy Bot',
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => api_json_encode($webhookPayload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$payload['webhook_sent'] = $webhookUrl !== '' && filter_var($webhookUrl, FILTER_VALIDATE_URL);
api_respond($payload, $result['http_status'], $result['signing_key']);
