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
$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';

// ===================== FILTERS =====================
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

// ===================== STATISTICS =====================
try {
    $total_clients     = $pdo->query("SELECT COUNT(*) FROM clients WHERE is_active = 1")->fetchColumn();
    $active_chws       = $pdo->query("SELECT COUNT(*) FROM chws WHERE is_active = 1")->fetchColumn();
    $pending_referrals = $pdo->query("SELECT COUNT(*) FROM referrals WHERE status = 'Pending'")->fetchColumn();
    $high_risk         = $pdo->query("SELECT COUNT(*) FROM clients WHERE is_high_risk = 1 AND is_active = 1")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE status = 'Completed' AND DATE(completion_date) BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $completed_referrals = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE visit_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $total_visits = $stmt->fetchColumn();

    // Referrals by status (for chart)
    $status_data = $pdo->query("
        SELECT status, COUNT(*) as total 
        FROM referrals 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Visits by type (for chart)
    $visit_types = $pdo->query("
        SELECT visit_type, COUNT(*) as total 
        FROM visits 
        GROUP BY visit_type
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (Exception $e) {
    $total_clients = $active_chws = $pending_referrals = $completed_referrals = $total_visits = $high_risk = 0;
    $status_data = [];
    $visit_types = [];
}

// ===================== EXPORT CSV =====================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="afya_mtoto_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Clients', $total_clients]);
    fputcsv($output, ['Active CHWs', $active_chws]);
    fputcsv($output, ['Pending Referrals', $pending_referrals]);
    fputcsv($output, ['Completed Referrals', $completed_referrals]);
    fputcsv($output, ['Total Visits', $total_visits]);
    fputcsv($output, ['High Risk Clients', $high_risk]);
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['reports'] ?? 'Reports' ?> - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Sidebar -->
   <!-- Sidebar -->
<div class="sidebar">
    <div class="brand">Afya Mtoto</div>

    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
    <a href="../requests/index.php"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
    <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
    <a href="../reports/index.php" class="active"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>

    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['reports'] ?? 'Reports' ?></h3>
            <div>
                <a href="?export=csv&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn btn-success me-2">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <a href="?lang=sw" class="btn btn-sm <?= $current_lang === 'sw' ? 'btn-primary' : 'btn-outline-primary' ?>">SW</a>
                <a href="?lang=en" class="btn btn-sm <?= $current_lang === 'en' ? 'btn-primary' : 'btn-outline-primary' ?>">EN</a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card card-stat mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">Total Clients</h6>
                    <h2><?= (int)$total_clients ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">Active CHWs</h6>
                    <h2><?= (int)$active_chws ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">High Risk Clients</h6>
                    <h2><?= (int)$high_risk ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">Pending Referrals</h6>
                    <h2><?= (int)$pending_referrals ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">Completed Referrals</h6>
                    <h2><?= (int)$completed_referrals ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat p-3">
                    <h6 class="text-muted">Visits in Period</h6>
                    <h2><?= (int)$total_visits ?></h2>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card card-stat p-3">
                    <h5 class="mb-3">Referrals by Status</h5>
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-stat p-3">
                    <h5 class="mb-3">Visits by Type</h5>
                    <canvas id="visitsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

<script>
// Referrals by Status Chart
const statusData = <?= json_encode($status_data) ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: ['#ffc107', '#0d6efd', '#198754', '#dc3545', '#6c757d']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Visits by Type Chart
const visitData = <?= json_encode($visit_types) ?>;
new Chart(document.getElementById('visitsChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(visitData),
        datasets: [{
            label: 'Visits',
            data: Object.values(visitData),
            backgroundColor: '#0d47a1'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

</body>
</html>