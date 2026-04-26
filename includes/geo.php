<?php
/**
 * IP-based Geo Location - Simple: check once, user can switch manually
 * Uses ip-api.com (free, no API key, 45 req/min)
 */
function detect_country() {
    if (php_sapi_name() === "cli") return "US";
    
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";
    if (!$ip || $ip === "127.0.0.1" || $ip === "::1") return "US";
    
    $country = "US";
    try {
        $ctx = stream_context_create(["http" => ["timeout" => 3]]);
        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,countryCode", false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && ($data["status"] ?? "") === "success") {
                $country = strtoupper($data["countryCode"] ?? "US");
            }
        }
    } catch (Exception $e) {}
    
    return $country;
}

function get_lang_from_country($country) {
    return ($country === "TR") ? "tr" : "en";
}

function detect_lang() {
    // Session"da dil kaydedildiyse onu kullan (manuel degisiklik veya onceki tespit)
    if (isset($_SESSION["lang"])) {
        return $_SESSION["lang"];
    }
    
    // Ilk giris: IP"den ulke tespit et, TR=turkce digerleri=ingilizce
    $country = detect_country();
    $lang = get_lang_from_country($country);
    $_SESSION["lang"] = $lang;
    
    return $lang;
}
