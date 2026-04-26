<?php
require_once __DIR__ . '/includes/config.php';

$status = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        require __DIR__ . '/includes/database.php';
        $status = 'success';
        $message = current_lang() === 'tr'
            ? 'Kurulum tamamlandi. Simdi kayit olup urun ve lisans olusturabilirsiniz.'
            : 'Setup completed. You can now register and create products and licenses.';
    } catch (Throwable $e) {
        $status = 'error';
        $message = $e->getMessage();
    }
}

$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Licensfy</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= $cl === 'tr' ? 'Kurulum' : 'Setup' ?></h1>
                <p><?= $cl === 'tr' ? 'MySQL bilgilerini config dosyasina yazin, sonra kurulumu calistirin.' : 'Put your MySQL credentials in the config file, then run setup.' ?></p>
            </div>
        </div>

        <?php if ($status): ?>
        <div class="alert alert-<?= $status === 'success' ? 'success' : 'error' ?>" style="margin-bottom:20px;">
            <?= e($message) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="max-width:760px;">
                <div class="code-block" style="margin-bottom:20px;">
includes/config.php

DB_HOST = sqlXXX.infinityfree.com
DB_NAME = if0_xxxxxxxx_licensfy
DB_USER = if0_xxxxxxxx
DB_PASS = your_database_password
                </div>

                <form method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary"><?= $cl === 'tr' ? 'Tablolari Olustur' : 'Create Tables' ?></button>
                    <a href="/kayit.php" class="btn btn-outline"><?= $cl === 'tr' ? 'Kayit Sayfasina Git' : 'Open Register Page' ?></a>
                </form>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
