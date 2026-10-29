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
$error = '';

$clients = $pdo->query("SELECT id, full_name, client_uid, facility_id FROM clients WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$chws = $pdo->query("SELECT id, full_name FROM chws WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$facilities = $pdo->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetchAll();
$visits = $pdo->query("SELECT id, visit_uid, client_id, visit_date, visit_type FROM visits ORDER BY visit_date DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id   = (int) ($_POST['client_id'] ?? 0);
    $chw_id      = (int) ($_POST['chw_id'] ?? 0);
    $from_facility_id = $_POST['from_facility_id'] !== '' ? (int) $_POST['from_facility_id'] : null;
    $to_facility_id   = (int) ($_POST['to_facility_id'] ?? 0);
    $visit_id    = $_POST['visit_id'] !== '' ? (int) $_POST['visit_id'] : null;
    $referral_date = $_POST['referral_date'] ?? '';
    $reason      = trim($_POST['reason'] ?? '');
    $urgency     = $_POST['urgency'] ?? 'Normal';
    $notes       = trim($_POST['notes'] ?? '');

    if (!$client_id || !$chw_id || !$to_facility_id || $referral_date === '' || $reason === '') {
        $error = $current_lang === 'sw'
            ? 'Mteja, CHW, kituo cha kupelekwa, tarehe, na sababu vinahitajika.'
            : 'Client, CHW, destination facility, date, and reason are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO referrals
                (referral_code, client_id, chw_id, from_facility_id, to_facility_id, visit_id, referral_date, reason, urgency, status, notes)
                VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$client_id, $chw_id, $from_facility_id, $to_facility_id, $visit_id, $referral_date, $reason, $urgency, $notes ?: null]);

            $new_id = $pdo->lastInsertId();
            $referral_code = 'REF-' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE referrals SET referral_code = ? WHERE id = ?")->execute([$referral_code, $new_id]);

            // Mark the related visit as referred if one was linked
            if ($visit_id) {
                $pdo->prepare("UPDATE visits SET is_referred = 1 WHERE id = ?")->execute([$visit_id]);
            }

            header("Location: index.php?success=added");
            exit();
        } catch (PDOException $e) {
            $error = ($current_lang === 'sw' ? 'Hitilafu: ' : 'Error: ') . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['add_referral'] ?? 'Create Referral' ?> - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0d47a1; --sidebar-hover: #1565c0; --body-bg: #f4f6f9; --card-bg: #ffffff; --text-color: #212529; }
        body.dark-theme { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --body-bg: #0f0f1a; --card-bg: #1f1f2e; --text-color: #e0e0e0; }
        body { background-color: var(--body-bg); color: var(--text-color); }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--sidebar-bg); color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--sidebar-hover); }
        .main-content { margin-left: 260px; padding: 30px; max-width: 850px; }
        .card-stat { border-radius: 12px; border: none; background: var(--card-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: var(--text-color); }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; padding: 0 15px; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <div class="sidebar">
        <div class="brand">Afya Mtoto</div>
        <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
        <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
        <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
        <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
        <a href="index.php" class="active"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
        <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
        <a href="#"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
        <a href="#"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>
        <div class="mt-5 pt-4">
            <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['add_referral'] ?? 'Create Referral' ?></h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> <?= $lang['back_to_list'] ?? 'Back to List' ?>
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($facilities)): ?>
            <div class="alert alert-warning">
                <?= $current_lang === 'sw' ? 'Hakuna vituo vya afya. Tafadhali ongeza kituo kwanza.' : 'No facilities found. Please add a facility first.' ?>
            </div>
        <?php endif; ?>

        <div class="card card-stat">
            <div class="card-body p-4">
                <form method="POST" action="" id="referralForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['client'] ?? 'Client' ?> *</label>
                            <select name="client_id" id="client_id" class="form-select" required onchange="filterVisits()">
                                <option value=""><?= $lang['select_client'] ?? 'Select Client' ?></option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-facility="<?= $c['facility_id'] ?? '' ?>">
                                        <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['client_uid'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['chw'] ?? 'CHW' ?> *</label>
                            <select name="chw_id" class="form-select" required>
                                <option value=""><?= $lang['select_chw'] ?? 'Select CHW' ?></option>
                                <?php foreach ($chws as $ch): ?>
                                    <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['from_facility'] ?? 'From Facility' ?></label>
                            <select name="from_facility_id" class="form-select">
                                <option value=""><?= $lang['select_facility'] ?? 'Select Facility' ?></option>
                                <?php foreach ($facilities as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['to_facility'] ?? 'To Facility' ?> *</label>
                            <select name="to_facility_id" class="form-select" required>
                                <option value=""><?= $lang['select_to_facility'] ?? 'Select Destination Facility' ?></option>
                                <?php foreach ($facilities as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['related_visit'] ?? 'Related Visit (optional)' ?></label>
                        <select name="visit_id" id="visit_id" class="form-select">
                            <option value=""><?= $lang['select_visit'] ?? 'Select Visit' ?></option>
                            <?php foreach ($visits as $v): ?>
                                <option value="<?= $v['id'] ?>" data-client="<?= $v['client_id'] ?>" style="display:none;">
                                    <?= htmlspecialchars($v['visit_uid'] ?? '') ?> — <?= htmlspecialchars($v['visit_type']) ?> (<?= htmlspecialchars($v['visit_date']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['referral_date'] ?? 'Referral Date' ?> *</label>
                            <input type="date" name="referral_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['urgency'] ?? 'Urgency' ?> *</label>
                            <select name="urgency" class="form-select" required>
                                <option value="Normal"><?= $lang['urgency_normal'] ?? 'Normal' ?></option>
                                <option value="Urgent"><?= $lang['urgency_urgent'] ?? 'Urgent' ?></option>
                                <option value="Emergency"><?= $lang['urgency_emergency'] ?? 'Emergency' ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['reason'] ?? 'Reason for Referral' ?> *</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['notes'] ?? 'Notes' ?></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><?= $lang['save'] ?? 'Save' ?></button>
                        <a href="index.php" class="btn btn-outline-secondary"><?= $lang['cancel'] ?? 'Cancel' ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Only show visits belonging to the selected client
        function filterVisits() {
            const clientId = document.getElementById('client_id').value;
            const visitSelect = document.getElementById('visit_id');
            [...visitSelect.options].forEach(opt => {
                if (!opt.value) { opt.style.display = ''; return; }
                opt.style.display = (opt.dataset.client === clientId) ? '' : 'none';
            });
            visitSelect.value = '';
        }
    </script>

</body>
</html>