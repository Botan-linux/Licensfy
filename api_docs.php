<?php
require_once __DIR__ . '/includes/config.php';
require_login();

$cl = current_lang();
$events = array_keys(webhook_events());
$errorCodes = [
    'LICENSE_KEY_REQUIRED',
    'INVALID_HWID',
    'LICENSE_NOT_FOUND',
    'BLACKLISTED',
    'LICENSE_REVOKED',
    'LICENSE_EXPIRED',
    'MAX_DEVICES_REACHED',
    'RATE_LIMITED',
];
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_api_docs') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div>
            <h1><?= lang('api_docs_title') ?></h1>
            <p><?= lang('api_docs_subtitle') ?></p>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_validate_title') ?></h2></div>
            <div class="card-body">
                <div class="code-block">
POST <?= SITE_URL ?>/api/validate.php
Content-Type: application/json
                </div>
                <p style="color:var(--text-secondary);margin-top:16px;"><?= lang('api_docs_validate_desc') ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_headers_title') ?></h2></div>
            <div class="card-body">
                <div class="list-item"><span>`X-Api-Version`</span><strong>1</strong></div>
                <div class="list-item"><span>`X-Request-Id`</span><strong><?= lang('api_docs_header_request_id') ?></strong></div>
                <div class="list-item"><span>`X-RateLimit-Limit`</span><strong><?= lang('api_docs_header_limit') ?></strong></div>
                <div class="list-item"><span>`X-RateLimit-Remaining`</span><strong><?= lang('api_docs_header_remaining') ?></strong></div>
                <div class="list-item"><span>`X-RateLimit-Reset`</span><strong><?= lang('api_docs_header_reset') ?></strong></div>
                <div class="list-item"><span>`X-Signature`</span><strong><?= lang('api_docs_header_signature') ?></strong></div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <div class="card-header"><h2><?= lang('api_docs_request_title') ?></h2></div>
        <div class="card-body">
            <div class="code-block">
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "product_id": 1,
  "hwid": "optional-device-id",
  "version": "1.0.0"
}
            </div>
            <div class="list-item"><span>`license_key`</span><strong><?= lang('api_docs_license_key_desc') ?></strong></div>
            <div class="list-item"><span>`product_id`</span><strong><?= lang('api_docs_product_id_desc') ?></strong></div>
            <div class="list-item"><span>`hwid`</span><strong><?= lang('api_docs_hwid_desc') ?></strong></div>
            <div class="list-item"><span>`version`</span><strong><?= lang('api_docs_version_desc') ?></strong></div>
        </div>
    </div>

    <div class="grid-2" style="margin-top:24px;">
        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_success_title') ?></h2></div>
            <div class="card-body">
                <div class="code-block">
{
  "success": true,
  "valid": true,
  "status": "active",
  "code": "LICENSE_VALID",
  "message": "License validated successfully.",
  "request_id": "abcd1234ef567890",
  "timestamp": "2026-04-26 20:30:00",
  "server_time": "2026-04-26 23:30:00",
  "data": {
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "product_id": 1,
    "product": "Premium App",
    "latest_version": "1.2.0",
    "customer": "John Doe",
    "expires_at": null,
    "expiry_days": null,
    "update_required": false,
    "activations": {
      "current": 1,
      "max": 3
    }
  }
}
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_error_title') ?></h2></div>
            <div class="card-body">
                <div class="code-block">
{
  "success": false,
  "valid": false,
  "status": "revoked",
  "code": "LICENSE_REVOKED",
  "error": "License has been revoked by the administrator.",
  "request_id": "abcd1234ef567890",
  "timestamp": "2026-04-26 20:30:00"
}
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2" style="margin-top:24px;">
        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_codes_title') ?></h2></div>
            <div class="card-body">
                <?php foreach ($errorCodes as $code): ?>
                <div class="list-item"><span><code><?= e($code) ?></code></span><strong><?= lang('api_code_' . strtolower($code)) ?></strong></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2><?= lang('api_docs_webhooks_title') ?></h2></div>
            <div class="card-body">
                <?php foreach ($events as $event): ?>
                <div class="list-item"><span><code><?= e($event) ?></code></span><strong><?= lang('api_docs_webhook_event_desc') ?></strong></div>
                <?php endforeach; ?>
                <div class="code-block" style="margin-top:16px;">
X-Licensfy-Event: license.validated
X-Licensfy-Signature: hmac_sha256(raw_body, secret_key)
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <div class="card-header"><h2><?= lang('api_docs_examples_title') ?></h2></div>
        <div class="card-body">
            <div class="code-block">
curl -X POST <?= SITE_URL ?>/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX-XXXX-XXXX-XXXX","hwid":"DEVICE12345","version":"1.0.0"}'
            </div>
        </div>
    </div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
