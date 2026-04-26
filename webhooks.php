<?php
require_once __DIR__ . '/includes/config.php';
require_login();

$user = current_user();
$db = getDB();
$availableEvents = webhook_events();

if (isset($_POST['add_webhook'])) {
    verify_csrf();
    $url = trim($_POST['url'] ?? '');
    $secret = trim($_POST['secret_key'] ?? '');
    $events = array_values(array_intersect(array_keys($availableEvents), $_POST['events'] ?? []));
    $errors = [];

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = lang('webhook_invalid_url');
    }

    if (!$events) {
        $errors[] = lang('webhook_events_required');
    }

    if ($errors) {
        set_flash(implode('<br>', $errors), 'error');
        redirect('/webhooks.php');
    }

    $stmt = $db->prepare('INSERT INTO webhook_endpoints (user_id, url, secret_key, events) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user['id'], $url, $secret ?: null, json_encode($events)]);
    log_activity($user['id'], 'webhook_add', $url);
    set_flash(lang('webhook_added'), 'success');
    redirect('/webhooks.php');
}

if (isset($_POST['toggle_webhook'])) {
    verify_csrf();
    $webhookId = (int)($_POST['webhook_id'] ?? 0);
    $stmt = $db->prepare('UPDATE webhook_endpoints SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND user_id = ?');
    $stmt->execute([$webhookId, $user['id']]);
    log_activity($user['id'], 'webhook_toggle', 'Webhook #' . $webhookId);
    set_flash(lang('webhook_updated'), 'success');
    redirect('/webhooks.php');
}

if (isset($_POST['delete_webhook'])) {
    verify_csrf();
    $webhookId = (int)($_POST['webhook_id'] ?? 0);
    $db->prepare('DELETE FROM webhook_endpoints WHERE id = ? AND user_id = ?')->execute([$webhookId, $user['id']]);
    log_activity($user['id'], 'webhook_delete', 'Webhook #' . $webhookId);
    set_flash(lang('webhook_deleted'), 'success');
    redirect('/webhooks.php');
}

$stmt = $db->prepare('SELECT * FROM webhook_endpoints WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$webhooks = $stmt->fetchAll();

$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_webhooks') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div><h1><?= lang('webhooks_title') ?></h1><p><?= lang('webhooks_subtitle') ?></p></div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2><?= lang('webhooks_add_title') ?></h2></div>
            <div class="card-body">
                <form method="POST" class="form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label><?= lang('webhook_url_label') ?></label>
                        <input type="url" name="url" required placeholder="https://example.com/webhook">
                    </div>
                    <div class="form-group">
                        <label><?= lang('webhook_secret_label') ?></label>
                        <input type="text" name="secret_key" placeholder="<?= lang('webhook_secret_placeholder') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang('webhook_events_label') ?></label>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ($availableEvents as $event): ?>
                            <label style="display:flex;gap:8px;align-items:center;">
                                <input type="checkbox" name="events[]" value="<?= $event ?>">
                                <span><code><?= e($event) ?></code></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_webhook" value="1" class="btn btn-primary btn-block"><?= lang('webhook_add_btn') ?></button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2><?= lang('webhook_signature_title') ?></h2></div>
            <div class="card-body">
                <p style="color:var(--text-secondary);margin-bottom:16px;"><?= lang('webhook_signature_desc') ?></p>
                <div class="code-block">
X-Licensfy-Event: license.validated
X-Licensfy-Signature: hmac_sha256(body, secret_key)
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:24px;">
        <div class="card-header"><h2><?= lang('webhooks_list_title') ?> (<?= count($webhooks) ?>)</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>URL</th><th><?= lang('webhook_events_label') ?></th><th><?= lang('th_status') ?></th><th><?= lang('th_created') ?></th><th><?= lang('th_actions') ?></th></tr></thead>
                <tbody>
                <?php if ($webhooks): ?>
                    <?php foreach ($webhooks as $webhook): ?>
                    <?php $events = json_decode($webhook['events'], true) ?: []; ?>
                    <tr>
                        <td><code><?= e($webhook['url']) ?></code></td>
                        <td><?= e(implode(', ', $events)) ?></td>
                        <td><span class="badge <?= $webhook['is_active'] ? 'badge-green' : 'badge-orange' ?>"><?= $webhook['is_active'] ? lang('status_active') : lang('webhook_status_paused') ?></span></td>
                        <td><?= format_date($webhook['created_at']) ?></td>
                        <td class="actions">
                            <form method="POST" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="webhook_id" value="<?= $webhook['id'] ?>">
                                <button type="submit" name="toggle_webhook" value="1" class="btn btn-sm btn-outline"><?= lang('webhook_toggle_btn') ?></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= lang('webhook_confirm_delete') ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="webhook_id" value="<?= $webhook['id'] ?>">
                                <button type="submit" name="delete_webhook" value="1" class="btn btn-sm btn-danger"><?= lang('btn_delete') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center"><?= lang('webhooks_empty') ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
