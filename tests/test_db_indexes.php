<?php
require_once __DIR__ . '/../config/database.php';

echo "Testing Database Indexes...\n";

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$hasDocIndex = false;
$hasPatIndex = false;

try {
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA index_list('appointments')");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexes as $idx) {
            if ($idx['name'] === 'idx_appointments_doctor_date') $hasDocIndex = true;
            if ($idx['name'] === 'idx_appointments_patient_date') $hasPatIndex = true;
        }
    } else {
        // MySQL
        $stmt = $pdo->query("SHOW INDEX FROM appointments");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexes as $idx) {
            if ($idx['Key_name'] === 'idx_appointments_doctor_date') $hasDocIndex = true;
            if ($idx['Key_name'] === 'idx_appointments_patient_date') $hasPatIndex = true;
        }
    }

    if ($hasDocIndex) {
        echo "PASS: idx_appointments_doctor_date exists.\n";
    } else {
        echo "FAIL: idx_appointments_doctor_date missing.\n";
        exit(1);
    }

    if ($hasPatIndex) {
        echo "PASS: idx_appointments_patient_date exists.\n";
    } else {
        echo "FAIL: idx_appointments_patient_date missing.\n";
        exit(1);
    }

    // Verify Queries run without error
    echo "Verifying Queries...\n";

    // Doctor Query
    $stmt = $pdo->prepare("
        SELECT a.id FROM appointments a
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date ASC
        LIMIT 1
    ");
    $stmt->execute([1]);
    echo "PASS: Doctor query executes.\n";

    // Patient Query
    $stmt = $pdo->prepare("
        SELECT a.id FROM appointments a
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 1
    ");
    $stmt->execute([1]);
    echo "PASS: Patient query executes.\n";

} catch (PDOException $e) {
    echo "FAIL: Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
