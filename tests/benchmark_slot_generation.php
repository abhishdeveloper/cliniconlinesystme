<?php
require_once __DIR__ . '/../config/database.php';

// Setup Test Data
$doctor_id = 9999;
$days_ahead = 30;

// Cleanup previous run
try {
    $pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);
    $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$doctor_id]);
} catch (PDOException $e) {
    // Ignore if tables don't exist yet
}

// Create dummy doctor
try {
    $pdo->prepare("INSERT INTO users (id, name, email, password, role) VALUES (?, 'Bench Doctor', 'bench@doc.com', 'pass', 'doctor')")->execute([$doctor_id]);
} catch (PDOException $e) {
    // Might already exist if cleanup failed? Or use different ID.
}

// Create a schedule: M-F, 9-5, 30 min slots
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($days as $day) {
    $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00', '17:00', 30)");
    $stmt->execute([$doctor_id, $day]);
}

echo "Starting benchmark (Old Logic)...\n";
$start_time = microtime(true);

// --- OLD LOGIC START ---
$generated_count = 0;
// Get templates
$stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$templates = $stmt->fetchAll();

$today = new DateTime();

for ($i = 0; $i < $days_ahead; $i++) {
    $current_date = clone $today;
    $current_date->modify("+$i days");
    $day_name = $current_date->format('l'); // e.g., "Monday"

    foreach ($templates as $template) {
        if ($template['day_of_week'] === $day_name) {
            $start = new DateTime($current_date->format('Y-m-d') . ' ' . $template['start_time']);
            $end = new DateTime($current_date->format('Y-m-d') . ' ' . $template['end_time']);
            $interval = new DateInterval('PT' . $template['slot_duration'] . 'M');

            while ($start < $end) {
                $slot_datetime = $start->format('Y-m-d H:i:s');

                // Check if slot exists
                $check = $pdo->prepare("SELECT id FROM appointment_slots WHERE doctor_id = ? AND slot_datetime = ?");
                $check->execute([$doctor_id, $slot_datetime]);

                if (!$check->fetch()) {
                    $insert = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime) VALUES (?, ?)");
                    $insert->execute([$doctor_id, $slot_datetime]);
                    $generated_count++;
                }

                $start->add($interval);
            }
        }
    }
}
// --- OLD LOGIC END ---

$end_time = microtime(true);
$duration = $end_time - $start_time;

echo "Generated $generated_count slots in " . number_format($duration, 4) . " seconds.\n";

// Cleanup
$pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);
$pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$doctor_id]);
?>
