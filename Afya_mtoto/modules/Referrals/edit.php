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

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT r.*, c.full_name AS client_name, c.client_uid
                        FROM referrals r JOIN clients c ON r.client_id = c.id
                        WHERE r.id = ?");
$stmt->execute([$id]);
$referral = $stmt->fetch();

if (!$referral) {
    header("Location: index.php");
    exit();
}

$facilities = $pdo->query("SELECT id, name FROM facilities ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_facility_id = (int) ($_POST['to_facility_id'] ?? 0);
    $urgency     = $_POST['urgency'] ?? 'Normal';
    $status      = $_POST['status'] ?? 'Pending';
    $reason      = trim($_POST['reason'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $arrival_date = $_POST['arrival_date'] ?: null;
    $completion_date = $_POST['completion_date'] ?: null;

    if (!$to_facility_id || $reason === '') {
        $error = $current_lang === 'sw'
            ? 'Kituo cha kupelekwa na sababu vinahitajika.'
            : 'Destination facility and reason are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE referrals SET
                to_facility_id = ?, urgency = ?, status = ?, reason = ?, notes = ?, arrival_date = ?, completion_date = ?
                WHERE id = ?");
            $stmt->execute([$to_facility_id, $urgency, $status, $reason, $notes ?: null, $arrival_date, $completion_date, $id]);

            header("Location: index.php?success=updated");
            exit();
        } catch (PDOException $e) {
            $error = ($current_lang === 'sw' ? 'Hitilafu: ' : 'Error: ') . $e->getMessage();
        }
    }
    $referral = array_merge($referral, $_POST);
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_referral'] ?? 'Update Referral' ?> - Afya Mtoto</title>
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
            <h3 class="mb-0"><?= $lang['edit_referral'] ?? 'Update Referral' ?> — <?= htmlspecialchars($referral['referral_code'] ?? '') ?></h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> <?= $lang['back_to_list'] ?? 'Back to List' ?>
            </a>
        </div>

        <p class="text-muted mb-3">
            <?= $lang['client'] ?? 'Client' ?>: <strong><?= htmlspecialchars($referral['client_name']) ?></strong>
            (<?= htmlspecialchars($referral['client_uid'] ?? '') ?>)
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card card-stat">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['to_facility'] ?? 'To Facility' ?> *</label>
                            <select name="to_facility_id" class="form-select" required>
                                <?php foreach ($facilities as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= (int) $referral['to_facility_id'] === (int) $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['urgency'] ?? 'Urgency' ?> *</label>
                            <select name="urgency" class="form-select" required>
                                <option value="Normal" <?= $referral['urgency'] === 'Normal' ? 'selected' : '' ?>><?= $lang['urgency_normal'] ?? 'Normal' ?></option>
                                <option value="Urgent" <?= $referral['urgency'] === 'Urgent' ? 'selected' : '' ?>><?= $lang['urgency_urgent'] ?? 'Urgent' ?></option>
                                <option value="Emergency" <?= $referral['urgency'] === 'Emergency' ? 'selected' : '' ?>><?= $lang['urgency_emergency'] ?? 'Emergency' ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['referral_status'] ?? 'Status' ?> *</label>
                        <select name="status" class="form-select" required>
                            <option value="Pending" <?= $referral['status'] === 'Pending' ? 'selected' : '' ?>><?= $lang['status_pending'] ?? 'Pending' ?></option>
                            <option value="Arrived" <?= $referral['status'] === 'Arrived' ? 'selected' : '' ?>><?= $lang['status_arrived'] ?? 'Arrived' ?></option>
                            <option value="Completed" <?= $referral['status'] === 'Completed' ? 'selected' : '' ?>><?= $lang['status_completed'] ?? 'Completed' ?></option>
                            <option value="Lost to Follow-up" <?= $referral['status'] === 'Lost to Follow-up' ? 'selected' : '' ?>><?= $lang['status_lost'] ?? 'Lost to Follow-up' ?></option>
                            <option value="Cancelled" <?= $referral['status'] === 'Cancelled' ? 'selected' : '' ?>><?= $lang['status_cancelled'] ?? 'Cancelled' ?></option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['arrival_date'] ?? 'Arrival Date' ?></label>
                            <input type="datetime-local" name="arrival_date" class="form-control" value="<?= htmlspecialchars(!empty($referral['arrival_date']) ? str_replace(' ', 'T', substr($referral['arrival_date'], 0, 16)) : '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['completion_date'] ?? 'Completion Date' ?></label>
                            <input type="datetime-local" name="completion_date" class="form-control" value="<?= htmlspecialchars(!empty($referral['completion_date']) ? str_replace(' ', 'T', substr($referral['completion_date'], 0, 16)) : '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['reason'] ?? 'Reason for Referral' ?> *</label>
                        <textarea name="reason" class="form-control" rows="3" required><?= htmlspecialchars($referral['reason'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['notes'] ?? 'Notes' ?></label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($referral['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><?= $lang['save'] ?? 'Save' ?></button>
                        <a href="index.php" class="btn btn-outline-secondary"><?= $lang['cancel'] ?? 'Cancel' ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>