<?php
/**
 * Licensfy - Robust Router (v3.1)
 * Fixed 404 issue and improved path resolution.
 */

// UTF-8 Desteği
mb_internal_encoding("UTF-8");

$uri = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

// 1. Statik Dosyalar (CSS, JS, Resim) - PHP Server doğrudan sunmalı
if (php_sapi_name() === "cli-server") {
    $file = __DIR__ . $uri;
    if (is_file($file) && strpos($uri, '.php') === false) {
        return false;
    }
}

// 2. Dinamik Rotalar (/ürün/2 veya /urun/2)
if (preg_match('/^\/(ürün|urun)\/([0-9]+)$/u', $uri, $matches)) {
    $_GET['id'] = (int)$matches[2];
    if (file_exists(__DIR__ . '/urun.php')) {
        require __DIR__ . '/urun.php';
        exit;
    }
}

// 3. Uzantısız veya PHP Dosya Yönlendirmeleri
$path = ltrim($uri, '/');
if ($uri === '/') {
    $file_to_load = __DIR__ . '/index.php';
} else {
    // Önce direkt .php olarak bak, sonra uzantısız bak
    if (file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
        $file_to_load = __DIR__ . $uri;
    } elseif (file_exists(__DIR__ . '/' . $path . '.php')) {
        $file_to_load = __DIR__ . '/' . $path . '.php';
    } else {
        $file_to_load = null;
    }
}

// Güvenlik: includes veya data dizinine direkt erişimi engelle
if ($file_to_load && (strpos($file_to_load, '/includes/') !== false || strpos($file_to_load, '/data/') !== false)) {
    http_response_code(403);
    die("Forbidden Area");
}

// 4. Dosya Yükleme veya 404
if ($file_to_load && file_exists($file_to_load)) {
    require $file_to_load;
    exit;
} else {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) {
        require __DIR__ . '/404.php';
    } else {
        echo "<h1>404 - Sayfa Bulunamadı</h1>";
    }
    exit;
}
