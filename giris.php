<?php
require_once __DIR__ . '/includes/config.php';
if (is_logged_in()) redirect('/dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $loginLimit = login_rate_limit_status($db, $ip);

    if ($loginLimit['locked']) {
        set_flash(lang('login_locked', ['minutes' => ceil($loginLimit['retry_after'] / 60)]), 'error');
    } else {
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        clear_login_failures($db, $ip);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        log_activity($user['id'], 'login', 'Giris yapildi');
        set_flash(lang('login_success'), 'success');
        redirect('/dashboard.php');
    } else {
        record_login_failure($db, $ip);
        sleep(1);
        set_flash(lang('login_fail'), 'error');
    }
    }
}
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html><html lang="<?= $cl ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?= lang('meta_title_login') ?></title><link rel="stylesheet" href="/assets/css/style.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="auth-main"><div class="auth-card">
<div class="auth-header"><h1><?= lang('login_title') ?></h1><p><?= lang('login_subtitle') ?></p></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div><?php endif; ?>
<form method="POST" class="form"><?= csrf_field() ?>
<div class="form-group"><label><?= lang('login_username_label') ?></label><input type="text" name="username" required placeholder="<?= lang('login_username_ph') ?>"></div>
<div class="form-group"><label><?= lang('login_password_label') ?></label><input type="password" name="password" required placeholder="<?= lang('login_password_ph') ?>"></div>
<button type="submit" class="btn btn-primary btn-block"><?= lang('login_btn') ?></button>
</form>
<p style="margin-top:14px;color:var(--text-secondary);font-size:13px;"><?= lang('login_limit_note') ?></p>
<div class="auth-footer"><p><?= lang('no_account') ?> <a href="/kayit.php"><?= lang('signup_link') ?></a></p></div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?></body></html>
