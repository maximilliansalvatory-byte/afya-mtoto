<?php
require_once 'config/db.php';

echo "<h2>Afya Mtoto - Creating Sample Data...</h2>";

try {
    // 1. Create a Facility
    $pdo->exec("INSERT IGNORE INTO facilities (facility_code, name, facility_type, region, district, ward, ownership, is_active) 
                VALUES ('FAC-001', 'Mkuyuni Dispensary', 'Dispensary', 'Mwanza', 'Nyamagana', 'Mkuyuni', 'Public', 1)");
    echo "✅ Facility created<br>";

    $facility_id = $pdo->query("SELECT id FROM facilities WHERE facility_code = 'FAC-001'")->fetchColumn();

    // 2. Create a CHW
    $pdo->exec("INSERT IGNORE INTO chws (chw_code, full_name, phone, gender, facility_id, village, ward, is_active) 
                VALUES ('CHW-001', 'Asha Juma', '0755111222', 'Female', $facility_id, 'Mkuyuni', 'Mkuyuni', 1)");
    echo "✅ CHW created<br>";

    $chw_id = $pdo->query("SELECT id FROM chws WHERE chw_code = 'CHW-001'")->fetchColumn();

    // 3. Create a Mother Client (Pregnant Woman)
    $pdo->exec("INSERT IGNORE INTO clients (client_uid, full_name, client_type, phone, village, ward, facility_id, chw_id, expected_delivery_date, is_high_risk, is_active) 
                VALUES ('CLT-001', 'Neema Hamisi', 'Pregnant Woman', '0754987654', 'Mkuyuni', 'Mkuyuni', $facility_id, $chw_id, '2026-12-15', 0, 1)");
    echo "✅ Client (Mama) created<br>";

    $client_id = $pdo->query("SELECT id FROM clients WHERE client_uid = 'CLT-001'")->fetchColumn();

    // 4. Create Appointment
    $pdo->exec("INSERT INTO appointments (client_id, facility_id, appointment_date, appointment_type, status) 
                VALUES ($client_id, $facility_id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'ANC', 'Scheduled')");
    echo "✅ Appointment created<br>";

    // 5. Create a Visit
    $pdo->exec("INSERT INTO visits (visit_uid, client_id, chw_id, visit_date, visit_type, danger_signs, findings, actions_taken, is_referred) 
                VALUES ('VIS-001', $client_id, $chw_id, CURDATE(), 'ANC', 'None', 'Mama yuko vizuri', 'Alishauriwa kurudi clinic', 0)");
    echo "✅ Visit created<br>";

    // 6. Create a Referral
    $pdo->exec("INSERT INTO referrals (referral_code, client_id, chw_id, to_facility_id, referral_date, reason, urgency, status) 
                VALUES ('REF-001', $client_id, $chw_id, $facility_id, NOW(), 'Maumivu ya tumbo', 'Urgent', 'Pending')");
    echo "✅ Referral created<br>";

    echo "<br><h3 style='color:green;'>Sample Data imeundwa kwa mafanikio!</h3>";
    echo "<a href='login.php'>Rudi Login</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error: " . $e->getMessage() . "</h3>";
}
?>