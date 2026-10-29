<?php
require_once 'config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($phone) || empty($password)) {
        $error = 'Tafadhali jaza sehemu zote muhimu.';
    } elseif ($password !== $confirm) {
        $error = 'Nenosiri halilingani.';
    } elseif (strlen($password) < 6) {
        $error = 'Nenosiri liwe angalau herufi 6.';
    } else {
        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR phone = ?");
        $stmt->execute([$phone, $phone]);
        
        if ($stmt->fetch()) {
            $error = 'Namba ya simu tayari imesajiliwa.';
        } else {
            // Get mother role id
            $role = $pdo->query("SELECT id FROM roles WHERE name = 'mother'")->fetch();
            
            if (!$role) {
                $error = 'Role ya mama haipo. Wasiliana na admin.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                
               // 1. Create User (Mother account)
$stmt = $pdo->prepare("
    INSERT INTO users (role_id, full_name, username, phone, password, is_active)
    VALUES (?, ?, ?, ?, ?, 1)
");
$stmt->execute([
    $role['id'],
    $full_name,
    $phone,      // username = phone
    $phone,
    $hashed
]);

// 2. Automatically create Client record
$client_uid = 'CLT-' . time() . rand(100, 999);

$stmt = $pdo->prepare("
    INSERT INTO clients (client_uid, full_name, client_type, phone, is_active)
    VALUES (?, ?, 'Pregnant Woman', ?, 1)
");
$stmt->execute([
    $client_uid,
    $full_name,
    $phone
]);

$success = 'Usajili umefanikiwa! Sasa unaweza kuingia.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sajili Mama - Afya Mtoto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #fce4ec);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <h3 class="text-primary">Afya Mtoto</h3>
                    <p class="text-muted">Sajili kama Mama</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br><a href="login.php">Bofya hapa kuingia</a>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Jina Kamili</label>
                        <input type="text" name="full_name" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Namba ya Simu</label>
                        <input type="text" name="phone" class="form-control" required 
                               placeholder="Mfano: 0754123456"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nenosiri</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Thibitisha Nenosiri</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Sajili</button>
                </form>

                <div class="text-center mt-3">
                    <small>Tayari una account? <a href="login.php">Ingia hapa</a></small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>