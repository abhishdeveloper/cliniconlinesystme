<?php
require_once __DIR__ . '/../config/database.php';

// Helper to reset state
function reset_slots($pdo, $doctor_id) {
    $pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);
}

function setup_schedule($pdo, $doctor_id) {
    $pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00', '17:00', 30)");

    foreach ($days as $day) {
        $stmt->execute([$doctor_id, $day]);
    }
}

// Get Doctor ID
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
$stmt->execute();
$doctor = $stmt->fetch();

if (!$doctor) {
    // create a doctor if none exists
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Dr. Bench', 'bench@test.com', 'pass', 'doctor')");
    $doctor_id = $pdo->lastInsertId();
} else {
    $doctor_id = $doctor['id'];
}

setup_schedule($pdo, $doctor_id);

echo "Benchmarking Slot Generation for 30 days...\n";

// --- Original Logic Benchmark ---
reset_slots($pdo, $doctor_id);

$start_time = microtime(true);

$days_ahead = 30;
$stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
$stmt->execute([$doctor_id]);
$templates = $stmt->fetchAll();

$today = new DateTime();
$generated_count = 0;

$pdo->beginTransaction(); // Wrap in transaction for fairness, though original code doesn't explicitly use one, but SQLite is faster with it.
// Actually, original code doesn't use transaction, so I should probably test without it to be accurate to "before" state?
// But SQLite without transaction is incredibly slow for inserts.
// I'll test *with* transaction for both to isolate the logic difference,
// OR I should mimic original code exactly.
// Original code does NOT use transaction.
$pdo->commit(); // End the transaction started above? No, I haven't started one.

// Let's stick to the original logic exactly (no transaction).
$start_time_original = microtime(true);

for ($i = 0; $i < $days_ahead; $i++) {
    $current_date = clone $today;
    $current_date->modify("+$i days");
    $day_name = $current_date->format('l');

    foreach ($templates as $template) {
        if ($template['day_of_week'] === $day_name) {
            $start = new DateTime($current_date->format('Y-m-d') . ' ' . $template['start_time']);
            $end = new DateTime($current_date->format('Y-m-d') . ' ' . $template['end_time']);
            $interval = new DateInterval('PT' . $template['slot_duration'] . 'M');

            while ($start < $end) {
                $slot_datetime = $start->format('Y-m-d H:i:s');

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
$end_time_original = microtime(true);
echo "Original Method: " . number_format($end_time_original - $start_time_original, 4) . " seconds. Generated: $generated_count slots.\n";


// --- Optimized Logic Benchmark ---
reset_slots($pdo, $doctor_id);

$start_time_optimized = microtime(true);
$generated_count_opt = 0;

// 1. Fetch templates (Already fetched: $templates)

// 2. Fetch ALL existing slots for the date range
$existing_slots = [];
$range_start = $today->format('Y-m-d 00:00:00');
$range_end = (clone $today)->modify("+$days_ahead days")->format('Y-m-d 23:59:59');

$stmt = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE doctor_id = ? AND slot_datetime BETWEEN ? AND ?");
$stmt->execute([$doctor_id, $range_start, $range_end]);
while ($row = $stmt->fetch()) {
    $existing_slots[$row['slot_datetime']] = true;
}

// 3. Generate Candidates
$to_insert = [];

for ($i = 0; $i < $days_ahead; $i++) {
    $current_date = clone $today;
    $current_date->modify("+$i days");
    $day_name = $current_date->format('l');

    foreach ($templates as $template) {
        if ($template['day_of_week'] === $day_name) {
            $start = new DateTime($current_date->format('Y-m-d') . ' ' . $template['start_time']);
            $end = new DateTime($current_date->format('Y-m-d') . ' ' . $template['end_time']);
            $interval = new DateInterval('PT' . $template['slot_duration'] . 'M');

            while ($start < $end) {
                $slot_datetime = $start->format('Y-m-d H:i:s');

                if (!isset($existing_slots[$slot_datetime])) {
                    $to_insert[] = $slot_datetime;
                    $generated_count_opt++;
                }
                $start->add($interval);
            }
        }
    }
}

// 4. Batch Insert
if (!empty($to_insert)) {
    $pdo->beginTransaction();
    $insert_stmt = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime) VALUES (?, ?)");
    foreach ($to_insert as $slot_dt) {
         $insert_stmt->execute([$doctor_id, $slot_dt]);
    }
    $pdo->commit();
}

$end_time_optimized = microtime(true);
echo "Optimized Method: " . number_format($end_time_optimized - $start_time_optimized, 4) . " seconds. Generated: $generated_count_opt slots.\n";

$improvement = ($end_time_original - $start_time_optimized) / $start_time_optimized * 100; // Wait, this math is wrong
$diff = $end_time_original - $start_time_original;
$diff_opt = $end_time_optimized - $start_time_optimized;
$improvement_factor = $diff / $diff_opt;

echo "Speedup: " . number_format($improvement_factor, 2) . "x\n";

?>
