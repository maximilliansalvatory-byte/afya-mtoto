<?php
require_once '../../config/db.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ONLY these roles can access Dashboard
$allowed_roles = ['admin', 'supervisor', 'facility_staff', 'chw'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: ../mother/index.php");
    exit();
}

// Load language
if (!isset($lang)) {
    $lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
    $lang = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/../../lang/sw.php';
}

$full_name     = $_SESSION['full_name'] ?? 'User';
$role          = $_SESSION['role'] ?? 'User';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';

// Statistics
try {
    $total_clients = $pdo->query("SELECT COUNT(*) FROM clients WHERE is_active = 1")->fetchColumn();
    $active_chws = $pdo->query("SELECT COUNT(*) FROM chws WHERE is_active = 1")->fetchColumn();
    $pending_referrals = $pdo->query("SELECT COUNT(*) FROM referrals WHERE status = 'Pending'")->fetchColumn();
    $visits_this_month = $pdo->query("
        SELECT COUNT(*) FROM visits 
        WHERE MONTH(visit_date) = MONTH(CURDATE()) 
        AND YEAR(visit_date) = YEAR(CURDATE())
    ")->fetchColumn();
} catch (Exception $e) {
    $total_clients = $active_chws = $pending_referrals = $visits_this_month = 0;
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['dashboard'] ?? 'Dashboard' ?> - Afya Mtoto</title>
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
            color: var(--text-color);
        }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

<div class="sidebar">
    <div class="brand">Afya Mtoto</div>
    <a href="index.php" class="active"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
    <a href="../requests/index.php"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
    <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
    <a href="../users/index.php"><i class="bi bi-person-gear me-2"></i> Users</a>
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>
    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><?= $lang['dashboard'] ?? 'Dashboard' ?></h3>
        <div class="d-flex align-items-center">
            <div class="me-3">
                <a href="?lang=sw" class="btn btn-sm <?= $current_lang === 'sw' ? 'btn-primary' : 'btn-outline-primary' ?>">SW</a>
                <a href="?lang=en" class="btn btn-sm <?= $current_lang === 'en' ? 'btn-primary' : 'btn-outline-primary' ?>">EN</a>
            </div>
            <div class="me-3">
                <a href="?theme=light" class="btn btn-sm <?= $current_theme === 'light' ? 'btn-warning' : 'btn-outline-warning' ?>"><i class="bi bi-sun"></i></a>
                <a href="?theme=dark" class="btn btn-sm <?= $current_theme === 'dark' ? 'btn-dark' : 'btn-outline-dark' ?>"><i class="bi bi-moon"></i></a>
            </div>
            <span class="me-3"><?= $lang['welcome'] ?? 'Karibu' ?>, <strong><?= htmlspecialchars($full_name) ?></strong></span>
            <span class="badge bg-primary"><?= htmlspecialchars($role) ?></span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <h6 class="text-muted"><?= $lang['total_clients'] ?? 'Jumla ya Wateja' ?></h6>
                    <h2 class="mb-0"><?= (int)$total_clients ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <h6 class="text-muted"><?= $lang['active_chws'] ?? 'CHW Hai' ?></h6>
                    <h2 class="mb-0"><?= (int)$active_chws ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <h6 class="text-muted"><?= $lang['pending_referrals'] ?? 'Rufaa Zinazosubiri' ?></h6>
                    <h2 class="mb-0"><?= (int)$pending_referrals ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat">
                <div class="card-body">
                    <h6 class="text-muted"><?= $lang['visits_this_month'] ?? 'Tembeleo za Mwezi' ?></h6>
                    <h2 class="mb-0"><?= (int)$visits_this_month ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-5 card-stat">
        <div class="card-body">
            <h5><?= $lang['welcome'] ?? 'Karibu' ?> Afya Mtoto</h5>
            <p class="text-muted mb-0">
                Huu ni mfumo wa huduma endelevu kwa ajili ya mama wajawazito, watoto wachanga na watoto chini ya miaka 5.
            </p>
        </div>
    </div>
</div>
</body>
</html>