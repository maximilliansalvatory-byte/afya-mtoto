<?php
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
$lang = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/../../lang/sw.php';

$full_name     = $_SESSION['full_name'] ?? 'Mama';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$user_id       = $_SESSION['user_id'];

$success = '';
$error = '';

// ===================== HANDLE SERVICE REQUEST =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_service'])) {
    $service_type = trim($_POST['service_type'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    if (!empty($service_type)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO service_requests (user_id, service_type, description, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $service_type, $description]);
            $success = 'Ombi lako limepokelewa! CHW atawasiliana nawe hivi karibuni.';
        } catch (Exception $e) {
            $error = 'Hitilafu imetokea. Jaribu tena.';
        }
    }
}

// ===================== GET USER + CLIENT DATA =====================
$my_requests = [];
$my_appointments = [];
$my_referrals = [];
$my_visits = [];
$client = null;
$user = null;

try {
    // Get logged in user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Get service requests
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY requested_at DESC LIMIT 15");
    $stmt->execute([$user_id]);
    $my_requests = $stmt->fetchAll();

    // Find client by phone
    if ($user && !empty($user['phone'])) {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE phone = ? LIMIT 1");
        $stmt->execute([$user['phone']]);
        $client = $stmt->fetch();

        if ($client) {
            $client_id = $client['id'];

            // Appointments
            $stmt = $pdo->prepare("
                SELECT a.*, f.name as facility_name 
                FROM appointments a 
                LEFT JOIN facilities f ON a.facility_id = f.id 
                WHERE a.client_id = ? 
                ORDER BY a.appointment_date DESC LIMIT 10
            ");
            $stmt->execute([$client_id]);
            $my_appointments = $stmt->fetchAll();

            // Referrals
            $stmt = $pdo->prepare("
                SELECT r.*, f.name as to_facility_name 
                FROM referrals r 
                LEFT JOIN facilities f ON r.to_facility_id = f.id 
                WHERE r.client_id = ? 
                ORDER BY r.referral_date DESC LIMIT 10
            ");
            $stmt->execute([$client_id]);
            $my_referrals = $stmt->fetchAll();

            // Visits
            $stmt = $pdo->prepare("
                SELECT v.*, c.full_name as chw_name 
                FROM visits v 
                LEFT JOIN chws c ON v.chw_id = c.id 
                WHERE v.client_id = ? 
                ORDER BY v.visit_date DESC LIMIT 10
            ");
            $stmt->execute([$client_id]);
            $my_visits = $stmt->fetchAll();
        }
    }
} catch (Exception $e) {
    // silent fail
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mama Portal - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e3f2fd, #fce4ec); min-height: 100vh; }
        .hero { background: #0d47a1; color: white; padding: 25px 20px; border-radius: 0 0 20px 20px; }
        .service-card { border: none; border-radius: 16px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); background: white; height: 100%; }
        .service-icon { font-size: 2rem; color: #0d47a1; }
        .nav-pills .nav-link.active { background-color: #0d47a1; }
    </style>
</head>
<body>

<div class="hero text-center">
    <h3 class="mb-1">Karibu Mama</h3>
    <p class="mb-0 small">Afya Mtoto - Huduma kwa Mama na Mtoto</p>
    <div class="mt-2">
        <span class="badge bg-light text-dark"><?= htmlspecialchars($full_name) ?></span>
        <a href="../../logout.php" class="btn btn-sm btn-outline-light ms-2">Toka</a>
    </div>
</div>

<div class="container py-4">

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Profile Card -->
    <div class="card service-card p-3 mb-4">
        <h6 class="mb-3"><i class="bi bi-person-circle"></i> Taarifa Zangu</h6>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Jina:</strong> <?= htmlspecialchars($user['full_name'] ?? '-') ?></p>
                <p class="mb-1"><strong>Simu:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></p>
            </div>
            <div class="col-md-6">
                <?php if ($client): ?>
                    <p class="mb-1"><strong>Aina:</strong> <?= htmlspecialchars($client['client_type']) ?></p>
                    <p class="mb-1"><strong>Kijiji:</strong> <?= htmlspecialchars($client['village'] ?? '-') ?></p>
                    <?php if ($client['expected_delivery_date']): ?>
                        <p class="mb-1"><strong>Tarehe ya Kujifungua:</strong> <?= date('d M Y', strtotime($client['expected_delivery_date'])) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small">Bado hujaunganishwa na rekodi ya afya.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Request Services -->
    <h5 class="mb-3">Omba Huduma</h5>
    <div class="row g-3 mb-4">
        <?php
        $services = [
            ['type' => 'ANC', 'icon' => 'bi-heart-pulse', 'name' => 'Huduma ya Ujauzito (ANC)'],
            ['type' => 'PNC', 'icon' => 'bi-emoji-smile', 'name' => 'Huduma baada ya Kujifungua (PNC)'],
            ['type' => 'Immunization', 'icon' => 'bi-shield-plus', 'name' => 'Chanjo ya Mtoto'],
            ['type' => 'Health Check', 'icon' => 'bi-clipboard2-pulse', 'name' => 'Uchunguzi wa Afya'],
            ['type' => 'Talk to CHW', 'icon' => 'bi-chat-dots', 'name' => 'Ongea na CHW'],
            ['type' => 'Health Education', 'icon' => 'bi-info-circle', 'name' => 'Elimu ya Afya'],
        ];
        foreach ($services as $service):
        ?>
        <div class="col-6 col-md-4">
            <div class="card service-card text-center p-3">
                <div class="service-icon mb-2"><i class="bi <?= $service['icon'] ?>"></i></div>
                <h6 class="small"><?= $service['name'] ?></h6>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="service_type" value="<?= $service['type'] ?>">
                    <input type="hidden" name="description" value="Ombi la <?= $service['name'] ?>">
                    <button type="submit" name="request_service" class="btn btn-sm btn-outline-primary w-100">Omba</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- My Requests -->
    <div class="card service-card p-3 mb-3">
        <h6 class="mb-3">Maombi Yangu</h6>
        <?php if ($my_requests): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Huduma</th><th>Tarehe</th><th>Hali</th></tr></thead>
                    <tbody>
                        <?php foreach ($my_requests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['service_type']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($req['requested_at'])) ?></td>
                            <td><span class="badge bg-<?= $req['status']=='Pending'?'warning':($req['status']=='Accepted'?'info':'success') ?>"><?= $req['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-0">Bado hujawa na maombi.</p>
        <?php endif; ?>
    </div>

    <!-- My Appointments -->
    <div class="card service-card p-3 mb-3">
        <h6 class="mb-3">Miadi Yangu</h6>
        <?php if ($my_appointments): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Tarehe</th><th>Aina</th><th>Kituo</th><th>Hali</th></tr></thead>
                    <tbody>
                        <?php foreach ($my_appointments as $app): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($app['appointment_date'])) ?></td>
                            <td><?= htmlspecialchars($app['appointment_type']) ?></td>
                            <td><?= htmlspecialchars($app['facility_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($app['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-0">Hakuna miadi kwa sasa.</p>
        <?php endif; ?>
    </div>

    <!-- My Referrals -->
    <div class="card service-card p-3 mb-3">
        <h6 class="mb-3">Rufaa Zangu</h6>
        <?php if ($my_referrals): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Namba</th><th>Kituo</th><th>Tarehe</th><th>Hali</th></tr></thead>
                    <tbody>
                        <?php foreach ($my_referrals as $ref): ?>
                        <tr>
                            <td><?= htmlspecialchars($ref['referral_code']) ?></td>
                            <td><?= htmlspecialchars($ref['to_facility_name'] ?? '-') ?></td>
                            <td><?= date('d M Y', strtotime($ref['referral_date'])) ?></td>
                            <td><?= htmlspecialchars($ref['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-0">Hakuna rufaa kwa sasa.</p>
        <?php endif; ?>
    </div>

    <!-- My Visits -->
    <div class="card service-card p-3 mb-3">
        <h6 class="mb-3">Tembeleo Zangu</h6>
        <?php if ($my_visits): ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Tarehe</th><th>Aina</th><th>CHW</th><th>Matokeo</th></tr></thead>
                    <tbody>
                        <?php foreach ($my_visits as $vis): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($vis['visit_date'])) ?></td>
                            <td><?= htmlspecialchars($vis['visit_type']) ?></td>
                            <td><?= htmlspecialchars($vis['chw_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($vis['findings'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-0">Hakuna tembeleo bado.</p>
        <?php endif; ?>
    </div>

    <!-- Health Tip -->
    <div class="card service-card p-3">
        <h6><i class="bi bi-lightbulb text-warning"></i> Kidokezo cha Afya</h6>
        <p class="mb-0 small text-muted">
            Kumbuka kwenda kliniki mapema unapokuwa mjamzito. Tembelea kituo cha afya angalau mara 4 wakati wa ujauzito.
        </p>
    </div>

</div>
</body>
</html>