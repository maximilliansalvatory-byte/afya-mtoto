<?php
require_once 'config/db.php';

echo "<h3>Inasawazisha CHWs...</h3>";

try {
    // Pata users wote wenye role = chw
    $chws_users = $pdo->query("
        SELECT u.id, u.full_name, u.phone 
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.name = 'chw'
    ")->fetchAll();

    if (!$chws_users) {
        echo "Hakuna user mwenye role ya CHW.<br>";
        exit;
    }

    foreach ($chws_users as $user) {
        // Angalia kama tayari yupo kwenye chws table
        $check = $pdo->prepare("SELECT id FROM chws WHERE phone = ? OR full_name = ?");
        $check->execute([$user['phone'], $user['full_name']]);

        if ($check->fetch()) {
            echo "✅ {$user['full_name']} tayari yupo kwenye CHWs<br>";
        } else {
            $chw_code = 'CHW-' . str_pad($user['id'], 3, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO chws (user_id, chw_code, full_name, phone, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $user['id'],
                $chw_code,
                $user['full_name'],
                $user['phone']
            ]);

            echo "✅ <strong>{$user['full_name']}</strong> sasa ameongezwa kwenye CHWs<br>";
        }
    }

    echo "<br><h4 style='color:green;'>Imekamilika!</h4>";
    echo "<a href='modules/chws/index.php'>Angalia CHWs</a> | ";
    echo "<a href='modules/visits/add.php'>Angalia Tembeleo</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>