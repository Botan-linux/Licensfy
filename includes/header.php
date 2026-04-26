<nav class="navbar">
<div class="container nav-content">
    <a href="/" class="nav-brand">Licensfy</a>
    <div class="nav-links">
        <a href="/"><?= lang("nav_home") ?></a>
        <?php if (is_logged_in()): ?>
            <a href="/dashboard"><?= lang("nav_dashboard") ?></a>
            <a href="/<?= lang('url_products') ?>"><?= lang("nav_products") ?></a>
            <a href="/<?= lang('url_licenses') ?>"><?= lang("nav_licenses") ?></a>
            <a href="/<?= lang('url_blacklist') ?>"><?= lang("nav_blacklist") ?></a>
            <a href="/<?= lang('url_webhooks') ?>"><?= lang("nav_webhooks") ?></a>
            <a href="/<?= lang('url_api_docs') ?>"><?= lang("nav_api_docs") ?></a>
            <a href="/<?= lang('url_logs') ?>"><?= lang("nav_logs") ?></a>
            <a href="/<?= lang('url_settings') ?>"><?= lang("nav_settings") ?></a>
        <?php endif; ?>
    </div>
    <div class="nav-actions">
        <?php
        $cl = current_lang();
        $switch_lang = ($cl === "tr") ? "en" : "tr";
        $switch_label = ($cl === "tr") ? "EN" : "TR";
        ?>
        <a href="?set_lang=<?= $switch_lang ?>" class="lang-switch"><?= $switch_label ?></a>
        <?php if (is_logged_in()): ?>
            <span class="user-badge"><?= e(current_user()["username"]) ?></span>
            <a href="/cikis" class="btn btn-outline btn-sm"><?= lang("nav_logout") ?></a>
        <?php else: ?>
            <a href="/giris" class="btn btn-outline btn-sm"><?= lang("nav_login") ?></a>
            <a href="/kayit" class="btn btn-primary btn-sm"><?= lang("nav_signup") ?></a>
        <?php endif; ?>
    </div>
</div>
</nav>
