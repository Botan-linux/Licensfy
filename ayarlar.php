<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();

// Regenerate API key
if (isset($_POST['regen_api'])) {
    verify_csrf();
    $new_key = generate_api_key();
    $db->prepare('UPDATE users SET api_key = ? WHERE id = ?')->execute([$new_key, $user['id']]);
    log_activity($user['id'], 'api_key_regen', 'API anahtari yenilendi');
    set_flash(lang('api_regened'), 'success');
    redirect('/ayarlar.php');
}

// Update profile
if (isset($_POST['update_profile'])) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = lang('err_invalid_email');
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) $errors[] = lang('err_email_taken');
    if ($errors) { set_flash(implode('<br>', $errors), 'error'); }
    else {
        $db->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $user['id']]);
        set_flash(lang('profile_updated'), 'success');
        redirect('/ayarlar.php');
    }
}

// Change password
if (isset($_POST['change_password'])) {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';
    $errors = [];
    if (!password_verify($current, $user['password_hash'])) $errors[] = lang('err_wrong_password');
    if (strlen($new) < 6) $errors[] = lang('err_new_password_short');
    if ($new !== $new2) $errors[] = lang('err_passwords_dont_match');
    if ($errors) { set_flash(implode('<br>', $errors), 'error'); }
    else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
        log_activity($user['id'], 'password_change', 'Sifre degistirildi');
        set_flash(lang('password_changed'), 'success');
        redirect('/ayarlar.php');
    }
}

// Refresh user data
$user = current_user();
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_settings') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header"><div><h1><?= lang('settings_title') ?></h1><p><?= lang('settings_subtitle') ?></p></div></div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Profile -->
        <div class="card"><div class="card-header"><h2><?= lang('profile_info') ?></h2></div><div class="card-body">
            <form method="POST" class="form">
                <?= csrf_field() ?>
                <div class="form-group"><label><?= lang('username_label') ?></label><input type="text" value="<?= e($user['username']) ?>" disabled style="opacity:0.5;"></div>
                <div class="form-group"><label><?= lang('email_label') ?></label><input type="email" name="email" value="<?= e($user['email']) ?>" required></div>
                <div class="form-group"><label><?= lang('account_created') ?></label><input type="text" value="<?= format_date($user['created_at']) ?>" disabled style="opacity:0.5;"></div>
                <button type="submit" name="update_profile" value="1" class="btn btn-primary btn-block"><?= lang('btn_update') ?></button>
            </form>
        </div></div>

        <!-- API Key -->
        <div class="card"><div class="card-header"><h2><?= lang('api_key_title') ?></h2></div><div class="card-body">
            <p style="color:var(--text-secondary);margin-bottom:16px;font-size:13px;"><?= lang('api_key_desc') ?></p>
            <div class="api-key-box">
                <code id="apiKey"><?= e($user['api_key']) ?></code>
                <button class="btn btn-sm btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('apiKey').textContent)"><?= lang('btn_copy') ?></button>
            </div>
            <div style="margin-top:20px;">
                <form method="POST" onsubmit="if(!confirm('<?= lang('confirm_regen_api') ?>'))return false;">
                    <?= csrf_field() ?>
                    <button type="submit" name="regen_api" value="1" class="btn btn-danger btn-block"><?= lang('btn_regen_api') ?></button>
                </form>
            </div>
            <div class="code-block" style="margin-top:16px;">
# Usage
curl -X POST <?= SITE_URL ?>/api/validate.php \
  -H "Content-Type: application/json" \
  -d '{"license_key":"LISANS-ANAHTARI", "product_id": 1}'
            </div>
        </div></div>

        <!-- Password -->
        <div class="card" style="grid-column:1/-1;"><div class="card-header"><h2><?= lang('change_password') ?></h2></div><div class="card-body" style="max-width:500px;">
            <form method="POST" class="form">
                <?= csrf_field() ?>
                <div class="form-group"><label><?= lang('current_password_label') ?></label><input type="password" name="current_password" required></div>
                <div class="form-group"><label><?= lang('new_password_label') ?></label><input type="password" name="new_password" required minlength="6"></div>
                <div class="form-group"><label><?= lang('new_password2_label') ?></label><input type="password" name="new_password2" required></div>
                <button type="submit" name="change_password" value="1" class="btn btn-primary"><?= lang('btn_change_password') ?></button>
            </form>
        </div></div>
    </div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>