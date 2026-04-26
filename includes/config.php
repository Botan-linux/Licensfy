<?php
define("DB_HOST", getenv("LICENSFY_DB_HOST") ?: "");
define("DB_NAME", getenv("LICENSFY_DB_NAME") ?: "");
define("DB_USER", getenv("LICENSFY_DB_USER") ?: "");
define("DB_PASS", getenv("LICENSFY_DB_PASS") ?: "");

// URL Ayarı: Tünel veya farklı hostlar için dinamik algılama
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define("SITE_URL", "{$protocol}://{$host}");

function getDB() {
    static $db = null;
    if ($db === null) {
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_NAME);
        $db = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $db->exec("SET NAMES utf8mb4");
    }
    return $db;
}

session_start();

// ===== DIL DEGISME TALEBI =====
if (isset($_GET["set_lang"]) && in_array($_GET["set_lang"], ["tr", "en"])) {
    $_SESSION["lang"] = $_GET["set_lang"];
    $back = $_SERVER["HTTP_REFERER"] ?? "/";
    $back = preg_replace("/[?&]set_lang=[^&]*/", "", $back);
    header("Location: " . $back);
    exit;
}

// ===== DIL SISTEMI =====
require_once __DIR__ . "/geo.php";

$GLOBALS["lang"] = [];

function init_lang() {
    $lang_code = detect_lang();
    $lang_file = __DIR__ . "/lang/" . $lang_code . ".php";
    if (file_exists($lang_file)) {
        $lang = [];
        include $lang_file;
        $GLOBALS["lang"] = $lang;
    }
    return $lang_code;
}

function lang($key, $replacements = []) {
    $text = $GLOBALS["lang"][$key] ?? $key;
    foreach ($replacements as $k => $v) {
        $text = str_replace("{" . $k . "}", $v, $text);
    }
    return $text;
}

function current_lang() {
    return $_SESSION["lang"] ?? "en";
}

// Initialize language
init_lang();

// ===== GUVENLIK BASLIKLARI =====
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Type: text/html; charset=utf-8");

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function csrf_field() {
    return chr(60)."input type=".chr(34)."hidden".chr(34)." name=".chr(34)."csrf_token".chr(34)." value=".chr(34).htmlspecialchars($_SESSION["csrf_token"]).chr(34).chr(62);
}

function verify_csrf() {
    if (empty($_POST["csrf_token"]) || $_POST["csrf_token"] !== $_SESSION["csrf_token"]) {
        die(lang("csrf_error"));
    }
}

function verify_csrf_get() {
    if (empty($_GET["csrf"]) || $_GET["csrf"] !== $_SESSION["csrf_token"]) {
        die(lang("csrf_error"));
    }
}

function require_login() {
    if (empty($_SESSION["user_id"])) {
        header("Location: /giris.php");
        exit;
    }
}

function current_user() {
    if (empty($_SESSION["user_id"])) return null;
    static $cache = [];
    $uid = (int)$_SESSION["user_id"];
    if (isset($cache[$uid])) return $cache[$uid];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    $cache[$uid] = $user ?: null;
    return $cache[$uid];
}

function is_logged_in() {
    return !empty($_SESSION["user_id"]);
}

function set_flash($message, $type = "success") {
    $_SESSION["flash"] = ["message" => $message, "type" => $type];
}

function get_flash() {
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}

function log_activity($user_id, $action, $description = "", $ip = "") {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $ip ?: ($_SERVER["REMOTE_ADDR"] ?? "")]);
}

function blacklist_types() {
    return [
        'license_key' => 'license_key',
        'ip_address' => 'ip_address',
        'hwid' => 'hwid',
        'customer_email' => 'customer_email',
    ];
}

function normalize_blacklist_value(string $type, string $value) {
    $value = trim($value);
    if ($type === 'license_key' || $type === 'customer_email') {
        $value = strtolower($value);
    }
    return $value;
}

function find_blacklist_entry(PDO $db, int $userId, array $checks) {
    $allowedTypes = blacklist_types();

    foreach ($checks as $type => $value) {
        if (!isset($allowedTypes[$type])) {
            continue;
        }

        $normalizedValue = normalize_blacklist_value($type, (string)$value);
        if ($normalizedValue === '') {
            continue;
        }

        $stmt = $db->prepare('SELECT * FROM blacklist_entries WHERE user_id = ? AND type = ? AND value = ? LIMIT 1');
        $stmt->execute([$userId, $type, $normalizedValue]);
        $entry = $stmt->fetch();

        if ($entry) {
            return $entry;
        }
    }

    return null;
}

function generate_license_key() {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $groups = [];
    for ($g = 0; $g < 4; $g++) {
        $group = "";
        for ($i = 0; $i < 4; $i++) {
            $group .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $groups[] = $group;
    }
    return implode("-", $groups);
}

function generate_api_key() {
    return bin2hex(random_bytes(32));
}

function webhook_events() {
    return [
        'license.created' => 'license.created',
        'license.revoked' => 'license.revoked',
        'license.activated' => 'license.activated',
        'license.validated' => 'license.validated',
        'license.validation_failed' => 'license.validation_failed',
    ];
}

function login_rate_limit_status(PDO $db, string $ip, int $maxAttempts = 5, int $lockMinutes = 15) {
    $stmt = $db->prepare('SELECT * FROM login_attempts WHERE ip_address = ? LIMIT 1');
    $stmt->execute([$ip]);
    $record = $stmt->fetch();

    if (!$record) {
        return [
            'locked' => false,
            'remaining' => $maxAttempts,
            'retry_after' => 0,
        ];
    }

    if (!empty($record['locked_until']) && strtotime($record['locked_until']) > time()) {
        return [
            'locked' => true,
            'remaining' => 0,
            'retry_after' => strtotime($record['locked_until']) - time(),
        ];
    }

    if (!empty($record['locked_until']) && strtotime($record['locked_until']) <= time()) {
        $db->prepare('UPDATE login_attempts SET attempts_count = 0, locked_until = NULL, first_attempt_at = NOW(), last_attempt_at = NOW() WHERE ip_address = ?')->execute([$ip]);
        return [
            'locked' => false,
            'remaining' => $maxAttempts,
            'retry_after' => 0,
        ];
    }

    return [
        'locked' => false,
        'remaining' => max(0, $maxAttempts - (int)$record['attempts_count']),
        'retry_after' => 0,
    ];
}

function record_login_failure(PDO $db, string $ip, int $maxAttempts = 5, int $lockMinutes = 15) {
    $stmt = $db->prepare('SELECT * FROM login_attempts WHERE ip_address = ? LIMIT 1');
    $stmt->execute([$ip]);
    $record = $stmt->fetch();

    if (!$record) {
        $db->prepare('INSERT INTO login_attempts (ip_address, attempts_count, first_attempt_at, last_attempt_at) VALUES (?, 1, NOW(), NOW())')->execute([$ip]);
        return;
    }

    $attempts = (int)$record['attempts_count'] + 1;
    $lockedUntil = null;
    if ($attempts >= $maxAttempts) {
        $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
    }

    $db->prepare('UPDATE login_attempts SET attempts_count = ?, last_attempt_at = NOW(), locked_until = ? WHERE ip_address = ?')->execute([$attempts, $lockedUntil, $ip]);
}

function clear_login_failures(PDO $db, string $ip) {
    $db->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}

function format_date($date) {
    if (!$date) return "-";
    return date("d.m.Y H:i", strtotime($date));
}

function e($str) {
    return htmlspecialchars($str ?? "", ENT_QUOTES, "UTF-8");
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}
