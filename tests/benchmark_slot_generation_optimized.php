<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Setup Test Data
$doctor_id = 9999;
$days_ahead = 30;

// Cleanup previous run
try {
    $pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);
    $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$doctor_id]);
} catch (PDOException $e) {}

// Create dummy doctor
$pdo->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, 'Bench Doctor', 'bench@doc.com', 'pass', 'doctor')")->execute([$doctor_id]);

// Create a schedule: M-F, 9-5, 30 min slots
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($days as $day) {
    $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00', '17:00', 30)");
    $stmt->execute([$doctor_id, $day]);
}

echo "Starting benchmark (Optimized Logic - First Run)...\n";
$start_time = microtime(true);

// --- NEW LOGIC CALL ---
$generated_count = generateDoctorSlots($pdo, $doctor_id, $days_ahead);
// --- NEW LOGIC END ---

$end_time = microtime(true);
$duration = $end_time - $start_time;

echo "Generated $generated_count slots in " . number_format($duration, 4) . " seconds.\n";

echo "Starting benchmark (Optimized Logic - Second Run / Update)...\n";
$start_time = microtime(true);

// --- NEW LOGIC CALL ---
$generated_count = generateDoctorSlots($pdo, $doctor_id, $days_ahead);
// --- NEW LOGIC END ---

$end_time = microtime(true);
$duration = $end_time - $start_time;

echo "Generated $generated_count slots in " . number_format($duration, 4) . " seconds.\n";

// Cleanup
$pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);
$pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$doctor_id]);
?>
