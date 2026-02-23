<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escapes output to prevent XSS.
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if a user is logged in.
 * Optionally checks for a specific role.
 * @param string|null $role
 * @return bool
 */
function isLoggedIn($role = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($role && $_SESSION['role'] !== $role) {
        return false;
    }
    return true;
}

/**
 * Redirects to a specific URL.
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Sets a flash message.
 * @param string $key
 * @param string $message
 * @param string $type (success, danger, warning, info)
 */
function setFlashMessage($key, $message, $type = 'info') {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Gets and clears a flash message.
 * @param string $key
 * @return string|null
 */
function getFlashMessage($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return "<div class='alert alert-{$msg['type']} alert-dismissible fade show' role='alert'>
                    {$msg['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return null;
}

/**
 * Generates appointment slots for a doctor based on their schedule templates.
 * Optimized to use bulk inserts and minimize queries.
 *
 * @param PDO $pdo
 * @param int $doctor_id
 * @param int $days_ahead
 * @return int Number of slots generated
 */
function generateDoctorSlots($pdo, $doctor_id, $days_ahead = 30) {
    // 1. Get templates
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($templates)) {
        return 0;
    }

    // 2. Calculate range for efficient checking
    $today = new DateTime();
    $start_date_check = $today->format('Y-m-d 00:00:00');

    $end_date = clone $today;
    $end_date->modify("+$days_ahead days");
    $end_date_check = $end_date->format('Y-m-d 23:59:59');

    // 3. Fetch existing slots in range to minimize queries
    // Use FETCH_COLUMN to get a simple array of datetime strings
    $stmt = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE doctor_id = ? AND slot_datetime BETWEEN ? AND ?");
    $stmt->execute([$doctor_id, $start_date_check, $end_date_check]);
    $existing_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Convert to map for O(1) lookup: ['2023-10-27 09:00:00' => true]
    $existing_slots_map = array_flip($existing_slots);

    // 4. Generate potential slots
    $slots_to_insert = [];

    for ($i = 0; $i < $days_ahead; $i++) {
        $current_date = clone $today;
        $current_date->modify("+$i days");
        $day_name = $current_date->format('l'); // e.g., "Monday"
        $date_str = $current_date->format('Y-m-d');

        foreach ($templates as $template) {
            if ($template['day_of_week'] === $day_name) {
                $start = new DateTime($date_str . ' ' . $template['start_time']);
                $end = new DateTime($date_str . ' ' . $template['end_time']);
                // Use explicit DateInterval format for safety
                $interval = new DateInterval('PT' . (int)$template['slot_duration'] . 'M');

                while ($start < $end) {
                    $slot_datetime = $start->format('Y-m-d H:i:s');

                    // Check against in-memory map instead of DB query
                    if (!isset($existing_slots_map[$slot_datetime])) {
                        $slots_to_insert[] = $slot_datetime;
                        // Mark as existing in our map to prevent duplicates within this run
                        $existing_slots_map[$slot_datetime] = true;
                    }

                    $start->add($interval);
                }
            }
        }
    }

    // 5. Bulk Insert
    $generated_count = 0;
    if (!empty($slots_to_insert)) {
        // Chunk inserts to avoid query length limits (e.g. SQLite limit is often 999 params)
        // Each slot uses 2 params (doctor_id, slot_datetime). 999 / 2 = 499. Safety margin: 100.
        $chunk_size = 100;
        $chunks = array_chunk($slots_to_insert, $chunk_size);

        foreach ($chunks as $chunk) {
            $placeholders = [];
            $values = [];
            foreach ($chunk as $slot_dt) {
                $placeholders[] = "(?, ?)";
                $values[] = $doctor_id;
                $values[] = $slot_dt;
            }

            $sql = "INSERT INTO appointment_slots (doctor_id, slot_datetime) VALUES " . implode(', ', $placeholders);
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute($values);
                $generated_count += count($chunk);
            } catch (PDOException $e) {
                // In case of race condition or other error, fallback or log
                // But generally safe due to pre-check
                error_log("Bulk insert failed: " . $e->getMessage());
            }
        }
    }

    return $generated_count;
}
?>
