<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api_helpers.php';

api_bootstrap();
api_handle_preflight();
api_require_post();

$db = getDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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

$result = api_validate_license($db, api_get_input(), $ip);
api_respond($result['payload'], $result['http_status'], $result['signing_key']);
