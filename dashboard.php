<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();
$stmt = $db->prepare('SELECT COUNT(*) as c FROM products WHERE user_id = ?');
$stmt->execute([$user['id']]); $tp = $stmt->fetch()['c'];
$stmt = $db->prepare('SELECT COUNT(*) as c FROM licenses l JOIN products p ON l.product_id=p.id WHERE p.user_id = ?');
$stmt->execute([$user['id']]); $tl = $stmt->fetch()['c'];
$stmt = $db->prepare('SELECT COUNT(*) as c FROM licenses l JOIN products p ON l.product_id=p.id WHERE p.user_id = ? AND l.status = "active"');
$stmt->execute([$user['id']]); $al = $stmt->fetch()['c'];
$stmt = $db->prepare('SELECT COUNT(*) as c FROM licenses l JOIN products p ON l.product_id=p.id WHERE p.user_id = ? AND l.status = "revoked"');
$stmt->execute([$user['id']]); $rv = $stmt->fetch()['c'];
$stmt = $db->prepare('SELECT COUNT(*) as c FROM licenses l JOIN products p ON l.product_id=p.id WHERE p.user_id = ? AND l.status = "expired"');
$stmt->execute([$user['id']]); $ex = $stmt->fetch()['c'];
$stmt = $db->prepare('SELECT p.*, (SELECT COUNT(*) FROM licenses WHERE product_id=p.id AND status="active") as ac FROM products p WHERE p.user_id=? ORDER BY p.created_at DESC LIMIT 5');
$stmt->execute([$user['id']]); $rproducts = $stmt->fetchAll();
$stmt = $db->prepare('SELECT * FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$user['id']]); $logs = $stmt->fetchAll();
$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html><html lang="<?= $cl ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?= lang('meta_title_dashboard') ?></title><link rel="stylesheet" href="/assets/css/style.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
<div class="page-header"><div><h1><?= lang('welcome', ['name' => e($user['username'])]) ?></h1><p><?= lang('dashboard_subtitle') ?></p></div><a href="/urun_ekle.php" class="btn btn-primary"><?= lang('btn_new_product') ?></a></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon stat-blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div><div class="stat-info"><span class="stat-val"><?= $tp ?></span><span class="stat-lbl"><?= lang('stat_products') ?></span></div></div>
<div class="stat-card"><div class="stat-icon stat-green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg></div><div class="stat-info"><span class="stat-val"><?= $tl ?></span><span class="stat-lbl"><?= lang('stat_licenses') ?></span></div></div>
<div class="stat-card"><div class="stat-icon stat-emerald"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div class="stat-info"><span class="stat-val"><?= $al ?></span><span class="stat-lbl"><?= lang('stat_active') ?></span></div></div>
<div class="stat-card"><div class="stat-icon stat-red"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div class="stat-info"><span class="stat-val"><?= $rv ?></span><span class="stat-lbl"><?= lang('stat_revoked') ?></span></div></div>
<div class="stat-card"><div class="stat-icon stat-orange"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="stat-info"><span class="stat-val"><?= $ex ?></span><span class="stat-lbl"><?= lang('stat_expired') ?></span></div></div>
</div>
<div class="grid-2">
<div class="card"><div class="card-header"><h2><?= lang('recent_products') ?></h2><a href="/urunler.php" class="card-link"><?= lang('view_all') ?> &rarr;</a></div><div class="card-body">
<?php if ($rproducts): foreach ($rproducts as $p): ?>
<a href="/urun.php?id=<?= $p['id'] ?>" class="list-item"><div><strong><?= e($p['name']) ?></strong> <small>v<?= e($p['version']) ?></small></div><span class="badge badge-green"><?= lang('active_badge', ['n' => $p['ac']]) ?></span></a>
<?php endforeach; else: ?>
<div class="empty-state"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.1; margin-bottom: 20px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg><p><?= lang('no_products') ?></p><a href="/urun_ekle.php" class="btn btn-primary btn-sm"><?= lang('add_product') ?></a></div>
<?php endif; ?>
</div></div>
<div class="card"><div class="card-header"><h2><?= lang('activities') ?></h2></div><div class="card-body">
<?php if ($logs): foreach ($logs as $l): ?>
<div class="activity-item"><div class="activity-dot"></div><div class="activity-info"><strong><?= e($l['action']) ?></strong><small><?= e($l['description']) ?></small><small class="muted"><?= format_date($l['created_at']) ?></small></div></div>
<?php endforeach; else: ?>
<div class="empty-state"><p><?= lang('no_activities') ?></p></div>
<?php endif; ?>
</div></div>
</div></div></main>
<?php include __DIR__ . '/includes/footer.php'; ?></body></html>
