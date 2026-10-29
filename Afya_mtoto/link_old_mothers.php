<?php
require_once 'config/db.php';

echo "<h3>Inaunganisha Mama wa awali kuwa Clients...</h3>";

try {
    // Pata mama wote ambao bado hawana client record
    $mothers = $pdo->query("
        SELECT u.id, u.full_name, u.phone 
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.name = 'mother'
        AND u.phone IS NOT NULL
        AND u.phone != ''
        AND NOT EXISTS (
            SELECT 1 FROM clients c WHERE c.phone = u.phone
        )
    ")->fetchAll();

    if (count($mothers) === 0) {
        echo "<p style='color:green;'>Hakuna mama wa awali wanaohitaji kuunganishwa. Wote tayari ni clients.</p>";
    } else {
        foreach ($mothers as $mama) {
            $uid = 'CLT-' . time() . rand(100, 999);

            $stmt = $pdo->prepare("
                INSERT INTO clients (client_uid, full_name, client_type, phone, is_active)
                VALUES (?, ?, 'Pregnant Woman', ?, 1)
            ");
            $stmt->execute([
                $uid,
                $mama['full_name'],
                $mama['phone']
            ]);

            echo "✅ <strong>{$mama['full_name']}</strong> ({$mama['phone']}) sasa ni Client<br>";
        }

        echo "<br><h4 style='color:green;'>Imekamilika!</h4>";
    }

    echo "<br><a href='modules/clients/index.php'>Angalia Clients</a> | ";
    echo "<a href='modules/mother/index.php'>Angalia Mother Portal</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>