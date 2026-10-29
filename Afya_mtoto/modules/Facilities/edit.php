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

$stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = ?");
$stmt->execute([$id]);
$facility = $stmt->fetch();

if (!$facility) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $facility_type = $_POST['facility_type'] ?? 'Dispensary';
    $region = trim($_POST['region'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $ward = trim($_POST['ward'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $ownership = $_POST['ownership'] ?? 'Public';
    $latitude = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;

    if ($name === '') {
        $error = $lang['facility_name'] . ' ' . ($current_lang === 'sw' ? 'inahitajika.' : 'is required.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE facilities SET
                name = ?, facility_type = ?, region = ?, district = ?, ward = ?, village = ?, ownership = ?, latitude = ?, longitude = ?
                WHERE id = ?");
            $stmt->execute([$name, $facility_type, $region ?: null, $district ?: null, $ward ?: null, $village ?: null, $ownership, $latitude, $longitude, $id]);

            header("Location: index.php?success=updated");
            exit();
        } catch (PDOException $e) {
            $error = ($current_lang === 'sw' ? 'Hitilafu: ' : 'Error: ') . $e->getMessage();
        }
    }
    $facility = array_merge($facility, $_POST);
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['edit_facility'] ?? 'Edit Facility' ?> - Afya Mtoto</title>
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
        <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> <?= $lang['referrals'] ?? 'Referrals' ?></a>
        <a href="index.php" class="active"><i class="bi bi-hospital me-2"></i> <?= $lang['facilities'] ?? 'Facilities' ?></a>
        <a href="#"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
        <a href="#"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>
        <div class="mt-5 pt-4">
            <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><?= $lang['edit_facility'] ?? 'Edit Facility' ?> — <?= htmlspecialchars($facility['facility_code'] ?? '') ?></h3>
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
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><?= $lang['facility_name'] ?? 'Facility Name' ?> *</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($facility['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['facility_type'] ?? 'Facility Type' ?></label>
                            <select name="facility_type" class="form-select">
                                <option value="Dispensary" <?= $facility['facility_type'] === 'Dispensary' ? 'selected' : '' ?>><?= $lang['type_dispensary'] ?? 'Dispensary' ?></option>
                                <option value="Health Centre" <?= $facility['facility_type'] === 'Health Centre' ? 'selected' : '' ?>><?= $lang['type_health_centre'] ?? 'Health Centre' ?></option>
                                <option value="Hospital" <?= $facility['facility_type'] === 'Hospital' ? 'selected' : '' ?>><?= $lang['type_hospital'] ?? 'Hospital' ?></option>
                                <option value="Clinic" <?= $facility['facility_type'] === 'Clinic' ? 'selected' : '' ?>><?= $lang['type_clinic'] ?? 'Clinic' ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['region'] ?? 'Region' ?></label>
                            <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($facility['region'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['district'] ?? 'District' ?></label>
                            <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($facility['district'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['ward'] ?? 'Ward' ?></label>
                            <input type="text" name="ward" class="form-control" value="<?= htmlspecialchars($facility['ward'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['village'] ?? 'Village' ?></label>
                            <input type="text" name="village" class="form-control" value="<?= htmlspecialchars($facility['village'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= $lang['ownership'] ?? 'Ownership' ?></label>
                        <select name="ownership" class="form-select">
                            <option value="Public" <?= $facility['ownership'] === 'Public' ? 'selected' : '' ?>><?= $lang['ownership_public'] ?? 'Public' ?></option>
                            <option value="Private" <?= $facility['ownership'] === 'Private' ? 'selected' : '' ?>><?= $lang['ownership_private'] ?? 'Private' ?></option>
                            <option value="FBO" <?= $facility['ownership'] === 'FBO' ? 'selected' : '' ?>><?= $lang['ownership_fbo'] ?? 'FBO' ?></option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['latitude'] ?? 'Latitude' ?></label>
                            <input type="text" name="latitude" class="form-control" value="<?= htmlspecialchars($facility['latitude'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['longitude'] ?? 'Longitude' ?></label>
                            <input type="text" name="longitude" class="form-control" value="<?= htmlspecialchars($facility['longitude'] ?? '') ?>">
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