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
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT c.*, f.name AS facility_name, ch.full_name AS chw_name
        FROM clients c
        LEFT JOIN facilities f ON c.facility_id = f.id
        LEFT JOIN chws ch ON c.chw_id = ch.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.full_name LIKE ? OR c.client_uid LIKE ? OR c.phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($type_filter === 'pregnant') {
    $sql .= " AND c.client_type = 'Pregnant Woman'";
} elseif ($type_filter === 'child') {
    $sql .= " AND c.client_type = 'Child Under 5'";
}

if ($status_filter === 'active') {
    $sql .= " AND c.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $sql .= " AND c.is_active = 0";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['client_management'] ?? 'Client Management' ?> - Afya Mtoto</title>
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
        .badge-active { background-color: #2e7d32; }
        .badge-inactive { background-color: #757575; }
        .badge-pregnant { background-color: #ad1457; }
        .badge-child { background-color: #1565c0; }
        .badge-risk { background-color: #e65100; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <!-- Sidebar -->
<!-- Sidebar -->
<div class="sidebar">
    <div class="brand">Afya Mtoto</div>

    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
     <a href="../clients/index.php" class="active"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
    <a href="../requests/index.php"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
    <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>

    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['client_management'] ?? 'Client Management' ?></h3>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> <?= $lang['add_client'] ?? 'Add Client' ?>
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? ($lang['client_added_success'] ?? 'Client added successfully.') : ($lang['client_updated_success'] ?? 'Client updated successfully.') ?>
            </div>
        <?php endif; ?>

        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="q" class="form-control" placeholder="<?= $lang['search_placeholder'] ?? 'Search by name, code, or phone...' ?>" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">-- <?= $lang['client_type'] ?? 'Client Type' ?> --</option>
                            <option value="pregnant" <?= $type_filter === 'pregnant' ? 'selected' : '' ?>><?= $lang['pregnant_woman'] ?? 'Pregnant Woman' ?></option>
                            <option value="child" <?= $type_filter === 'child' ? 'selected' : '' ?>><?= $lang['child_under_5'] ?? 'Child Under 5' ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value=""><?= $lang['all_statuses'] ?? 'All Statuses' ?></option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>><?= $lang['active'] ?? 'Active' ?></option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>><?= $lang['inactive'] ?? 'Inactive' ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i> <?= $lang['search'] ?? 'Search' ?>
                        </button>
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
                                <th><?= $lang['client_uid'] ?? 'Client ID' ?></th>
                                <th><?= $lang['full_name'] ?? 'Full Name' ?></th>
                                <th><?= $lang['type'] ?? 'Type' ?></th>
                                <th><?= $lang['phone'] ?? 'Phone' ?></th>
                                <th><?= $lang['facility'] ?? 'Facility' ?></th>
                                <th><?= $lang['chw'] ?? 'CHW' ?></th>
                                <th><?= $lang['status'] ?? 'Status' ?></th>
                                <th class="text-end"><?= $lang['actions'] ?? 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clients)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4"><?= $lang['no_records_found'] ?? 'No records found.' ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($clients as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['client_uid'] ?? '-') ?></td>
                                        <td>
                                            <?= htmlspecialchars($c['full_name']) ?>
                                            <?php if ($c['is_high_risk']): ?>
                                                <span class="badge badge-risk ms-1"><?= $lang['high_risk'] ?? 'High Risk' ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($c['client_type'] === 'Pregnant Woman'): ?>
                                                <span class="badge badge-pregnant"><?= $lang['pregnant_woman'] ?? 'Pregnant Woman' ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-child"><?= $lang['child_under_5'] ?? 'Child Under 5' ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($c['facility_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($c['chw_name'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($c['is_active']): ?>
                                                <span class="badge badge-active"><?= $lang['active'] ?? 'Active' ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive"><?= $lang['inactive'] ?? 'Inactive' ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($c['is_active']): ?>
                                                <a href="toggle_status.php?id=<?= $c['id'] ?>&action=deactivate"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('<?= addslashes($lang['confirm_deactivate'] ?? 'Are you sure?') ?>');">
                                                    <i class="bi bi-slash-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_status.php?id=<?= $c['id'] ?>&action=activate"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('<?= addslashes($lang['confirm_activate'] ?? 'Are you sure?') ?>');">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
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