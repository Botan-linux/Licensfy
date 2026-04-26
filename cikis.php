<?php
require_once __DIR__ . '/includes/config.php';

// Oturumu guvenli bir sekilde sonlandir
session_unset();
session_destroy();

// Session cookie'sini temizle
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: /");
exit;
