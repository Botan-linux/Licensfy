<?php
require_once __DIR__ . '/includes/config.php';
require_login();
$user = current_user();
$db = getDB();

// Get products
$stmt = $db->prepare('SELECT p.*, (SELECT COUNT(*) FROM licenses WHERE product_id=p.id) as total_licenses, (SELECT COUNT(*) FROM licenses WHERE product_id=p.id AND status="active") as active_licenses FROM products p WHERE p.user_id = ? ORDER BY p.created_at DESC');
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();

// Handle delete
if (isset($_GET['delete'])) {
    verify_csrf_get();
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare('SELECT id FROM products WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    if ($stmt->fetch()) {
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        log_activity($user['id'], 'product_delete', 'Urun silindi: #' . $id);
        set_flash(lang('product_deleted'), 'success');
        redirect('/urunler.php');
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
    <title><?= lang('meta_title_products') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div><h1><?= lang('my_products') ?></h1><p><?= lang('product_count', ['n' => count($products)]) ?></p></div>
        <a href="/urun_ekle.php" class="btn btn-primary"><?= lang('btn_new_product') ?></a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <?php if ($products): ?>
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= lang('th_product') ?></th>
                        <th><?= lang('th_version') ?></th>
                        <th><?= lang('th_licenses') ?></th>
                        <th><?= lang('th_active') ?></th>
                        <th><?= lang('th_created') ?></th>
                        <th><?= lang('th_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><strong><?= e($p['name']) ?></strong><br><small style="color:var(--text-muted)"><?= e(mb_substr($p['description'], 0, 50)) ?></small></td>
                        <td><span class="badge badge-blue">v<?= e($p['version']) ?></span></td>
                        <td><?= $p['total_licenses'] ?></td>
                        <td><span class="badge badge-green"><?= $p['active_licenses'] ?></span></td>
                        <td><?= format_date($p['created_at']) ?></td>
                        <td class="actions">
                            <a href="/urun.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline"><?= lang('btn_detail') ?></a>
                            <a href="/urun.php?id=<?= $p['id'] ?>&tab=licenses" class="btn btn-sm btn-primary"><?= lang('btn_licenses') ?></a>
                            <a href="/urunler.php?delete=<?= $p['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang('confirm_delete') ?>')"><?= lang('btn_delete') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <p><?= lang('no_products_yet') ?></p>
            <a href="/urun_ekle.php" class="btn btn-primary"><?= lang('add_first_product') ?></a>
        </div>
    </div></div>
    <?php endif; ?>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>