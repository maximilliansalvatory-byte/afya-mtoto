<?php
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($lang)) {
    $lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
    $lang = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/../../lang/sw.php';
}

$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';

$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$urgency_filter = $_GET['urgency'] ?? '';

$sql = "SELECT r.*, c.full_name AS client_name, c.client_uid, ch.full_name AS chw_name,
               tf.name AS to_facility_name, ff.name AS from_facility_name
        FROM referrals r
        JOIN clients c ON r.client_id = c.id
        JOIN chws ch ON r.chw_id = ch.id
        JOIN facilities tf ON r.to_facility_id = tf.id
        LEFT JOIN facilities ff ON r.from_facility_id = ff.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.full_name LIKE ? OR r.referral_code LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

if ($status_filter !== '') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($urgency_filter !== '') {
    $sql .= " AND r.urgency = ?";
    $params[] = $urgency_filter;
}

$sql .= " ORDER BY r.referral_date DESC, r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$referrals = $stmt->fetchAll();

$status_labels = [
    'Pending' => $lang['status_pending'] ?? 'Pending',
    'Arrived' => $lang['status_arrived'] ?? 'Arrived',
    'Completed' => $lang['status_completed'] ?? 'Completed',
    'Lost to Follow-up' => $lang['status_lost'] ?? 'Lost to Follow-up',
    'Cancelled' => $lang['status_cancelled'] ?? 'Cancelled',
];
$status_badge = [
    'Pending' => 'badge-pending',
    'Arrived' => 'badge-arrived',
    'Completed' => 'badge-completed',
    'Lost to Follow-up' => 'badge-lost',
    'Cancelled' => 'badge-cancelled',
];
$urgency_labels = [
    'Normal' => $lang['urgency_normal'] ?? 'Normal',
    'Urgent' => $lang['urgency_urgent'] ?? 'Urgent',
    'Emergency' => $lang['urgency_emergency'] ?? 'Emergency',
];
$urgency_badge = [
    'Normal' => 'badge-normal',
    'Urgent' => 'badge-urgent',
    'Emergency' => 'badge-emergency',
];
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['referral_management'] ?? 'Referral Management' ?> - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0d47a1; --sidebar-hover: #1565c0; --body-bg: #f4f6f9; --card-bg: #ffffff; --text-color: #212529; }
        body.dark-theme { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --body-bg: #0f0f1a; --card-bg: #1f1f2e; --text-color: #e0e0e0; }
        body { background-color: var(--body-bg); color: var(--text-color); }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--sidebar-bg); color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--sidebar-hover); }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-stat { border-radius: 12px; border: none; background: var(--card-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: var(--text-color); }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; padding: 0 15px; }
        table { color: var(--text-color); }
        .badge-pending { background-color: #f9a825; color: #212529; }
        .badge-arrived { background-color: #0288d1; }
        .badge-completed { background-color: #2e7d32; }
        .badge-lost { background-color: #757575; }
        .badge-cancelled { background-color: #424242; }
        .badge-normal { background-color: #546e7a; }
        .badge-urgent { background-color: #ef6c00; }
        .badge-emergency { background-color: #c62828; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

  <!-- Sidebar -->
<div class="sidebar">
    <div class="brand">Afya Mtoto</div>

    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
    <a href="../requests/index.php"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
    <a href="../referrals/index.php" class="active"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>

    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['referral_management'] ?? 'Referral Management' ?></h3>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> <?= $lang['add_referral'] ?? 'Create Referral' ?>
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? ($lang['referral_added_success'] ?? 'Referral created successfully.') : ($lang['referral_updated_success'] ?? 'Referral updated successfully.') ?>
            </div>
        <?php endif; ?>

        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control" placeholder="<?= $lang['search_placeholder'] ?? 'Search...' ?>" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value=""><?= $lang['all_statuses'] ?? 'All Statuses' ?></option>
                            <?php foreach ($status_labels as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $status_filter === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="urgency" class="form-select">
                            <option value=""><?= $lang['all_urgencies'] ?? 'All Urgencies' ?></option>
                            <?php foreach ($urgency_labels as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $urgency_filter === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> <?= $lang['search'] ?? 'Search' ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-stat">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th><?= $lang['referral_code'] ?? 'Referral Code' ?></th>
                                <th><?= $lang['client'] ?? 'Client' ?></th>
                                <th><?= $lang['to_facility'] ?? 'To Facility' ?></th>
                                <th><?= $lang['referral_date'] ?? 'Referral Date' ?></th>
                                <th><?= $lang['urgency'] ?? 'Urgency' ?></th>
                                <th><?= $lang['referral_status'] ?? 'Status' ?></th>
                                <th class="text-end"><?= $lang['actions'] ?? 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($referrals)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4"><?= $lang['no_records_found'] ?? 'No records found.' ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($referrals as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['referral_code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($r['client_name']) ?> <small class="text-muted"><?= htmlspecialchars($r['client_uid'] ?? '') ?></small></td>
                                        <td><?= htmlspecialchars($r['to_facility_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($r['referral_date'], 0, 10)) ?></td>
                                        <td><span class="badge <?= $urgency_badge[$r['urgency']] ?? 'badge-normal' ?>"><?= htmlspecialchars($urgency_labels[$r['urgency']] ?? $r['urgency']) ?></span></td>
                                        <td><span class="badge <?= $status_badge[$r['status']] ?? 'badge-pending' ?>"><?= htmlspecialchars($status_labels[$r['status']] ?? $r['status']) ?></span></td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>