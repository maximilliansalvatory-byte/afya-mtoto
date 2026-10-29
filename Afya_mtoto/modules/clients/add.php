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

$facilities = $pdo->query("SELECT id, name FROM facilities WHERE is_active = 1 ORDER BY name")->fetchAll();
$chws = $pdo->query("SELECT id, full_name FROM chws WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$mothers = $pdo->query("SELECT id, full_name, client_uid FROM clients WHERE client_type = 'Pregnant Woman' AND is_active = 1 ORDER BY full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $client_type  = $_POST['client_type'] ?? '';
    $nida_number  = trim($_POST['nida_number'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $gender       = $_POST['gender'] ?? '';
    $phone        = trim($_POST['phone'] ?? '');
    $village      = trim($_POST['village'] ?? '');
    $ward         = trim($_POST['ward'] ?? '');
    $facility_id  = $_POST['facility_id'] !== '' ? (int) $_POST['facility_id'] : null;
    $chw_id       = $_POST['chw_id'] !== '' ? (int) $_POST['chw_id'] : null;
    $mother_id    = $_POST['mother_id'] !== '' ? (int) $_POST['mother_id'] : null;
    $edd          = $_POST['expected_delivery_date'] ?: null;
    $blood_group  = trim($_POST['blood_group'] ?? '');
    $is_high_risk = isset($_POST['is_high_risk']) ? 1 : 0;

    if ($full_name === '' || $client_type === '') {
        $error = $current_lang === 'sw'
            ? 'Jina kamili na aina ya mteja vinahitajika.'
            : 'Full name and client type are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO clients
                (nida_number, full_name, client_type, date_of_birth, gender, phone, village, ward,
                 facility_id, chw_id, mother_id, expected_delivery_date, blood_group, is_high_risk, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $nida_number ?: null, $full_name, $client_type, $date_of_birth, $gender ?: null,
                $phone ?: null, $village ?: null, $ward ?: null, $facility_id, $chw_id, $mother_id,
                $edd, $blood_group ?: null, $is_high_risk
            ]);

            $new_id = $pdo->lastInsertId();
            $client_uid = 'CL-' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE clients SET client_uid = ? WHERE id = ?")->execute([$client_uid, $new_id]);

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
    <title><?= $lang['add_client'] ?? 'Add Client' ?> - Afya Mtoto</title>
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
        <a href="index.php" class="active"><i class="bi bi-person-hearts me-2"></i> <?= $lang['clients'] ?? 'Clients' ?></a>
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
            <h3 class="mb-0"><?= $lang['add_client'] ?? 'Add Client' ?></h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> <?= $lang['back_to_list'] ?? 'Back to List' ?>
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card card-stat">
            <div class="card-body p-4">
                <form method="POST" action="" id="clientForm">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label"><?= $lang['full_name'] ?? 'Full Name' ?> *</label>
                            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['client_type'] ?? 'Client Type' ?> *</label>
                            <select name="client_type" id="client_type" class="form-select" required onchange="toggleTypeFields()">
                                <option value="">--</option>
                                <option value="Pregnant Woman"><?= $lang['pregnant_woman'] ?? 'Pregnant Woman' ?></option>
                                <option value="Child Under 5"><?= $lang['child_under_5'] ?? 'Child Under 5' ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['nida_number'] ?? 'National ID (NIDA)' ?></label>
                            <input type="text" name="nida_number" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['gender'] ?? 'Gender' ?></label>
                            <select name="gender" class="form-select">
                                <option value="">--</option>
                                <option value="Male"><?= $lang['male'] ?? 'Male' ?></option>
                                <option value="Female"><?= $lang['female'] ?? 'Female' ?></option>
                                <option value="Other"><?= $lang['other'] ?? 'Other' ?></option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['phone'] ?? 'Phone' ?></label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>

                    <!-- Child-only field -->
                    <div class="row field-child" style="display:none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['date_of_birth'] ?? 'Date of Birth' ?></label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['mother'] ?? 'Mother' ?></label>
                            <select name="mother_id" class="form-select">
                                <option value=""><?= $lang['select_mother'] ?? 'Select Mother' ?></option>
                                <?php foreach ($mothers as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= htmlspecialchars($m['client_uid'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['blood_group'] ?? 'Blood Group' ?></label>
                            <input type="text" name="blood_group" class="form-control" placeholder="e.g. O+">
                        </div>
                    </div>

                    <!-- Pregnant-only field -->
                    <div class="row field-pregnant" style="display:none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['expected_delivery_date'] ?? 'Expected Delivery Date' ?></label>
                            <input type="date" name="expected_delivery_date" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['facility'] ?? 'Facility' ?></label>
                            <select name="facility_id" class="form-select">
                                <option value=""><?= $lang['select_facility'] ?? 'Select Facility' ?></option>
                                <?php foreach ($facilities as $f): ?>
                                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= $lang['chw'] ?? 'CHW' ?></label>
                            <select name="chw_id" class="form-select">
                                <option value=""><?= $lang['select_chw'] ?? 'Select CHW' ?></option>
                                <?php foreach ($chws as $ch): ?>
                                    <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_high_risk" value="1" class="form-check-input" id="highRisk">
                                <label class="form-check-label" for="highRisk"><?= $lang['is_high_risk'] ?? 'Mark as high risk' ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['village'] ?? 'Village' ?></label>
                            <input type="text" name="village" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= $lang['ward'] ?? 'Ward' ?></label>
                            <input type="text" name="ward" class="form-control">
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

    <script>
        function toggleTypeFields() {
            const type = document.getElementById('client_type').value;
            document.querySelectorAll('.field-child').forEach(el => el.style.display = (type === 'Child Under 5') ? 'flex' : 'none');
            document.querySelectorAll('.field-pregnant').forEach(el => el.style.display = (type === 'Pregnant Woman') ? 'flex' : 'none');
        }
    </script>

</body>
</html>