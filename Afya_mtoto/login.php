<?php
require_once 'config/db.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'mother') {
        header("Location: modules/mother/index.php");
    } else {
        header("Location: modules/dashboard/index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Tafadhali jaza username na password.";
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role_name'];
            $_SESSION['role_id']   = $user['role_id'];

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // STRICT Role-based Redirect
            if ($user['role_name'] === 'mother') {
                header("Location: modules/mother/index.php");
            } else {
                header("Location: modules/dashboard/index.php");
            }
            exit();
        } else {
            $error = "Username au password si sahihi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afya Mtoto - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .brand {
            font-size: 28px;
            font-weight: 700;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="text-center mb-4">
            <div class="brand">Afya Mtoto</div>
            <p class="text-muted">Continuum of Care System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username / Namba ya Simu</label>
                <input type="text" name="username" class="form-control form-control-lg" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Ingia</button>
        </form>

        <div class="text-center mt-3">
            <small>Wewe ni Mama? <a href="register_mother.php">Jisajili hapa</a></small>
        </div>
    </div>
</body>
</html>