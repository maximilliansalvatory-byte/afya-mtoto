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

$stmt = $pdo->prepare("SELECT * FROM visits WHERE id = ?");
$stmt->execute([$id]);
$visit = $stmt->fetch();

if (!$visit) {
    header("Location: index.php");
    exit();
}

$clients = $pdo->query("SELECT id, full_name, client_uid, client_type FROM clients ORDER BY full_name")->fetchAll();
$chws = $pdo->query("SELECT id, full_name FROM chws ORDER BY full_name")->fetchAll();

$visit_types = [
    'ANC' => $lang['anc'] ?? 'ANC',
    'PNC' => $lang['pnc'] ?? 'PNC',
    'Growth Monitoring' => $lang['growth_monitoring'] ?? 'Growth Monitoring',
    'Immunization' => $lang['immunization'] ?? 'Immunization',
    'Danger Sign Screening' => $lang['danger_sign_screening'] ?? 'Danger Sign Screening',
    'Home Visit - Follow-up' => $lang['home_visit_followup'] ?? 'Home Visit - Follow-up',
    'Other' => $lang['other_visit_type'] ?? 'Other',
];

$danger_sign_options = [
    'ds_fever' => $lang['ds_fever'] ?? 'Fever',
    'ds_convulsions' => $lang['ds_convulsions'] ?? 'Convulsions',
    'ds_bleeding' => $lang['ds_bleeding'] ?? 'Bleeding',
    'ds_difficulty_breathing' => $lang['ds_difficulty_breathing'] ?? 'Difficulty Breathing',
    'ds_poor_feeding' => $lang['ds_poor_feeding'] ?? 'Poor Feeding',
    'ds_lethargy' => $lang['ds_lethargy'] ?? 'Lethargy / Unresponsive',
    'ds_swelling' => $lang['ds_swelling'] ?? 'Severe Swelling',
];

$existing_signs = array_map('trim', explode(',', $visit['danger_signs'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int) ($_POST['client_id'] ?? 0);
    $chw_id    = (int) ($_POST['chw_id'] ?? 0);
    $visit_date = $_POST['visit_date'] ?? '';
    $visit_type = $_POST['visit_type'] ?? '';
    $findings   = trim($_POST['findings'] ?? '');
    $actions_taken = trim($_POST['actions_taken'] ?? '');
    $is_referred = isset($_POST['is_referred']) ? 1 : 0;

    $selected_signs = [];
    foreach ($danger_sign_options as $key => $label) {
        if (isset($_POST[$key])) {
            $selected_signs[] = $label;
        }
    }
    $danger_signs = !empty($selected_signs) ? implode(', ', $selected_signs) : ($lang['ds_none'] ?? 'None Observed');

    if (!$client_id || !$chw_id || $visit_date === '' || $visit_type === '') {
        $error = $current_lang === 'sw'
            ? 'Mteja, CHW, tarehe, na aina ya tembeleo vinahitajika.'
            : 'Client, CHW, visit date, and visit type are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE visits SET
                client_id = ?, chw_id = ?, visit_date = ?, visit_type = ?, danger_signs = ?, findings = ?, actions_taken = ?, is_referred = ?
                WHERE id = ?");
            $stmt->execute([$client_id, $chw_id, $visit_date, $visit_type, $danger_signs, $findings ?: null, $actions_taken ?: null, $is_referred, $id]);

            header("Location: index.php?success=updated");
            exit();
        } catch (PDOException $e) {
            $error = ($current_lang === 'sw' ? 'Hitilafu: ' : 'Error: ') . $e->getMessage();
        }
    }
    $visit = array_merge($visit, $_POST);
    $visit['is_referred'] = $is_referred;
    $existing_signs = $selected_signs ? array_map(fn($k) => $danger_sign_options[$k] ?? '', array_filter(array_keys($danger_sign_options), fn($k) => isset($_POST[$k]))) : [];
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_visit'] ?? 'Edit Visit' ?> - Afya Mtoto</title>
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
        .danger-check { border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; padding: 10px 14px; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <div class="sidebar">
        <div class="brand">Afya Mtoto</div>
        <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
        <a href="../chws/index.php"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
        <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
        <a href="index.php" class="active"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
        <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
        <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
        <a href="#"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
        <a href="#"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>
        <div class="mt-5 pt-4">
            <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['edit_visit'] ?? 'Edit Visit' ?> — <?= htmlspecialchars($visit['visit_uid'] ?? '') ?></h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> <?= $lang['back_to_list'] ?? 'Back to List' ?>
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card card-stat">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['client'] ?? 'Client' ?> *</label>
                            <select name="client_id" class="form-select" required>
                                <option value=""><?= $lang['select_client'] ?? 'Select Client' ?></option>
                                <?php foreach ($clients as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int) $visit['client_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['client_uid'] ?? '') ?> — <?= htmlspecialchars($c['client_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['chw'] ?? 'CHW' ?> *</label>
                            <select name="chw_id" class="form-select" required>
                                <option value=""><?= $lang['select_chw'] ?? 'Select CHW' ?></option>
                                <?php foreach ($chws as $ch): ?>
                                    <option value="<?= $ch['id'] ?>" <?= (int) $visit['chw_id'] === (int) $ch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ch['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['visit_date'] ?? 'Visit Date' ?> *</label>
                            <input type="date" name="visit_date" class="form-control" required value="<?= htmlspecialchars($visit['visit_date']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['visit_type'] ?? 'Visit Type' ?> *</label>
                            <select name="visit_type" class="form-select" required>
                                <option value=""><?= $lang['select_visit_type'] ?? 'Select Visit Type' ?></option>
                                <?php foreach ($visit_types as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $visit['visit_type'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['danger_signs'] ?? 'Danger Signs Observed' ?></label>
                        <div class="danger-check">
                            <div class="row">
                                <?php foreach ($danger_sign_options as $key => $label): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="<?= $key ?>" value="1" class="form-check-input" id="<?= $key ?>" <?= in_array(trim($label), $existing_signs, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['findings'] ?? 'Findings' ?></label>
                        <textarea name="findings" class="form-control" rows="3"><?= htmlspecialchars($visit['findings'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['actions_taken'] ?? 'Actions Taken' ?></label>
                        <textarea name="actions_taken" class="form-control" rows="3"><?= htmlspecialchars($visit['actions_taken'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_referred" value="1" class="form-check-input" id="is_referred" <?= !empty($visit['is_referred']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_referred"><?= $lang['mark_as_referred'] ?? 'This client was referred to a facility' ?></label>
                        </div>
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