<?php
require_once __DIR__ . '/includes/config.php';
require_login();

$user = current_user();
$db = getDB();
$types = blacklist_types();

if (isset($_POST['add_entry'])) {
    verify_csrf();

    $type = $_POST['type'] ?? '';
    $value = trim($_POST['value'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $errors = [];

    if (!isset($types[$type])) {
        $errors[] = lang('blacklist_invalid_type');
    }

    $value = normalize_blacklist_value($type, $value);
    if ($value === '') {
        $errors[] = lang('blacklist_value_required');
    }

    if ($type === 'ip_address' && !filter_var($value, FILTER_VALIDATE_IP)) {
        $errors[] = lang('blacklist_invalid_ip');
    }

    if ($type === 'customer_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[] = lang('blacklist_invalid_email');
    }

    if ($errors) {
        set_flash(implode('<br>', $errors), 'error');
        redirect('/blacklist.php');
    }

    try {
        $stmt = $db->prepare('INSERT INTO blacklist_entries (user_id, type, value, reason) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $type, $value, $reason ?: null]);
        log_activity($user['id'], 'blacklist_add', $type . ': ' . $value);
        set_flash(lang('blacklist_added'), 'success');
    } catch (PDOException $e) {
        set_flash(lang('blacklist_duplicate'), 'error');
    }

    redirect('/blacklist.php');
}

if (isset($_POST['delete_entry'])) {
    verify_csrf();
    $entryId = (int)($_POST['entry_id'] ?? 0);

    $stmt = $db->prepare('SELECT * FROM blacklist_entries WHERE id = ? AND user_id = ?');
    $stmt->execute([$entryId, $user['id']]);
    $entry = $stmt->fetch();

    if ($entry) {
        $db->prepare('DELETE FROM blacklist_entries WHERE id = ? AND user_id = ?')->execute([$entryId, $user['id']]);
        log_activity($user['id'], 'blacklist_delete', $entry['type'] . ': ' . $entry['value']);
        set_flash(lang('blacklist_deleted'), 'success');
    }

    redirect('/blacklist.php');
}

$filter = $_GET['type'] ?? '';
if ($filter !== '' && !isset($types[$filter])) {
    $filter = '';
}

if ($filter !== '') {
    $stmt = $db->prepare('SELECT * FROM blacklist_entries WHERE user_id = ? AND type = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id'], $filter]);
} else {
    $stmt = $db->prepare('SELECT * FROM blacklist_entries WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
}
$entries = $stmt->fetchAll();

$counts = [];
foreach (array_keys($types) as $type) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM blacklist_entries WHERE user_id = ? AND type = ?');
    $stmt->execute([$user['id'], $type]);
    $counts[$type] = (int)$stmt->fetchColumn();
}

$flash = get_flash();
$cl = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $cl ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('meta_title_blacklist') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="main-content"><div class="container">
    <div class="page-header">
        <div>
            <h1><?= lang('blacklist_title') ?></h1>
            <p><?= lang('blacklist_subtitle') ?></p>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom:20px;"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-info"><span class="stat-val"><?= array_sum($counts) ?></span><span class="stat-lbl"><?= lang('blacklist_total') ?></span></div></div>
        <div class="stat-card"><div class="stat-info"><span class="stat-val"><?= $counts['license_key'] ?></span><span class="stat-lbl"><?= lang('blacklist_type_license_key') ?></span></div></div>
        <div class="stat-card"><div class="stat-info"><span class="stat-val"><?= $counts['ip_address'] ?></span><span class="stat-lbl"><?= lang('blacklist_type_ip_address') ?></span></div></div>
        <div class="stat-card"><div class="stat-info"><span class="stat-val"><?= $counts['hwid'] ?></span><span class="stat-lbl"><?= lang('blacklist_type_hwid') ?></span></div></div>
        <div class="stat-card"><div class="stat-info"><span class="stat-val"><?= $counts['customer_email'] ?></span><span class="stat-lbl"><?= lang('blacklist_type_customer_email') ?></span></div></div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2><?= lang('blacklist_add_title') ?></h2></div>
            <div class="card-body">
                <form method="POST" class="form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label><?= lang('blacklist_field_type') ?></label>
                        <select name="type" required>
                            <option value="license_key"><?= lang('blacklist_type_license_key') ?></option>
                            <option value="ip_address"><?= lang('blacklist_type_ip_address') ?></option>
                            <option value="hwid"><?= lang('blacklist_type_hwid') ?></option>
                            <option value="customer_email"><?= lang('blacklist_type_customer_email') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= lang('blacklist_field_value') ?></label>
                        <input type="text" name="value" required placeholder="<?= lang('blacklist_value_placeholder') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang('blacklist_field_reason') ?></label>
                        <textarea name="reason" rows="4" placeholder="<?= lang('blacklist_reason_placeholder') ?>"></textarea>
                    </div>
                    <button type="submit" name="add_entry" value="1" class="btn btn-primary btn-block"><?= lang('blacklist_add_btn') ?></button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2><?= lang('blacklist_rules_title') ?></h2></div>
            <div class="card-body">
                <div class="list-item"><span><?= lang('blacklist_type_license_key') ?></span><strong><?= lang('blacklist_rule_license_key') ?></strong></div>
                <div class="list-item"><span><?= lang('blacklist_type_ip_address') ?></span><strong><?= lang('blacklist_rule_ip_address') ?></strong></div>
                <div class="list-item"><span><?= lang('blacklist_type_hwid') ?></span><strong><?= lang('blacklist_rule_hwid') ?></strong></div>
                <div class="list-item"><span><?= lang('blacklist_type_customer_email') ?></span><strong><?= lang('blacklist_rule_customer_email') ?></strong></div>
            </div>
        </div>
    </div>

    <form method="GET" style="margin:20px 0;display:flex;gap:10px;">
        <select name="type" style="min-width:220px;">
            <option value=""><?= lang('blacklist_filter_all') ?></option>
            <option value="license_key" <?= $filter === 'license_key' ? 'selected' : '' ?>><?= lang('blacklist_type_license_key') ?></option>
            <option value="ip_address" <?= $filter === 'ip_address' ? 'selected' : '' ?>><?= lang('blacklist_type_ip_address') ?></option>
            <option value="hwid" <?= $filter === 'hwid' ? 'selected' : '' ?>><?= lang('blacklist_type_hwid') ?></option>
            <option value="customer_email" <?= $filter === 'customer_email' ? 'selected' : '' ?>><?= lang('blacklist_type_customer_email') ?></option>
        </select>
        <button type="submit" class="btn btn-primary"><?= lang('btn_search') ?></button>
        <?php if ($filter !== ''): ?><a href="/blacklist.php" class="btn btn-outline"><?= lang('btn_clear') ?></a><?php endif; ?>
    </form>

    <div class="card">
        <div class="card-header"><h2><?= lang('blacklist_entries_title') ?> (<?= count($entries) ?>)</h2></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><?= lang('blacklist_field_type') ?></th>
                        <th><?= lang('blacklist_field_value') ?></th>
                        <th><?= lang('blacklist_field_reason') ?></th>
                        <th><?= lang('th_created') ?></th>
                        <th><?= lang('th_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($entries): ?>
                    <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><span class="badge badge-red"><?= lang('blacklist_type_' . $entry['type']) ?></span></td>
                        <td><code><?= e($entry['value']) ?></code></td>
                        <td><?= e($entry['reason']) ?: '-' ?></td>
                        <td><?= format_date($entry['created_at']) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('<?= lang('blacklist_confirm_delete') ?>')" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                <button type="submit" name="delete_entry" value="1" class="btn btn-sm btn-danger"><?= lang('btn_delete') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center"><?= lang('blacklist_empty') ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
