<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM products WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$product = $stmt->fetch();
if (!$product) { set_flash(lang('product_not_found'), 'error'); redirect('/urunler.php'); }

// Handle license generation
if (isset($_POST['generate'])) {
    verify_csrf();
    $count = max(1, min(100, (int)($_POST['count'] ?? 1)));
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $expires = $_POST['expires_at'] ?? '';
    $max_act = (int)($_POST['max_activations'] ?? 1);
    $generated = 0;
    for ($i = 0; $i < $count; $i++) {
        $key = generate_license_key();
        try {
            $stmt = $db->prepare('INSERT INTO licenses (license_key, product_id, user_id, customer_name, customer_email, max_activations, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
            $stmt->execute([$key, $id, $user['id'], $customer_name, $customer_email, $max_act, $expires ?: null]);
            api_dispatch_webhooks($db, (int)$user['id'], 'license.created', [
                'license_key' => $key,
                'product_id' => $id,
                'product' => $product['name'],
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'max_activations' => $max_act,
                'expires_at' => $expires ?: null,
            ]);
            $generated++;
        } catch (PDOException $e) {
            // Duplicate key collision - skip silently and try next
            if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'UNIQUE') === false) {
                // Log unexpected errors but continue generation
                error_log('License generation error: ' . $e->getMessage());
            }
        }
    }
    log_activity($user['id'], 'license_generate', $generated . ' lisans uretildi: ' . $product['name']);
    set_flash(lang('licenses_generated', ['n' => $generated]), 'success');
    redirect('/urun.php?id=' . $id . '&tab=licenses');
}

// Handle license revoke
if (isset($_POST['revoke'])) {
    verify_csrf();
    $lid = (int)$_POST['license_id'];
    $db->prepare('UPDATE licenses SET status = "revoked" WHERE id = ? AND product_id = ? AND user_id = ?')->execute([$lid, $id, $user['id']]);
    $stmt = $db->prepare('SELECT license_key FROM licenses WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$lid, $user['id']]);
    $revokedLicense = $stmt->fetch();
    if ($revokedLicense) {
        api_dispatch_webhooks($db, (int)$user['id'], 'license.revoked', [
            'license_id' => $lid,
            'license_key' => $revokedLicense['license_key'],
            'product_id' => $id,
            'product' => $product['name'],
        ]);
    }
    log_activity($user['id'], 'license_revoke', 'Lisans iptal edildi: #' . $lid);
    set_flash(lang('license_revoked'), 'success');
    redirect('/urun.php?id=' . $id . '&tab=licenses');
}

// Handle license activate (reactivate)
if (isset($_POST['activate'])) {
    verify_csrf();
    $lid = (int)$_POST['license_id'];
    $db->prepare('UPDATE licenses SET status = "active" WHERE id = ? AND product_id = ? AND user_id = ?')->execute([$lid, $id, $user['id']]);
    $stmt = $db->prepare('SELECT license_key FROM licenses WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$lid, $user['id']]);
    $activatedLicense = $stmt->fetch();
    if ($activatedLicense) {
        api_dispatch_webhooks($db, (int)$user['id'], 'license.activated', [
            'license_id' => $lid,
            'license_key' => $activatedLicense['license_key'],
            'product_id' => $id,
            'product' => $product['name'],
        ]);
    }
    log_activity($user['id'], 'license_activate', 'Lisans aktif edildi: #' . $lid);
    set_flash(lang('license_activated'), 'success');
    redirect('/urun.php?id=' . $id . '&tab=licenses');
}

// Handle single device removal
if (isset($_POST['remove_device'])) {
    verify_csrf();
    $deviceId = (int)($_POST['device_id'] ?? 0);

    $stmt = $db->prepare('
        SELECT ad.id, ad.license_id, l.license_key
        FROM activated_devices ad
        JOIN licenses l ON ad.license_id = l.id
        WHERE ad.id = ? AND l.product_id = ? AND l.user_id = ?
        LIMIT 1
    ');
    $stmt->execute([$deviceId, $id, $user['id']]);
    $device = $stmt->fetch();

    if ($device) {
        $db->prepare('DELETE FROM activated_devices WHERE id = ?')->execute([$deviceId]);
        $db->prepare('UPDATE licenses SET current_activations = GREATEST(current_activations - 1, 0) WHERE id = ?')->execute([$device['license_id']]);
        log_activity($user['id'], 'device_remove', 'Cihaz kaldirildi: ' . $device['license_key']);
        set_flash(lang('device_removed'), 'success');
    }

    redirect('/urun.php?id=' . $id . '&tab=devices');
}

// Handle device reset per license
if (isset($_POST['reset_devices'])) {
    verify_csrf();
    $lid = (int)($_POST['license_id'] ?? 0);

    $stmt = $db->prepare('SELECT id, license_key FROM licenses WHERE id = ? AND product_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$lid, $id, $user['id']]);
    $licenseToReset = $stmt->fetch();

    if ($licenseToReset) {
        $db->prepare('DELETE FROM activated_devices WHERE license_id = ?')->execute([$licenseToReset['id']]);
        $db->prepare('UPDATE licenses SET current_activations = 0 WHERE id = ?')->execute([$licenseToReset['id']]);
        log_activity($user['id'], 'device_reset', 'Aktivasyonlar sifirlandi: ' . $licenseToReset['license_key']);
        set_flash(lang('devices_reset'), 'success');
    }

    redirect('/urun.php?id=' . $id . '&tab=devices');
}

// Get licenses
$stmt = $db->prepare('SELECT * FROM licenses WHERE product_id = ? AND user_id = ? ORDER BY created_at DESC');
$stmt->execute([$id, $user['id']]);
$licenses = $stmt->fetchAll();

$stmt = $db->prepare('
    SELECT ad.*, l.license_key, l.max_activations, l.customer_name
    FROM activated_devices ad
    JOIN licenses l ON ad.license_id = l.id
    WHERE l.product_id = ? AND l.user_id = ?
    ORDER BY ad.last_seen_at DESC
');
$stmt->execute([$id, $user['id']]);
$devices = $stmt->fetchAll();

// Stats
$stmt = $db->prepare('SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = "active"');
$stmt->execute([$id]); $active = $stmt->fetchColumn();
$stmt = $db->prepare('SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = "revoked"');
$stmt->execute([$id]); $revoked = $stmt->fetchColumn();
$stmt = $db->prepare('SELECT COUNT(*) FROM licenses WHERE product_id = ? AND status = "expired"');
$stmt->execute([$id]); $expired = $stmt->fetchColumn();

$allowedTabs = ['overview', 'licenses', 'devices', 'generate', 'integration'];
$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'overview';
}
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['name']) ?> - Licensfy</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div>
            <h1><?= e($product['name']) ?></h1>
            <p>v<?= e($product['version']) ?> &middot; <?= e($product['description']) ?: lang('no_description') ?></p>
        </div>
        <a href="/urunler.php" class="btn btn-outline"><?= lang('btn_back') ?></a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card"><div class="stat-icon stat-blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div><div class="stat-info"><span class="stat-val"><?= count($licenses) ?></span><span class="stat-lbl"><?= lang('stat_total') ?></span></div></div>
        <div class="stat-card"><div class="stat-icon stat-green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-info"><span class="stat-val"><?= $active ?></span><span class="stat-lbl"><?= lang('stat_active') ?></span></div></div>
        <div class="stat-card"><div class="stat-icon stat-red"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div class="stat-info"><span class="stat-val"><?= $revoked ?></span><span class="stat-lbl"><?= lang('stat_revoked') ?></span></div></div>
        <div class="stat-card"><div class="stat-icon stat-orange"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="stat-info"><span class="stat-val"><?= $expired ?></span><span class="stat-lbl"><?= lang('stat_expired') ?></span></div></div>
    </div>

    <div class="tabs">
        <button class="tab-btn <?= $tab === 'overview' ? 'active' : '' ?>" onclick="location.href='?id=<?= $id ?>'"><?= lang('tab_overview') ?></button>
        <button class="tab-btn <?= $tab === 'licenses' ? 'active' : '' ?>" onclick="location.href='?id=<?= $id ?>&tab=licenses'"><?= lang('tab_licenses') ?></button>
        <button class="tab-btn <?= $tab === 'devices' ? 'active' : '' ?>" onclick="location.href='?id=<?= $id ?>&tab=devices'"><?= lang('tab_devices') ?></button>
        <button class="tab-btn <?= $tab === 'generate' ? 'active' : '' ?>" onclick="location.href='?id=<?= $id ?>&tab=generate'"><?= lang('tab_generate') ?></button>
        <button class="tab-btn <?= $tab === 'integration' ? 'active' : '' ?>" onclick="location.href='?id=<?= $id ?>&tab=integration'"><?= lang('tab_integration') ?></button>
    </div>

    <?php if ($tab === 'overview'): ?>
    <div class="grid-2">
        <div class="card"><div class="card-header"><h2><?= lang('info_title') ?></h2></div><div class="card-body">
            <div class="list-item"><span><?= lang('product_id') ?></span><strong>#<?= $product['id'] ?></strong></div>
            <div class="list-item"><span><?= lang('th_version') ?></span><span class="badge badge-blue">v<?= e($product['version']) ?></span></div>
            <div class="list-item"><span><?= lang('th_created') ?></span><span><?= format_date($product['created_at']) ?></span></div>
            <div class="list-item"><span><?= lang('total_licenses') ?></span><strong><?= count($licenses) ?></strong></div>
            <div class="list-item"><span><?= lang('total_devices') ?></span><strong><?= count($devices) ?></strong></div>
        </div></div>
        <div class="card"><div class="card-header"><h2><?= lang('quick_actions') ?></h2></div><div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
            <a href="?id=<?= $id ?>&tab=generate" class="btn btn-primary btn-block"><?= lang('btn_generate') ?></a>
            <a href="?id=<?= $id ?>&tab=licenses" class="btn btn-outline btn-block"><?= lang('btn_view_licenses') ?></a>
            <a href="?id=<?= $id ?>&tab=devices" class="btn btn-outline btn-block"><?= lang('btn_manage_devices') ?></a>
            <a href="?id=<?= $id ?>&tab=integration" class="btn btn-outline btn-block"><?= lang('btn_integration') ?></a>
        </div></div>
    </div>

    <?php elseif ($tab === 'licenses'): ?>
    <div class="card">
        <div class="card-header"><h2><?= lang('tab_licenses') ?> (<?= count($licenses) ?>)</h2></div>
        <div class="table-wrap">
        <?php if ($licenses): ?>
            <table>
                <thead><tr><th><?= lang('th_license_key') ?></th><th><?= lang('th_customer') ?></th><th><?= lang('th_status') ?></th><th><?= lang('th_last_validated') ?></th><th><?= lang('th_expiry') ?></th><th><?= lang('th_actions') ?></th></tr></thead>
                <tbody>
                <?php foreach ($licenses as $l): ?>
                    <tr>
                        <td><span class="license-key" onclick="navigator.clipboard.writeText('<?= e($l['license_key']) ?>');this.textContent='<?= lang('copied') ?>';setTimeout(()=>this.textContent='<?= e($l['license_key']) ?>',1000)"><?= e($l['license_key']) ?></span></td>
                        <td><?= e($l['customer_name']) ?: '-' ?><br><small><?= e($l['customer_email']) ?: '' ?></small></td>
                        <td><?php
                            $st = $l['status'];
                            $cls = $st === 'active' ? 'badge-green' : ($st === 'revoked' ? 'badge-red' : 'badge-orange');
                            $lbl = $st === 'active' ? lang('status_active') : ($st === 'revoked' ? lang('status_revoked') : lang('status_expired'));
                            echo "<span class=\"badge $cls\">$lbl</span>";
                        ?></td>
                        <td><?= $l['last_validated_at'] ? format_date($l['last_validated_at']) : lang('never') ?></td>
                        <td><?= $l['expires_at'] ? format_date($l['expires_at']) : lang('infinite') ?></td>
                        <td class="actions">
                            <?php if ($l['status'] !== 'active'): ?>
                            <form method="POST" style="display:inline;"><input type="hidden" name="license_id" value="<?= $l['id'] ?>"><input type="hidden" name="activate" value="1"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-success"><?= lang('btn_activate') ?></button></form>
                            <?php endif; ?>
                            <?php if ($l['status'] === 'active'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= lang('confirm_revoke') ?>')"><input type="hidden" name="license_id" value="<?= $l['id'] ?>"><input type="hidden" name="revoke" value="1"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger"><?= lang('btn_revoke') ?></button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><p><?= lang('no_licenses') ?></p><a href="?id=<?= $id ?>&tab=generate" class="btn btn-primary btn-sm"><?= lang('tab_generate') ?></a></div>
        <?php endif; ?>
        </div>
    </div>

    <?php elseif ($tab === 'devices'): ?>
    <div class="card">
        <div class="card-header"><h2><?= lang('devices_title') ?> (<?= count($devices) ?>)</h2></div>
        <div class="table-wrap">
        <?php if ($devices): ?>
            <table>
                <thead><tr><th><?= lang('th_license_key') ?></th><th><?= lang('th_customer') ?></th><th><?= lang('th_hwid') ?></th><th><?= lang('th_ip_address') ?></th><th><?= lang('th_last_seen') ?></th><th><?= lang('th_actions') ?></th></tr></thead>
                <tbody>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><code><?= e($device['license_key']) ?></code></td>
                        <td><?= e($device['customer_name']) ?: '-' ?></td>
                        <td><code><?= e($device['hwid']) ?></code></td>
                        <td><?= e($device['ip_address']) ?: '-' ?></td>
                        <td><?= format_date($device['last_seen_at']) ?></td>
                        <td class="actions">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= lang('confirm_remove_device') ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                <button type="submit" name="remove_device" value="1" class="btn btn-sm btn-danger"><?= lang('btn_remove_device') ?></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= lang('confirm_reset_devices') ?>')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="license_id" value="<?= $device['license_id'] ?>">
                                <button type="submit" name="reset_devices" value="1" class="btn btn-sm btn-outline"><?= lang('btn_reset_devices') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><p><?= lang('no_devices') ?></p></div>
        <?php endif; ?>
        </div>
    </div>

    <?php elseif ($tab === 'generate'): ?>
    <div class="card"><div class="card-body" style="max-width:500px;">
        <form method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label><?= lang('gen_count_label') ?></label>
                <input type="number" name="count" value="1" min="1" max="100" required>
            </div>
            <div class="form-group">
                <label><?= lang('gen_customer_label') ?></label>
                <input type="text" name="customer_name" placeholder="<?= lang('gen_customer_ph') ?>">
            </div>
            <div class="form-group">
                <label><?= lang('gen_email_label') ?></label>
                <input type="email" name="customer_email" placeholder="<?= lang('gen_email_ph') ?>">
            </div>
            <div class="form-group">
                <label><?= lang('gen_max_act_label') ?></label>
                <input type="number" name="max_activations" value="1" min="1" max="100">
            </div>
            <div class="form-group">
                <label><?= lang('gen_expiry_label') ?></label>
                <input type="datetime-local" name="expires_at">
            </div>
            <button type="submit" name="generate" value="1" class="btn btn-primary btn-block"><?= lang('btn_gen_licenses') ?></button>
        </form>
    </div></div>

    <?php elseif ($tab === 'integration'): ?>
    <div class="card"><div class="card-header"><h2><?= lang('tab_integration') ?> - <?= e($product['name']) ?></h2></div><div class="card-body">
        <p style="color:var(--text-secondary);margin-bottom:16px;"><?= lang('integration_desc') ?></p>

        <h3 style="margin-bottom:8px;"><?= lang('integration_contract') ?></h3>
        <div class="code-block">
POST <?= SITE_URL ?>/api/validate.php
Content-Type: application/json

{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "hwid": "optional-device-id",
  "version": "1.0.0"
}
        </div>

        <p style="color:var(--text-secondary);margin:16px 0 8px;"><?= lang('integration_contract_desc') ?></p>

        <h3 style="margin:20px 0 8px;"><?= lang('integration_response_title') ?></h3>
        <div class="code-block">
{
  "valid": true,
  "message": "License validated successfully.",
  "server_time": "2026-04-26 21:00:00",
  "update_required": false,
  "data": {
    "product": "<?= e($product['name']) ?>",
    "latest_version": "<?= e($product['version']) ?>",
    "customer": "Customer Name",
    "expires_at": null,
    "expiry_days": null,
    "activations": {
      "current": 1,
      "max": 1
    }
  }
}
        </div>

        <h3 style="margin-bottom:8px;">Python</h3>
        <div class="code-block">
import requests
import hashlib
import platform

license_key = open("license.txt").read().strip()
hwid = hashlib.sha256(platform.node().encode()).hexdigest()[:32]
response = requests.post(
    "<?= SITE_URL ?>/api/validate.php",
    json={"license_key": license_key, "hwid": hwid}
)
data = response.json()
if data["valid"]:
    print("<?= lang('license_valid') ?>")
else:
    print("<?= lang('license_invalid') ?>" + data.get("error", ""))
    exit(1)
        </div>

        <h3 style="margin:20px 0 8px;">JavaScript (Node.js)</h3>
        <div class="code-block">
const fs = require('fs');
const os = require('os');
const crypto = require('crypto');
const licenseKey = fs.readFileSync('license.txt', 'utf8').trim();
const hwid = crypto.createHash('sha256').update(os.hostname()).digest('hex').slice(0, 32);

fetch('<?= SITE_URL ?>/api/validate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ license_key: licenseKey, hwid })
})
.then(r => r.json())
.then(data => {
    if (data.valid) console.log('<?= lang('license_valid') ?>');
    else { console.error('<?= lang('license_invalid') ?>', data.error); process.exit(1); }
});
        </div>

        <h3 style="margin:20px 0 8px;">cURL</h3>
        <div class="code-block">
curl -X POST <?= SITE_URL ?>/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{"license_key":"BURAYA_LISANS_KEY", "hwid":"DEVICE12345"}'
        </div>
    </div></div>
    <?php endif; ?>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
