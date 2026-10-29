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

$full_name     = $_SESSION['full_name'] ?? 'User';
$role          = $_SESSION['role'] ?? 'User';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';

// ---------------------------------------------
// Filters
// ---------------------------------------------
$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT c.*, f.name AS facility_name
        FROM chws c
        LEFT JOIN facilities f ON c.facility_id = f.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.full_name LIKE ? OR c.chw_code LIKE ? OR c.phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status_filter === 'active') {
    $sql .= " AND c.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $sql .= " AND c.is_active = 0";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chws = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['chw_management'] ?? 'CHW Management' ?> - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #0d47a1;
            --sidebar-hover: #1565c0;
            --body-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-color: #212529;
        }
        body.dark-theme {
            --sidebar-bg: #1a1a2e;
            --sidebar-hover: #16213e;
            --body-bg: #0f0f1a;
            --card-bg: #1f1f2e;
            --text-color: #e0e0e0;
        }
        body { background-color: var(--body-bg); color: var(--text-color); }
        .sidebar {
            width: 260px; height: 100vh; position: fixed;
            background: var(--sidebar-bg); color: white; padding-top: 20px;
        }
        .sidebar a {
            color: white; text-decoration: none; display: block;
            padding: 12px 20px; transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active { background: var(--sidebar-hover); }
        .main-content { margin-left: 260px; padding: 30px; }
        .card-stat {
            border-radius: 12px; border: none; background: var(--card-bg);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: var(--text-color);
        }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; padding: 0 15px; }
        table { color: var(--text-color); }
        .badge-active { background-color: #2e7d32; }
        .badge-inactive { background-color: #757575; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <!-- Sidebar -->
<div class="sidebar">
    <div class="brand">Afya Mtoto</div>

    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php" class="active"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
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
            <h3 class="mb-0"><?= $lang['chw_management'] ?? 'CHW Management' ?></h3>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> <?= $lang['add_chw'] ?? 'Add CHW' ?>
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] === 'added' ? ($lang['chw_added_success'] ?? 'CHW added successfully.') : ($lang['chw_updated_success'] ?? 'CHW updated successfully.') ?>
            </div>
        <?php endif; ?>

        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="q" class="form-control" placeholder="<?= $lang['search_placeholder'] ?? 'Search by name, code, or phone...' ?>" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value=""><?= $lang['all_statuses'] ?? 'All Statuses' ?></option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>><?= $lang['active'] ?? 'Active' ?></option>
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>><?= $lang['inactive'] ?? 'Inactive' ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
                                <th><?= $lang['chw_code'] ?? 'CHW Code' ?></th>
                                <th><?= $lang['full_name'] ?? 'Full Name' ?></th>
                                <th><?= $lang['phone'] ?? 'Phone' ?></th>
                                <th><?= $lang['facility'] ?? 'Facility' ?></th>
                                <th><?= $lang['village'] ?? 'Village' ?></th>
                                <th><?= $lang['status'] ?? 'Status' ?></th>
                                <th class="text-end"><?= $lang['actions'] ?? 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($chws)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4"><?= $lang['no_records_found'] ?? 'No records found.' ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($chws as $chw): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($chw['chw_code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($chw['full_name']) ?></td>
                                        <td><?= htmlspecialchars($chw['phone'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($chw['facility_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($chw['village'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($chw['is_active']): ?>
                                                <span class="badge badge-active"><?= $lang['active'] ?? 'Active' ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive"><?= $lang['inactive'] ?? 'Inactive' ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="edit.php?id=<?= $chw['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($chw['is_active']): ?>
                                                <a href="toggle_status.php?id=<?= $chw['id'] ?>&action=deactivate"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('<?= addslashes($lang['confirm_deactivate'] ?? 'Are you sure you want to deactivate this CHW?') ?>');">
                                                    <i class="bi bi-slash-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_status.php?id=<?= $chw['id'] ?>&action=activate"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('<?= addslashes($lang['confirm_activate'] ?? 'Are you sure you want to activate this CHW?') ?>');">
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