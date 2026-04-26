<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $version = trim($_POST['version'] ?? '1.0.0');
    $errors = [];
    if (strlen($name) < 2) $errors[] = lang('err_product_name');
    if (strlen($version) < 1) $errors[] = lang('err_product_version');
    if ($errors) {
        set_flash(implode('<br>', $errors), 'error');
    } else {
        $stmt = $db->prepare('INSERT INTO products (name, description, version, user_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $description, $version, $user['id']]);
        $pid = $db->lastInsertId();
        log_activity($user['id'], 'product_create', 'Urun olusturuldu: ' . $name);
        set_flash(lang('product_created'), 'success');
        redirect('/urun.php?id=' . $pid);
    }
}
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_add_product') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div><h1><?= lang('new_product') ?></h1><p><?= lang('new_product_desc') ?></p></div>
        <a href="/urunler.php" class="btn btn-outline"><?= lang('btn_back') ?></a>
    </div>

    <div class="card"><div class="card-body" style="max-width:600px;">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label><?= lang('product_name_label') ?></label>
                <input type="text" name="name" required placeholder="<?= lang('product_name_ph') ?>" value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= lang('product_desc_label') ?></label>
                <textarea name="description" placeholder="<?= lang('product_desc_ph') ?>"><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label><?= lang('product_version_label') ?></label>
                <input type="text" name="version" placeholder="<?= lang('product_version_ph') ?>" value="<?= e($_POST['version'] ?? '1.0.0') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= lang('btn_create_product') ?></button>
        </form>
    </div></div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>