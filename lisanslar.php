<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();

// Get all licenses with product name
$stmt = $db->prepare('SELECT l.*, p.name as product_name, p.version as product_version FROM licenses l JOIN products p ON l.product_id = p.id WHERE p.user_id = ? ORDER BY l.created_at DESC');
$stmt->execute([$user['id']]);
$licenses = $stmt->fetchAll();

// Handle search
$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $db->prepare('SELECT l.*, p.name as product_name, p.version as product_version FROM licenses l JOIN products p ON l.product_id = p.id WHERE p.user_id = ? AND (l.license_key LIKE ? OR l.customer_name LIKE ? OR l.customer_email LIKE ? OR p.name LIKE ?) ORDER BY l.created_at DESC');
    $like = "%$search%";
    $stmt->execute([$user['id'], $like, $like, $like, $like]);
    $licenses = $stmt->fetchAll();
}

// Handle bulk revoke
if (isset($_POST['bulk_revoke'])) {
    verify_csrf();
    $ids = $_POST['ids'] ?? [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_map('intval', $ids);
        $params[] = $user['id'];
        $db->prepare("UPDATE licenses SET status = 'revoked' WHERE id IN ($placeholders) AND user_id = ?")->execute($params);
        log_activity($user['id'], 'bulk_revoke', count($ids) . ' lisans toplu iptal');
        set_flash(lang('bulk_revoked', ['n' => count($ids)]), 'success');
        redirect('/lisanslar.php');
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
    <title><?= lang('meta_title_licenses') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div><h1><?= lang('all_licenses') ?></h1><p><?= lang('license_count', ['n' => count($licenses)]) ?></p></div>
        <a href="/urunler.php" class="btn btn-outline"><?= lang('btn_back_to_products') ?></a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;">
        <input type="text" name="q" placeholder="<?= lang('search_ph') ?>" value="<?= e($search) ?>" style="flex:1;background:var(--bg-input);border:1px solid var(--border);color:var(--text-primary);padding:10px 14px;border-radius:var(--radius-sm);font-family:inherit;font-size:14px;outline:none;">
        <button type="submit" class="btn btn-primary"><?= lang('btn_search') ?></button>
        <?php if ($search): ?><a href="/lisanslar.php" class="btn btn-outline"><?= lang('btn_clear') ?></a><?php endif; ?>
    </form>

    <?php if ($licenses): ?>
    <form method="POST" id="bulkForm">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header">
            <h2><?= $search ? lang('search_results') : lang('all_licenses_title') ?> (<?= count($licenses) ?>)</h2>
            <button type="submit" name="bulk_revoke" value="1" class="btn btn-sm btn-danger" onclick="if(!confirm('<?= lang('confirm_bulk_revoke') ?>'))return false;"><?= lang('btn_bulk_revoke') ?></button>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th><input type="checkbox" id="selectAll"></th><th><?= lang('th_license_key') ?></th><th><?= lang('th_product') ?></th><th><?= lang('th_customer') ?></th><th><?= lang('th_status') ?></th><th><?= lang('th_created') ?></th><th><?= lang('th_expiry') ?></th><th><?= lang('th_actions') ?></th></tr></thead>
                <tbody>
                <?php foreach ($licenses as $l): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?= $l['id'] ?>" class="lic-check"></td>
                        <td><span class="license-key" onclick="navigator.clipboard.writeText('<?= e($l['license_key']) ?>')"><?= e($l['license_key']) ?></span></td>
                        <td><a href="/urun.php?id=<?= $l['product_id'] ?>"><?= e($l['product_name']) ?></a> <small>v<?= e($l['product_version']) ?></small></td>
                        <td><?= e($l['customer_name']) ?: '-' ?><br><small><?= e($l['customer_email']) ?: '' ?></small></td>
                        <td><?php
                            $st = $l['status'];
                            $cls = $st === 'active' ? 'badge-green' : ($st === 'revoked' ? 'badge-red' : 'badge-orange');
                            $lbl = $st === 'active' ? lang('status_active') : ($st === 'revoked' ? lang('status_revoked') : lang('status_expired'));
                            echo "<span class=\"badge $cls\">$lbl</span>";
                        ?></td>
                        <td><?= format_date($l['created_at']) ?></td>
                        <td><?= $l['expires_at'] ? format_date($l['expires_at']) : lang('infinite') ?></td>
                        <td><a href="/urun.php?id=<?= $l['product_id'] ?>&tab=licenses" class="btn btn-sm btn-ghost"><?= lang('btn_detail') ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    </form>

    <script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.lic-check').forEach(c => c.checked = this.checked);
    });
    </script>
    <?php else: ?>
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <p><?= $search ? lang('no_results') : lang('no_licenses') ?></p>
            <a href="/urunler.php" class="btn btn-primary btn-sm"><?= lang('add_product') ?></a>
        </div>
    </div></div>
    <?php endif; ?>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>