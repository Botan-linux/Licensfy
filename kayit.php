<?php
require_once __DIR__ . '/includes/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $errors = [];
    if (strlen($username) < 3) $errors[] = lang('reg_err_username');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = lang('reg_err_email');
    if (strlen($password) < 6) $errors[] = lang('reg_err_password');
    if ($password !== $password2) $errors[] = lang('reg_err_password_match');
    $db = getDB();
    $s = $db->prepare('SELECT id FROM users WHERE username = ?'); $s->execute([$username]);
    if ($s->fetch()) $errors[] = lang('reg_err_username_taken');
    $s = $db->prepare('SELECT id FROM users WHERE email = ?'); $s->execute([$email]);
    if ($s->fetch()) $errors[] = lang('reg_err_email_taken');
    if ($errors) { set_flash(implode('<br>', $errors), 'error'); }
    else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $api_key = generate_api_key();
        $db->prepare('INSERT INTO users (username, email, password_hash, api_key) VALUES (?, ?, ?, ?)')->execute([$username, $email, $hash, $api_key]);
        set_flash(lang('reg_success'), 'success');
        redirect('/giris.php');
    }
}
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html><html lang="<?= $cl ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?= lang('meta_title_register') ?></title><link rel="stylesheet" href="/assets/css/style.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="auth-main"><div class="auth-card">
<div class="auth-header"><h1><?= lang('register_title') ?></h1><p><?= lang('register_subtitle') ?></p></div>
<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div><?php endif; ?>
<form method="POST" class="form"><?= csrf_field() ?>
<div class="form-group"><label><?= lang('reg_username_label') ?></label><input type="text" name="username" required minlength="3" value="<?= e($_POST['username'] ?? '') ?>"></div>
<div class="form-group"><label><?= lang('reg_email_label') ?></label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
<div class="form-group"><label><?= lang('reg_password_label') ?></label><input type="password" name="password" required minlength="6"></div>
<div class="form-group"><label><?= lang('reg_password2_label') ?></label><input type="password" name="password2" required></div>
<button type="submit" class="btn btn-primary btn-block"><?= lang('reg_btn') ?></button>
</form>
<div class="auth-footer"><p><?= lang('has_account') ?> <a href="/giris.php"><?= lang('login_link') ?></a></p></div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?></body></html>