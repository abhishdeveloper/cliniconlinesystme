<?php
require_once 'config/database.php';

echo "Testing Database Schema...\n";
// 1. Check specialty column
try {
    $stmt = $pdo->query("SELECT specialty FROM users LIMIT 1");
    echo "PASS: 'specialty' column exists.\n";
} catch (PDOException $e) {
    echo "FAIL: 'specialty' column missing.\n";
    exit(1);
}

// 2. Check Doctor Seed
$stmt = $pdo->query("SELECT specialty FROM users WHERE role='doctor' AND email='doctor@clinic.com'");
$doc = $stmt->fetch();
if ($doc && $doc['specialty'] === 'General Physician') {
    echo "PASS: Dr. Smith has correct specialty.\n";
} else {
    echo "FAIL: Dr. Smith specialty incorrect or missing.\n";
}

// 3. Test Appointment Cancellation Logic (Simulation)
echo "Testing Cancellation Logic...\n";
$pdo->beginTransaction();
try {
    // Create dummy doctor and patient
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Test Doc', 'doc@test.com', 'pass', 'doctor')");
    $docId = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Test Pat', 'pat@test.com', 'pass', 'patient')");
    $patId = $pdo->lastInsertId();

    // Create slot
    $slotTime = date('Y-m-d H:i:s', strtotime('+1 day'));
    $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime, is_booked) VALUES (?, ?, 0)")->execute([$docId, $slotTime]);
    $slotId = $pdo->lastInsertId();

    // Book it
    $pdo->prepare("UPDATE appointment_slots SET is_booked = 1 WHERE id = ?")->execute([$slotId]);
    $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, status) VALUES (?, ?, ?, ?, 'pending')")->execute([$patId, $docId, $slotId, $slotTime]);
    $apptId = $pdo->lastInsertId();

    // Verify booked
    $checkSlot = $pdo->query("SELECT is_booked FROM appointment_slots WHERE id = $slotId")->fetchColumn();
    if ($checkSlot != 1) echo "FAIL: Slot not marked as booked.\n";

    // Cancel (Simulate logic from patient/dashboard.php)
    $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$apptId]);
    $pdo->prepare("UPDATE appointment_slots SET is_booked = 0 WHERE id = ?")->execute([$slotId]);

    // Verify cancelled
    $checkAppt = $pdo->query("SELECT status FROM appointments WHERE id = $apptId")->fetchColumn();
    $checkSlot = $pdo->query("SELECT is_booked FROM appointment_slots WHERE id = $slotId")->fetchColumn();

    if ($checkAppt === 'cancelled' && $checkSlot == 0) {
        echo "PASS: Cancellation logic works (Status cancelled, Slot released).\n";
    } else {
        echo "FAIL: Cancellation logic failed. Status: $checkAppt, Slot Booked: $checkSlot\n";
    }

} catch (Exception $e) {
    echo "FAIL: Exception: " . $e->getMessage() . "\n";
}
$pdo->rollBack(); // Clean up
?>
