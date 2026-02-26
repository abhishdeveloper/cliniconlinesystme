<?php
require_once __DIR__ . '/../config/database.php';

// Setup Doctor
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
$stmt->execute();
$doctor = $stmt->fetch();
if (!$doctor) {
    die("No doctor found. Please seed database.\n");
}
$doctor_id = $doctor['id'];

// Setup Schedule (Mon-Sun 9-5)
$pdo->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctor_id]);
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, '09:00', '17:00', 30)");
foreach ($days as $day) {
    $stmt->execute([$doctor_id, $day]);
}

// Clear Slots
$pdo->prepare("DELETE FROM appointment_slots WHERE doctor_id = ?")->execute([$doctor_id]);

echo "Verifying Slot Generation Logic...\n";

// Emulate the logic in doctor/schedule.php
$days_ahead = 30;
$_SESSION['user_id'] = $doctor_id; // Simulating session for the logic block

try {
    // Get templates
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $templates = $stmt->fetchAll();

    $today = new DateTime();
    $generated_count = 0;

    // --- LOGIC START ---
    $range_start = $today->format('Y-m-d 00:00:00');
    $range_end = (clone $today)->modify("+" . $days_ahead . " days")->format('Y-m-d 23:59:59');

    $existing_slots = [];
    $stmt_check = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE doctor_id = ? AND slot_datetime BETWEEN ? AND ?");
    $stmt_check->execute([$_SESSION['user_id'], $range_start, $range_end]);
    while ($row = $stmt_check->fetch()) {
        $existing_slots[$row['slot_datetime']] = true;
    }

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
                    }

                    $start->add($interval);
                }
            }
        }
    }

    if (!empty($to_insert)) {
        $pdo->beginTransaction();
        try {
            $insert_stmt = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime) VALUES (?, ?)");
            foreach ($to_insert as $slot_dt) {
                $insert_stmt->execute([$_SESSION['user_id'], $slot_dt]);
                $generated_count++;
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    // --- LOGIC END ---

    echo "Generated $generated_count slots.\n";

    // Verification 1: Check count
    // 16 slots/day * 30 days = 480 slots.
    // Wait, "next 30 days" including today? 0..29 = 30 days.
    $expected = 16 * 30;

    $count = $pdo->query("SELECT count(*) FROM appointment_slots WHERE doctor_id = $doctor_id")->fetchColumn();
    echo "Total slots in DB: $count\n";

    if ($count == $expected) {
        echo "✅ Slot count matches expected ($expected).\n";
    } else {
        echo "❌ Slot count MISMATCH! Expected $expected, got $count.\n";
        exit(1);
    }

    // Verification 2: Idempotency (Run again)
    echo "Running generation again (should add 0 slots)...\n";

    // Repeat logic...
    $generated_count_2 = 0;
     // --- LOGIC START ---
    $range_start = $today->format('Y-m-d 00:00:00');
    $range_end = (clone $today)->modify("+" . $days_ahead . " days")->format('Y-m-d 23:59:59');

    $existing_slots = [];
    $stmt_check = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE doctor_id = ? AND slot_datetime BETWEEN ? AND ?");
    $stmt_check->execute([$_SESSION['user_id'], $range_start, $range_end]);
    while ($row = $stmt_check->fetch()) {
        $existing_slots[$row['slot_datetime']] = true;
    }

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
                    }

                    $start->add($interval);
                }
            }
        }
    }

    // Should be empty
    if (!empty($to_insert)) {
        echo "❌ Candidates found on second run! Logic is flawed.\n";
        print_r($to_insert);
        exit(1);
    } else {
        echo "✅ No new slots generated on second run. Idempotency confirmed.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
