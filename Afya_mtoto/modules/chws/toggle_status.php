<?php
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id && in_array($action, ['activate', 'deactivate'], true)) {
    $new_status = $action === 'activate' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE chws SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
}

header("Location: index.php");
exit();
?>