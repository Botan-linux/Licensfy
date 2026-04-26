<?php

function api_request_id() {
    static $requestId = null;
    if ($requestId === null) {
        $requestId = bin2hex(random_bytes(8));
    }
    return $requestId;
}

function api_bootstrap() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('X-Api-Version: 1');
    header('X-Request-Id: ' . api_request_id());
}

function api_handle_preflight() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function api_require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        api_respond([
            'success' => false,
            'valid' => false,
            'status' => 'method_not_allowed',
            'code' => 'METHOD_NOT_ALLOWED',
            'error' => 'Method not allowed. Use POST.',
        ], 405);
    }
}

function api_get_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }
    return $_POST;
}

function api_json_encode($payload) {
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function api_respond(array $payload, int $statusCode = 200, ?string $signingKey = null) {
    if (!isset($payload['request_id'])) {
        $payload['request_id'] = api_request_id();
    }
    if (!isset($payload['timestamp'])) {
        $payload['timestamp'] = gmdate('Y-m-d H:i:s');
    }

    $json = api_json_encode($payload);

    if ($signingKey) {
        header('X-Signature: ' . hash_hmac('sha256', $json, $signingKey));
    }

    http_response_code($statusCode);
    echo $json;
    exit;
}

function api_set_rate_limit_headers(array $rate) {
    header('X-RateLimit-Limit: ' . $rate['limit']);
    header('X-RateLimit-Remaining: ' . max(0, $rate['remaining']));
    header('X-RateLimit-Reset: ' . max(0, $rate['reset_in']));
}

function api_security_delay() {
    usleep(250000);
}

function api_dispatch_webhooks(PDO $db, int $userId, string $event, array $payload) {
    if (!function_exists('curl_init')) {
        return;
    }

    $stmt = $db->prepare('SELECT * FROM webhook_endpoints WHERE user_id = ? AND is_active = 1');
    $stmt->execute([$userId]);
    $webhooks = $stmt->fetchAll();

    if (!$webhooks) {
        return;
    }

    $envelope = [
        'event' => $event,
        'timestamp' => gmdate('Y-m-d H:i:s'),
        'payload' => $payload,
    ];
    $json = api_json_encode($envelope);

    foreach ($webhooks as $webhook) {
        $events = json_decode($webhook['events'], true);
        if (!is_array($events) || !in_array($event, $events, true)) {
            continue;
        }

        $headers = [
            'Content-Type: application/json',
            'X-Licensfy-Event: ' . $event,
        ];

        if (!empty($webhook['secret_key'])) {
            $headers[] = 'X-Licensfy-Signature: ' . hash_hmac('sha256', $json, $webhook['secret_key']);
        }

        $ch = curl_init($webhook['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

function api_consume_rate_limit(PDO $db, string $ip, int $maxRequests = 30, int $window = 60) {
    $stmt = $db->prepare('SELECT id, requests_count, first_request_at FROM rate_limits WHERE ip_address = ?');
    $stmt->execute([$ip]);
    $record = $stmt->fetch();

    if (!$record) {
        $db->prepare('INSERT INTO rate_limits (ip_address) VALUES (?)')->execute([$ip]);
        return [
            'allowed' => true,
            'limit' => $maxRequests,
            'remaining' => $maxRequests - 1,
            'reset_in' => $window,
        ];
    }

    $elapsed = time() - strtotime($record['first_request_at']);
    if ($elapsed > $window) {
        $db->prepare('UPDATE rate_limits SET requests_count = 1, first_request_at = NOW(), last_request_at = NOW() WHERE ip_address = ?')->execute([$ip]);
        return [
            'allowed' => true,
            'limit' => $maxRequests,
            'remaining' => $maxRequests - 1,
            'reset_in' => $window,
        ];
    }

    if ((int)$record['requests_count'] >= $maxRequests) {
        return [
            'allowed' => false,
            'limit' => $maxRequests,
            'remaining' => 0,
            'reset_in' => $window - $elapsed,
        ];
    }

    $db->prepare('UPDATE rate_limits SET requests_count = requests_count + 1, last_request_at = NOW() WHERE ip_address = ?')->execute([$ip]);

    return [
        'allowed' => true,
        'limit' => $maxRequests,
        'remaining' => $maxRequests - ((int)$record['requests_count'] + 1),
        'reset_in' => $window - $elapsed,
    ];
}

function api_validate_license(PDO $db, array $input, string $ip) {
    $licenseKey = strtoupper(trim($input['license_key'] ?? ''));
    $productId = (int)($input['product_id'] ?? 0);
    $hwid = trim($input['hwid'] ?? '');
    $clientVersion = trim($input['version'] ?? '1.0.0');

    if ($licenseKey === '') {
        api_security_delay();
        return [
            'http_status' => 400,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'invalid_input',
                'code' => 'LICENSE_KEY_REQUIRED',
                'error' => 'License key is required.',
            ],
            'signing_key' => null,
        ];
    }

    if ($hwid !== '' && !preg_match('/^[a-zA-Z0-9\-_]{8,128}$/', $hwid)) {
        api_security_delay();
        return [
            'http_status' => 400,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'invalid_input',
                'code' => 'INVALID_HWID',
                'error' => 'Invalid HWID format.',
            ],
            'signing_key' => null,
        ];
    }

    $sql = '
        SELECT l.*, p.name AS product_name, p.version AS product_version, u.api_key
        FROM licenses l
        JOIN products p ON l.product_id = p.id
        JOIN users u ON l.user_id = u.id
        WHERE l.license_key = ?
    ';
    $params = [$licenseKey];

    if ($productId > 0) {
        $sql .= ' AND l.product_id = ?';
        $params[] = $productId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $license = $stmt->fetch();

    if (!$license) {
        api_security_delay();
        return [
            'http_status' => 404,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'not_found',
                'code' => 'LICENSE_NOT_FOUND',
                'error' => $productId > 0
                    ? 'License key not found or invalid for this product.'
                    : 'License key not found.',
            ],
            'signing_key' => null,
        ];
    }

    $signingKey = $license['api_key'];
    $blacklistEntry = find_blacklist_entry($db, (int)$license['user_id'], [
        'license_key' => $licenseKey,
        'ip_address' => $ip,
        'hwid' => $hwid,
        'customer_email' => $license['customer_email'] ?? '',
    ]);

    if ($blacklistEntry) {
        api_security_delay();
        log_activity(
            $license['user_id'],
            'API_BLACKLISTED',
            'Blocked by blacklist: ' . $blacklistEntry['type'] . ' = ' . $blacklistEntry['value'],
            $ip
        );
        api_dispatch_webhooks($db, (int)$license['user_id'], 'license.validation_failed', [
            'license_key' => $licenseKey,
            'status' => 'blacklisted',
            'reason' => $blacklistEntry['type'],
            'ip_address' => $ip,
            'hwid' => $hwid,
        ]);
        return [
            'http_status' => 403,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'blacklisted',
                'code' => 'BLACKLISTED',
                'error' => 'Request blocked by blacklist.',
                'blocked_by' => $blacklistEntry['type'],
            ],
            'signing_key' => $signingKey,
        ];
    }

    if ($license['status'] === 'revoked') {
        api_security_delay();
        log_activity($license['user_id'], 'API_REVOKED', "License: $licenseKey", $ip);
        api_dispatch_webhooks($db, (int)$license['user_id'], 'license.validation_failed', [
            'license_key' => $licenseKey,
            'status' => 'revoked',
            'ip_address' => $ip,
            'hwid' => $hwid,
        ]);
        return [
            'http_status' => 403,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'revoked',
                'code' => 'LICENSE_REVOKED',
                'error' => 'License has been revoked by the administrator.',
            ],
            'signing_key' => $signingKey,
        ];
    }

    if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
        $db->prepare('UPDATE licenses SET status = "expired" WHERE id = ?')->execute([$license['id']]);
        log_activity($license['user_id'], 'API_EXPIRED', "License: $licenseKey", $ip);
        api_dispatch_webhooks($db, (int)$license['user_id'], 'license.validation_failed', [
            'license_key' => $licenseKey,
            'status' => 'expired',
            'ip_address' => $ip,
            'hwid' => $hwid,
        ]);
        return [
            'http_status' => 403,
            'payload' => [
                'success' => false,
                'valid' => false,
                'status' => 'expired',
                'code' => 'LICENSE_EXPIRED',
                'error' => 'License has expired.',
            ],
            'signing_key' => $signingKey,
        ];
    }

    $currentCount = (int)$license['current_activations'];

    if ($hwid !== '') {
        $stmt = $db->prepare('SELECT id FROM activated_devices WHERE license_id = ? AND hwid = ?');
        $stmt->execute([$license['id'], $hwid]);
        $device = $stmt->fetch();

        if ($device) {
            $db->prepare('UPDATE activated_devices SET last_seen_at = NOW(), ip_address = ? WHERE id = ?')->execute([$ip, $device['id']]);
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM activated_devices WHERE license_id = ?');
            $stmt->execute([$license['id']]);
            $currentCount = (int)$stmt->fetchColumn();

            if ($currentCount >= (int)$license['max_activations']) {
                api_security_delay();
                log_activity($license['user_id'], 'API_HWID_DENIED', "HWID: $hwid (Limit: {$license['max_activations']})", $ip);
                api_dispatch_webhooks($db, (int)$license['user_id'], 'license.validation_failed', [
                    'license_key' => $licenseKey,
                    'status' => 'max_devices',
                    'ip_address' => $ip,
                    'hwid' => $hwid,
                ]);
                return [
                    'http_status' => 403,
                    'payload' => [
                        'success' => false,
                        'valid' => false,
                        'status' => 'max_devices',
                        'code' => 'MAX_DEVICES_REACHED',
                        'error' => 'Maximum device limit reached (' . $license['max_activations'] . ').',
                    ],
                    'signing_key' => $signingKey,
                ];
            }

            $db->prepare('INSERT INTO activated_devices (license_id, hwid, ip_address) VALUES (?, ?, ?)')->execute([$license['id'], $hwid, $ip]);
            $currentCount++;
            $db->prepare('UPDATE licenses SET current_activations = ? WHERE id = ?')->execute([$currentCount, $license['id']]);
        }
    }

    $updateRequired = version_compare($clientVersion, $license['product_version'], '<');
    $db->prepare('UPDATE licenses SET last_validated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$license['id']]);

    $logDescription = "License: $licenseKey | Ver: $clientVersion";
    if ($hwid !== '') {
        $logDescription .= " | HWID: $hwid";
    }
    log_activity($license['user_id'], 'API_SUCCESS', $logDescription, $ip);
    api_dispatch_webhooks($db, (int)$license['user_id'], 'license.validated', [
        'license_key' => $license['license_key'],
        'product_id' => (int)$license['product_id'],
        'product' => $license['product_name'],
        'customer' => $license['customer_name'],
        'ip_address' => $ip,
        'hwid' => $hwid,
        'update_required' => $updateRequired,
    ]);

    $expiryDays = $license['expires_at'] ? ceil((strtotime($license['expires_at']) - time()) / 86400) : null;

    return [
        'http_status' => 200,
        'payload' => [
            'success' => true,
            'valid' => true,
            'status' => 'active',
            'code' => 'LICENSE_VALID',
            'message' => 'License validated successfully.',
            'server_time' => date('Y-m-d H:i:s'),
            'data' => [
                'license_key' => $license['license_key'],
                'product_id' => (int)$license['product_id'],
                'product' => $license['product_name'],
                'latest_version' => $license['product_version'],
                'customer' => $license['customer_name'],
                'expires_at' => $license['expires_at'],
                'expiry_days' => $expiryDays,
                'update_required' => $updateRequired,
                'activations' => [
                    'current' => $currentCount,
                    'max' => (int)$license['max_activations'],
                ],
            ],
        ],
        'signing_key' => $signingKey,
    ];
}
