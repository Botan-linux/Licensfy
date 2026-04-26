<?php
require_once __DIR__ . '/includes/config.php';

// Landing page - no login required
if (is_logged_in()) redirect('/dashboard.php');

// Get some stats for the landing page
$dbError = null;
$totalUsers = 0;
$totalLicenses = 0;
$totalProducts = 0;

try {
    $db = getDB();
    $totalUsers = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalLicenses = $db->query('SELECT COUNT(*) FROM licenses')->fetchColumn();
    $totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_home') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($dbError): ?>
<div class="container" style="padding-top:24px;">
    <div class="alert alert-error">
        <?= $cl === 'tr' ? 'Veritabani baglantisi hazir degil. Once ayarlari girip kurulumu calistirin.' : 'Database connection is not ready. Configure credentials and run setup first.' ?>
        <a href="/setup_mysql.php" style="margin-left:8px; font-weight:700;"><?= $cl === 'tr' ? 'Kurulumu Ac' : 'Open Setup' ?></a>
    </div>
</div>
<?php endif; ?>

<section class="hero">
    <h1><?= lang('hero_title_1') ?><br><span class="gradient"><?= lang('hero_title_2') ?></span></h1>
    <p><?= lang('hero_desc') ?></p>
    <div class="hero-actions">
        <a href="/kayit.php" class="btn btn-primary btn-lg"><?= lang('hero_start') ?></a>
        <a href="/giris.php" class="btn btn-outline btn-lg"><?= lang('hero_login') ?></a>
    </div>
</section>

<section class="features">
    <h2><?= lang('why_licensfy') ?></h2>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div>
            <h3><?= lang('feat_license_mgmt') ?></h3>
            <p><?= lang('feat_license_mgmt_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
            <h3><?= lang('feat_api_verify') ?></h3>
            <p><?= lang('feat_api_verify_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <h3><?= lang('feat_pricing') ?></h3>
            <p><?= lang('feat_pricing_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
            <h3><?= lang('feat_stats') ?></h3>
            <p><?= lang('feat_stats_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></div>
            <h3><?= lang('feat_fast') ?></h3>
            <p><?= lang('feat_fast_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 0 1 0 4h-.09c-.658.003-1.25.396-1.51 1z"/></svg></div>
            <h3><?= lang('feat_dev_tools') ?></h3>
            <p><?= lang('feat_dev_tools_desc') ?></p>
        </div>
    </div>
</section>

<section class="features">
    <h2><?= lang('how_it_works') ?></h2>
    <div class="feature-grid" style="max-width:800px;margin:0 auto;">
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:48px;font-weight:900;color:var(--accent);margin-bottom:8px;">1</div>
            <h3><?= lang('step1_title') ?></h3>
            <p><?= lang('step1_desc') ?></p>
        </div>
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:48px;font-weight:900;color:var(--accent);margin-bottom:8px;">2</div>
            <h3><?= lang('step2_title') ?></h3>
            <p><?= lang('step2_desc') ?></p>
        </div>
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:48px;font-weight:900;color:var(--accent);margin-bottom:8px;">3</div>
            <h3><?= lang('step3_title') ?></h3>
            <p><?= lang('step3_desc') ?></p>
        </div>
    </div>
</section>

<section class="features">
    <h2><?= $cl === 'tr' ? 'Tek Cekirdek, Cok Istemci' : 'One Core, Many Clients' ?></h2>
    <div class="feature-grid">
        <div class="feature-card">
            <h3><?= $cl === 'tr' ? 'GUI Uygulamalari' : 'GUI Applications' ?></h3>
            <p><?= $cl === 'tr' ? 'Kullanici lisans anahtarini girer, istemci sunucuya sorar ve sonucu ekranda uygular.' : 'The user enters a license key, the client asks the server, and applies the result locally.' ?></p>
        </div>
        <div class="feature-card">
            <h3><?= $cl === 'tr' ? 'Discord Botlari' : 'Discord Bots' ?></h3>
            <p><?= $cl === 'tr' ? 'Komut veya panel uzerinden girilen anahtar ayni cekirdek ile dogrulanir.' : 'Keys entered through commands or panels are validated by the same core.' ?></p>
        </div>
        <div class="feature-card">
            <h3><?= $cl === 'tr' ? 'Ozel Istemciler' : 'Custom Clients' ?></h3>
            <p><?= $cl === 'tr' ? 'Launcher, updater, arka plan servisleri veya ozel entegrasyonlar ayni endpointi kullanir.' : 'Launchers, updaters, background services and custom integrations use the same endpoint.' ?></p>
        </div>
    </div>
</section>

<div class="container" style="padding-bottom:60px;">
    <div style="text-align:center;margin-bottom:20px;">
        <h2 style="font-size:24px;margin-bottom:12px;"><?= lang('integration_example') ?></h2>
        <p style="color:var(--text-secondary);font-size:14px;"><?= lang('integration_example_desc') ?></p>
    </div>
    <div class="code-block">
<span style="color:#686880;"><?= lang('code_comment') ?></span>
<span style="color:#6c5ce7;">import</span> requests

license_key = <span style="color:#00d68f;">open("license.txt")</span>.read().strip()
response = requests.post(
    <span style="color:#00d68f;">"https://your-domain.com/api/validate.php"</span>,
    json={<span style="color:#00d68f;">"license_key"</span>: license_key}
)
<span style="color:#6c5ce7;">if</span> response.json()[<span style="color:#00d68f;">"valid"</span>]:
    <span style="color:#6c5ce7;">print</span>(<span style="color:#00d68f;">"<?= lang('license_valid') ?>"</span>)
<span style="color:#6c5ce7;">else</span>:
    <span style="color:#6c5ce7;">print</span>(<span style="color:#ff6b6b;">"<?= lang('license_invalid') ?>"</span>)
    <span style="color:#6c5ce7;">exit</span>(<span style="color:#ffa726;">1</span>)
    </div>
</div>

<!-- Rules & Policy Section -->
<section class="features" style="background:var(--bg-secondary);padding:60px 0;">
    <div class="container">
        <div style="text-align:center;margin-bottom:40px;">
            <h2 style="font-size:28px;margin-bottom:8px;"><?= lang('lp_rules_title') ?></h2>
            <p style="color:var(--text-secondary);"><?= lang('lp_rules_subtitle') ?></p>
        </div>
        <div class="grid-2" style="gap:16px;">
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(108,92,231,0.15);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700;">1</div>
                    <div>
                        <h4 style="margin-bottom:4px;"><?= $cl === 'tr' ? 'Hesap Yönetimi' : 'Account Management' ?></h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_account') ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(108,92,231,0.15);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700;">2</div>
                    <div>
                        <h4 style="margin-bottom:4px;"><?= $cl === 'tr' ? 'API Kullanımı' : 'API Usage' ?></h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_api') ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(108,92,231,0.15);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700;">3</div>
                    <div>
                        <h4 style="margin-bottom:4px;"><?= $cl === 'tr' ? 'Lisans Kuralları' : 'License Rules' ?></h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_license') ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(108,92,231,0.15);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700;">4</div>
                    <div>
                        <h4 style="margin-bottom:4px;">Blacklist</h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_blacklist') ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(108,92,231,0.15);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:700;">5</div>
                    <div>
                        <h4 style="margin-bottom:4px;">Webhook</h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_webhook') ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="border:1px solid var(--border);">
                <div class="card-body" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="min-width:36px;height:36px;border-radius:8px;background:rgba(255,107,107,0.15);display:flex;align-items:center;justify-content:center;color:var(--danger);font-weight:700;">!</div>
                    <div>
                        <h4 style="margin-bottom:4px;"><?= $cl === 'tr' ? 'Yasaklı Kullanım' : 'Forbidden Usage' ?></h4>
                        <p style="color:var(--text-secondary);font-size:13px;margin:0;"><?= lang('lp_rule_forbidden') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Security Section -->
<section class="features" style="padding:60px 0;">
    <div class="container">
        <div style="text-align:center;margin-bottom:40px;">
            <h2 style="font-size:28px;margin-bottom:8px;"><?= lang('lp_security_title') ?></h2>
            <p style="color:var(--text-secondary);"><?= lang('lp_security_subtitle') ?></p>
        </div>
        <div class="feature-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
            <?php
            $secItems = [
                ['lp_sec_csrf', 'lp_sec_csrf_desc', 'shield'],
                ['lp_sec_sql', 'lp_sec_sql_desc', 'lock'],
                ['lp_sec_hmac', 'lp_sec_hmac_desc', 'key'],
                ['lp_sec_rate', 'lp_sec_rate_desc', 'clock'],
                ['lp_sec_bcrypt', 'lp_sec_bcrypt_desc', 'hash'],
                ['lp_sec_headers', 'lp_sec_headers_desc', 'header'],
                ['lp_sec_hwid', 'lp_sec_hwid_desc', 'cpu'],
            ];
            $icons = [
                'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                'key' => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
                'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                'hash' => '<line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/>',
                'header' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/><line x1="12" y1="2" x2="12" y2="22"/>',
                'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>',
            ];
            foreach ($secItems as $i => $item):
                $svgIcon = $icons[$item[2]] ?? $icons['shield'];
            ?>
            <div class="feature-card" style="padding:20px;text-align:center;">
                <div class="icon" style="margin:0 auto 12px;width:40px;height:40px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $svgIcon ?></svg>
                </div>
                <h3 style="font-size:15px;margin-bottom:6px;"><?= lang($item[0]) ?></h3>
                <p style="font-size:13px;color:var(--text-secondary);"><?= lang($item[1]) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:32px;">
            <p style="color:var(--text-muted);font-size:12px;"><?= lang('lp_rule_privacy') ?></p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
