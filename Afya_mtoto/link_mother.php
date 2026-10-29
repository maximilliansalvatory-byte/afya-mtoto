<?php
require_once 'config/db.php';

echo "<h3>Linking Mother to Client Record...</h3>";

try {
    // Get all mothers
    $mothers = $pdo->query("
        SELECT u.id, u.full_name, u.phone 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE r.name = 'mother'
    ")->fetchAll();

    if (!$mothers) {
        echo "Hakuna mama waliosajiliwa.<br>";
        exit;
    }

    foreach ($mothers as $mother) {
        $phone = $mother['phone'];
        $name  = $mother['full_name'];

        // Check if client with this phone exists
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE phone = ?");
        $stmt->execute([$phone]);
        $client = $stmt->fetch();

        if ($client) {
            echo "✅ $name tayari ana rekodi ya client.<br>";
        } else {
            // Create client record
            $stmt = $pdo->prepare("
                INSERT INTO clients (client_uid, full_name, client_type, phone, is_active)
                VALUES (?, ?, 'Pregnant Woman', ?, 1)
            ");
            $uid = 'CLT-' . time() . rand(10,99);
            $stmt->execute([$uid, $name, $phone]);
            echo "✅ Client record imeundwa kwa: <strong>$name</strong> ($phone)<br>";
        }
    }

    echo "<br><h4 style='color:green;'>Imekamilika!</h4>";
    echo "<a href='modules/mother/index.php'>Angalia Mother Portal</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>