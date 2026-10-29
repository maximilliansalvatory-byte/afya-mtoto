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

$stmt = $pdo->prepare("SELECT * FROM chws WHERE id = ?");
$stmt->execute([$id]);
$chw = $stmt->fetch();

if (!$chw) {
    header("Location: index.php");
    exit();
}

$facilities = $pdo->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $facility_id = $_POST['facility_id'] !== '' ? (int) $_POST['facility_id'] : null;
    $village    = trim($_POST['village'] ?? '');
    $ward       = trim($_POST['ward'] ?? '');

    if ($full_name === '') {
        $error = $lang['full_name'] . ' ' . ($current_lang === 'sw' ? 'inahitajika.' : 'is required.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE chws SET full_name = ?, phone = ?, gender = ?, facility_id = ?, village = ?, ward = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone ?: null, $gender ?: null, $facility_id, $village ?: null, $ward ?: null, $id]);

            header("Location: index.php?success=updated");
            exit();
        } catch (PDOException $e) {
            $error = ($current_lang === 'sw' ? 'Hitilafu: ' : 'Error: ') . $e->getMessage();
        }
    }
    // Keep the submitted values displayed if there was an error
    $chw = array_merge($chw, $_POST);
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_chw'] ?? 'Edit CHW' ?> - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0d47a1; --sidebar-hover: #1565c0; --body-bg: #f4f6f9; --card-bg: #ffffff; --text-color: #212529; }
        body.dark-theme { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --body-bg: #0f0f1a; --card-bg: #1f1f2e; --text-color: #e0e0e0; }
        body { background-color: var(--body-bg); color: var(--text-color); }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--sidebar-bg); color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: var(--sidebar-hover); }
        .main-content { margin-left: 260px; padding: 30px; max-width: 800px; }
        .card-stat { border-radius: 12px; border: none; background: var(--card-bg); box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: var(--text-color); }
        .brand { font-size: 22px; font-weight: 700; text-align: center; margin-bottom: 30px; padding: 0 15px; }
    </style>
</head>
<body class="<?= $current_theme === 'dark' ? 'dark-theme' : '' ?>">

    <div class="sidebar">
        <div class="brand">Afya Mtoto</div>
        <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
        <a href="index.php" class="active"><i class="bi bi-people me-2"></i> <?= $lang['chws'] ?? 'CHWs' ?></a>
        <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
        <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> <?= $lang['visits'] ?? 'Visits' ?></a>
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
            <h3 class="mb-0"><?= $lang['edit_chw'] ?? 'Edit CHW' ?> — <?= htmlspecialchars($chw['chw_code'] ?? '') ?></h3>
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
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['full_name'] ?? 'Full Name' ?> *</label>
                        <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($chw['full_name'] ?? '') ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['phone'] ?? 'Phone' ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($chw['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['gender'] ?? 'Gender' ?></label>
                            <select name="gender" class="form-select">
                                <option value="">--</option>
                                <option value="Male" <?= ($chw['gender'] ?? '') === 'Male' ? 'selected' : '' ?>><?= $lang['male'] ?? 'Male' ?></option>
                                <option value="Female" <?= ($chw['gender'] ?? '') === 'Female' ? 'selected' : '' ?>><?= $lang['female'] ?? 'Female' ?></option>
                                <option value="Other" <?= ($chw['gender'] ?? '') === 'Other' ? 'selected' : '' ?>><?= $lang['other'] ?? 'Other' ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang['facility'] ?? 'Facility' ?></label>
                        <select name="facility_id" class="form-select">
                            <option value=""><?= $lang['select_facility'] ?? 'Select Facility' ?></option>
                            <?php foreach ($facilities as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= (int) ($chw['facility_id'] ?? 0) === (int) $f['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['village'] ?? 'Village' ?></label>
                            <input type="text" name="village" class="form-control" value="<?= htmlspecialchars($chw['village'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['ward'] ?? 'Ward' ?></label>
                            <input type="text" name="ward" class="form-control" value="<?= htmlspecialchars($chw['ward'] ?? '') ?>">
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