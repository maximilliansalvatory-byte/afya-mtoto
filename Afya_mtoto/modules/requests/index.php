<?php
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Load language
$lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
$lang = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/../../lang/sw.php';

$full_name     = $_SESSION['full_name'] ?? 'User';
$role          = $_SESSION['role'] ?? 'User';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';

$success = '';
$error = '';

// ===================== UPDATE STATUS =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if ($request_id && in_array($new_status, ['Pending', 'Accepted', 'Completed', 'Cancelled'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE service_requests 
                SET status = ?, responded_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $request_id]);
            $success = $current_lang === 'sw' ? 'Hali imesasishwa.' : 'Status updated successfully.';
        } catch (Exception $e) {
            $error = 'Error updating status.';
        }
    }
}

// ===================== FETCH REQUESTS =====================
$status_filter = $_GET['status'] ?? '';
$sql = "
    SELECT sr.*, u.full_name as mother_name, u.phone as mother_phone
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
";
$params = [];

if ($status_filter && in_array($status_filter, ['Pending', 'Accepted', 'Completed', 'Cancelled'])) {
    $sql .= " WHERE sr.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY sr.requested_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests - Afya Mtoto</title>
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
            border-radius: 12px; border: none;
            background: var(--card-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <!-- Sidebar -->
    <!-- Sidebar -->
<div class="sidebar">
    <div class="brand">Afya Mtoto</div>

    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
    <a href="../requests/index.php" class="active"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
    <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>

    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Service Requests / Maombi ya Huduma</h3>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="card card-stat mb-4 p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Accepted" <?= $status_filter === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <!-- Requests Table -->
        <div class="card card-stat">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mama</th>
                                <th>Huduma</th>
                                <th>Tarehe</th>
                                <th>Hali</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $index => $req): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <?= htmlspecialchars($req['mother_name'] ?? 'Unknown') ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($req['mother_phone'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($req['service_type']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($req['requested_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $req['status'] === 'Pending' ? 'warning' : 
                                                ($req['status'] === 'Accepted' ? 'info' : 
                                                ($req['status'] === 'Completed' ? 'success' : 'secondary')) 
                                            ?>">
                                                <?= htmlspecialchars($req['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <select name="status" class="form-select form-select-sm" style="width: 130px;">
                                                    <option value="Pending" <?= $req['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Accepted" <?= $req['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                                    <option value="Completed" <?= $req['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="Cancelled" <?= $req['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No service requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>