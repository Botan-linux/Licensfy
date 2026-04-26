<?php
require_once __DIR__ . '/includes/config.php';
require_login();

$db = getDB();
$user = current_user();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$action = trim($_GET['action'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = ['user_id = ?'];
$params = [$user['id']];

if ($action !== '') {
    $where[] = 'action = ?';
    $params[] = $action;
}

if ($search !== '') {
    $where[] = '(description LIKE ? OR ip_address LIKE ? OR action LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("SELECT * FROM activity_logs WHERE $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$limit, $offset]));
$logs = $stmt->fetchAll();

$total_stmt = $db->prepare("SELECT COUNT(*) FROM activity_logs WHERE $whereSql");
$total_stmt->execute($params);
$total_logs = $total_stmt->fetchColumn();
$total_pages = ceil($total_logs / $limit);
$action_stmt = $db->prepare('SELECT DISTINCT action FROM activity_logs WHERE user_id = ? ORDER BY action ASC');
$action_stmt->execute([$user['id']]);
$actions = $action_stmt->fetchAll(PDO::FETCH_COLUMN);

$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html><html lang="<?= $cl ?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?= lang('meta_title_logs') ?></title><link rel="stylesheet" href="/assets/css/style.css"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></head><body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div>
            <h1><?= lang("nav_logs") ?></h1>
            <p><?= lang("activities") ?></p>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">
        <select name="action" style="min-width:220px;">
            <option value=""><?= lang('log_filter_all_actions') ?></option>
            <?php foreach ($actions as $logAction): ?>
            <option value="<?= e($logAction) ?>" <?= $action === $logAction ? 'selected' : '' ?>><?= e($logAction) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= lang('log_search_placeholder') ?>" style="flex:1;min-width:220px;background:var(--bg-input);border:1px solid var(--border);color:var(--text-primary);padding:10px 14px;border-radius:var(--radius-sm);font-family:inherit;font-size:14px;outline:none;">
        <button type="submit" class="btn btn-primary"><?= lang('btn_search') ?></button>
        <?php if ($action !== '' || $search !== ''): ?><a href="/loglar.php" class="btn btn-outline"><?= lang('btn_clear') ?></a><?php endif; ?>
    </form>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= lang("th_created") ?></th>
                        <th><?= lang("th_action") ?></th>
                        <th><?= lang("th_description") ?></th>
                        <th><?= lang("th_ip_address") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:40px 0;color:var(--text-muted);"><?= lang("no_activities") ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:0.85rem;color:var(--text-muted);"><?= format_date($log['created_at']) ?></td>
                                <td><span class="badge badge-blue" style="font-size:0.75rem;"><?= e($log['action']) ?></span></td>
                                <td style="font-size:0.9rem;"><?= e($log['description']) ?></td>
                                <td style="font-family:monospace;font-size:0.85rem;"><?= e($log['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div style="margin-top:20px;display:flex;gap:5px;justify-content:center;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&action=<?= urlencode($action) ?>&q=<?= urlencode($search) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
