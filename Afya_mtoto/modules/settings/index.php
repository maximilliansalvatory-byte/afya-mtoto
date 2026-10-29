<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Load language
$lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    $lang = require __DIR__ . '/../../lang/sw.php';
}

$full_name     = $_SESSION['full_name'] ?? 'User';
$role          = $_SESSION['role'] ?? 'User';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$current_theme = $_SESSION['theme'] ?? 'light';
$user_id       = $_SESSION['user_id'];

$success = '';
$error   = '';

// Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = $current_lang === 'sw' ? 'Jaza sehemu zote.' : 'Please fill all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = $current_lang === 'sw' ? 'Nenosiri jipya halilingani.' : 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = $current_lang === 'sw' ? 'Nenosiri liwe angalau herufi 6.' : 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
            $success = $current_lang === 'sw' ? 'Nenosiri limebadilishwa.' : 'Password changed successfully.';
        } else {
            $error = $current_lang === 'sw' ? 'Nenosiri la sasa si sahihi.' : 'Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar {
            width: 260px; height: 100vh; position: fixed;
            background: #0d47a1; color: white; padding-top: 20px;
        }
        .sidebar a {
            color: white; text-decoration: none; display: block;
            padding: 12px 20px;
        }
        .sidebar a:hover, .sidebar a.active { background: #1565c0; }
        .main-content { margin-left: 260px; padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

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
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> <?= $lang['reports'] ?? 'Reports' ?></a>
    <a href="../settings/index.php" class="active"><i class="bi bi-gear me-2"></i> <?= $lang['settings'] ?? 'Settings' ?></a>

    <div class="mt-5 pt-4">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> <?= $lang['logout'] ?? 'Logout' ?></a>
    </div>
</div>

<div class="main-content">
    <h3 class="mb-4">Settings / Mipangilio</h3>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="mb-3"><?= $current_lang === 'sw' ? 'Badilisha Nenosiri' : 'Change Password' ?></h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?= $current_lang === 'sw' ? 'Nenosiri la Sasa' : 'Current Password' ?></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $current_lang === 'sw' ? 'Nenosiri Jipya' : 'New Password' ?></label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $current_lang === 'sw' ? 'Thibitisha Nenosiri' : 'Confirm Password' ?></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <?= $current_lang === 'sw' ? 'Badilisha' : 'Change Password' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="mb-3"><?= $current_lang === 'sw' ? 'Taarifa za Mfumo' : 'System Info' ?></h5>
                <p><strong>System:</strong> Afya Mtoto</p>
                <p><strong>Version:</strong> 1.0.0</p>
                <p><strong>User:</strong> <?= htmlspecialchars($full_name) ?></p>
                <p><strong>Role:</strong> <?= htmlspecialchars($role) ?></p>
                <p><strong>Language:</strong> <?= strtoupper($current_lang) ?></p>
            </div>
        </div>
    </div>
</div>

</body>
</html>