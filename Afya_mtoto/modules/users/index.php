<?php
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Only admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit();
}

$lang_file = __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'sw') . '.php';
$lang = file_exists($lang_file) ? require $lang_file : require __DIR__ . '/../../lang/sw.php';

$full_name     = $_SESSION['full_name'] ?? 'Admin';
$current_lang  = $_SESSION['lang'] ?? 'sw';
$success = '';
$error = '';

// ===================== SAVE USER =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $full_name  = trim($_POST['full_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $role_id    = (int)($_POST['role_id'] ?? 0);
    $password   = $_POST['password'] ?? '';
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($full_name) || empty($username) || empty($role_id)) {
        $error = 'Jaza sehemu zote muhimu.';
    } else {
        try {
            // Get role name
            $roleStmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->execute([$role_id]);
            $role = $roleStmt->fetch();
            $role_name = $role['name'] ?? '';

            if ($id > 0) {
                // UPDATE USER
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, phone=?, role_id=?, password=?, is_active=? WHERE id=?");
                    $stmt->execute([$full_name, $username, $phone, $role_id, $hashed, $is_active, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, phone=?, role_id=?, is_active=? WHERE id=?");
                    $stmt->execute([$full_name, $username, $phone, $role_id, $is_active, $id]);
                }
                $success = 'User amesasishwa.';
            } else {
                // CREATE USER
                if (empty($password)) {
                    $error = 'Password inahitajika.';
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, phone, role_id, password, is_active) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$full_name, $username, $phone, $role_id, $hashed, $is_active]);
                    $new_user_id = $pdo->lastInsertId();

                    // ===================== AUTO CREATE CHW =====================
                    if ($role_name === 'chw') {
                        // Check if CHW with this phone already exists
                        $check = $pdo->prepare("SELECT id FROM chws WHERE phone = ?");
                        $check->execute([$phone]);
                        
                        if (!$check->fetch()) {
                            $chw_code = 'CHW-' . str_pad($new_user_id, 3, '0', STR_PAD_LEFT);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO chws (user_id, chw_code, full_name, phone, is_active)
                                VALUES (?, ?, ?, ?, 1)
                            ");
                            $stmt->execute([$new_user_id, $chw_code, $full_name, $phone]);
                        }
                    }

                    $success = 'User ameundwa' . ($role_name === 'chw' ? ' na CHW record imeundwa kiotomatiki.' : '.');
                }
            }
        } catch (Exception $e) {
            $error = 'Hitilafu: ' . $e->getMessage();
        }
    }
}

// Fetch data
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$users = $pdo->query("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Afya Mtoto</title>
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

<div class="sidebar">
    <div class="text-center mb-4 fw-bold fs-4">Afya Mtoto</div>
    <a href="../dashboard/index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="../chws/index.php"><i class="bi bi-people me-2"></i> CHWs</a>
    <a href="../requests/index.php"><i class="bi bi-inbox me-2"></i> Service Requests</a>
    <a href="../clients/index.php"><i class="bi bi-person-hearts me-2"></i> Clients</a>
    <a href="../visits/index.php"><i class="bi bi-journal-medical me-2"></i> Visits</a>
    <a href="../referrals/index.php"><i class="bi bi-arrow-left-right me-2"></i> Referrals</a>
    <a href="../facilities/index.php"><i class="bi bi-hospital me-2"></i> Facilities</a>
    <a href="index.php" class="active"><i class="bi bi-person-gear me-2"></i> Users</a>
    <a href="../reports/index.php"><i class="bi bi-bar-chart me-2"></i> Reports</a>
    <a href="../settings/index.php"><i class="bi bi-gear me-2"></i> Settings</a>
    <div class="mt-5">
        <a href="../../logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Users Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
            + Add User
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($u) ?>)'>Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="user_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" id="role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank when editing)</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_user" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Add User';
    document.getElementById('user_id').value = 0;
    document.getElementById('full_name').value = '';
    document.getElementById('username').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password').value = '';
    document.getElementById('is_active').checked = true;
}

function editUser(user) {
    document.getElementById('modalTitle').innerText = 'Edit User';
    document.getElementById('user_id').value = user.id;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('username').value = user.username;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role_id').value = user.role_id;
    document.getElementById('password').value = '';
    document.getElementById('is_active').checked = user.is_active == 1;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>
</body>
</html>