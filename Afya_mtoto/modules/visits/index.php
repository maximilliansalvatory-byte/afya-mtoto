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
$type_filter = $_GET['visit_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "SELECT v.*, c.full_name AS client_name, c.client_uid, ch.full_name AS chw_name
        FROM visits v
        JOIN clients c ON v.client_id = c.id
        JOIN chws ch ON v.chw_id = ch.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.full_name LIKE ? OR v.visit_uid LIKE ? OR ch.full_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($type_filter !== '') {
    $sql .= " AND v.visit_type = ?";
    $params[] = $type_filter;
}

if ($date_from !== '') {
    $sql .= " AND v.visit_date >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $sql .= " AND v.visit_date <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY v.visit_date DESC, v.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

$visit_types = [
    'ANC' => $lang['anc'] ?? 'ANC',
    'PNC' => $lang['pnc'] ?? 'PNC',
    'Growth Monitoring' => $lang['growth_monitoring'] ?? 'Growth Monitoring',
    'Immunization' => $lang['immunization'] ?? 'Immunization',
    'Danger Sign Screening' => $lang['danger_sign_screening'] ?? 'Danger Sign Screening',
    'Home Visit - Follow-up' => $lang['home_visit_followup'] ?? 'Home Visit - Follow-up',
    'Other' => $lang['other_visit_type'] ?? 'Other',
];
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['visit_management'] ?? 'Visit Management' ?> - Afya Mtoto</title>
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
        .badge-danger-signs { background-color: #c62828; }
        .badge-referred { background-color: #6a1b9a; }
        .badge-type { background-color: #37474f; }
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
    <a href="../visits/index.php" class="active"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
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
            <h3 class="mb-0"><?= $lang['visit_management'] ?? 'Visit Management' ?></h3>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> <?= $lang['add_visit'] ?? 'Log Visit' ?>
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? ($lang['visit_added_success'] ?? 'Visit logged successfully.') : ($lang['visit_updated_success'] ?? 'Visit updated successfully.') ?>
            </div>
        <?php endif; ?>

        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control" placeholder="<?= $lang['search_placeholder'] ?? 'Search...' ?>" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="visit_type" class="form-select">
                            <option value=""><?= $lang['select_visit_type'] ?? 'Select Visit Type' ?></option>
                            <?php foreach ($visit_types as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $type_filter === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>" title="<?= $lang['date_from'] ?? 'From' ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>" title="<?= $lang['date_to'] ?? 'To' ?>">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
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
                                <th><?= $lang['visit_uid'] ?? 'Visit ID' ?></th>
                                <th><?= $lang['visit_date'] ?? 'Visit Date' ?></th>
                                <th><?= $lang['client'] ?? 'Client' ?></th>
                                <th><?= $lang['chw'] ?? 'CHW' ?></th>
                                <th><?= $lang['visit_type'] ?? 'Visit Type' ?></th>
                                <th><?= $lang['danger_signs'] ?? 'Danger Signs' ?></th>
                                <th><?= $lang['referred'] ?? 'Referred' ?></th>
                                <th class="text-end"><?= $lang['actions'] ?? 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($visits)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4"><?= $lang['no_records_found'] ?? 'No records found.' ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($visits as $v): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($v['visit_uid'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($v['visit_date']) ?></td>
                                        <td><?= htmlspecialchars($v['client_name']) ?> <small class="text-muted"><?= htmlspecialchars($v['client_uid'] ?? '') ?></small></td>
                                        <td><?= htmlspecialchars($v['chw_name']) ?></td>
                                        <td><span class="badge badge-type"><?= htmlspecialchars($visit_types[$v['visit_type']] ?? $v['visit_type']) ?></span></td>
                                        <td>
                                            <?php if (!empty($v['danger_signs']) && $v['danger_signs'] !== ($lang['ds_none'] ?? 'None Observed')): ?>
                                                <span class="badge badge-danger-signs"><i class="bi bi-exclamation-triangle me-1"></i><?= $lang['yes'] ?? 'Yes' ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($v['is_referred']): ?>
                                                <span class="badge badge-referred"><?= $lang['yes'] ?? 'Yes' ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary">
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