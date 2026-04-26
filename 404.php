<?php
require_once __DIR__ . "/includes/config.php";
$cl = current_lang();
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Licensfy</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            text-align: center;
            padding: 40px 20px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        .error-desc {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 400px;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
    </style>
</head>
<body>
<?php include __DIR__ . "/includes/header.php"; ?>
<main class="error-container">
    <div class="error-code">404</div>
    <h1 class="error-title"><?= $cl === "tr" ? "Sayfa Bulunamadi" : "Page Not Found" ?></h1>
    <p class="error-desc"><?= $cl === "tr" ? "Aradiginiz sayfa taslanmis, silinmis veya hic olmamis olabilir." : "The page you are looking for might have been removed, had its name changed, or is temporarily unavailable." ?></p>
    <div class="error-actions">
        <a href="/" class="btn btn-primary btn-lg"><?= lang("nav_home") ?></a>
        <a href="/giris.php" class="btn btn-outline btn-lg"><?= lang("nav_login") ?></a>
    </div>
</main>
<?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>