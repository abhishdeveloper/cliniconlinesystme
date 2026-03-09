<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if doctor
if (!isLoggedIn('doctor')) {
    setFlashMessage('danger', "Access Denied. Doctors only.", 'danger');
    redirect('/login.php');
}

$error = '';
$success = '';

// Handle Adding Weekly Template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    $day = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $duration = $_POST['duration'];

    try {
        $stmt = $pdo->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $day, $start_time, $end_time, $duration]);
        setFlashMessage('success', "Schedule added for $day!", 'success');
        redirect('schedule.php');
    } catch (PDOException $e) {
        $error = "Error adding schedule: " . $e->getMessage();
    }
}

// Handle Generating Slots from Template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_slots') {
    $days_ahead = 30; // Generate for next 30 days
    $generated_count = 0;

    try {
        // Get templates
        $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $templates = $stmt->fetchAll();

        $today = new DateTime();

        // ⚡ Bolt Optimization: Replace N+1 queries by fetching existing slots upfront into an O(1) hash map
        $start_of_day = $today->format('Y-m-d 00:00:00');
        $existing_stmt = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE doctor_id = ? AND slot_datetime >= ?");
        $existing_stmt->execute([$_SESSION['user_id'], $start_of_day]);
        // Use array_flip to create an O(1) lookup map of existing slots
        $existing_slots = array_flip($existing_stmt->fetchAll(PDO::FETCH_COLUMN));

        // ⚡ Bolt Optimization: Prepare the INSERT statement ONCE outside the loops
        $insert = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime) VALUES (?, ?)");

        // ⚡ Bolt Optimization: Wrap bulk inserts in a single transaction to prevent severe SQLite disk-sync I/O overhead
        $pdo->beginTransaction();

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

                        // ⚡ Bolt Optimization: O(1) lookup instead of a SELECT query per slot
                        if (!isset($existing_slots[$slot_datetime])) {
                            $insert->execute([$_SESSION['user_id'], $slot_datetime]);
                            $generated_count++;

                            // Prevent duplicate inserts if templates overlap
                            $existing_slots[$slot_datetime] = true;
                        }

                        $start->add($interval);
                    }
                }
            }
        }

        $pdo->commit();

        setFlashMessage('success', "Generated $generated_count slots for the next 30 days!", 'success');
        redirect('schedule.php');

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error generating slots: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <h2>Manage Schedule</h2>
    <div class="row">
        <!-- Add Weekly Schedule Template -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">1. Set Weekly Availability</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Define your regular hours here (e.g., Every Monday 9-5).</p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_schedule">
                        <div class="mb-3">
                            <label class="form-label">Day of Week</label>
                            <select name="day" class="form-select" required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Slot Duration (Minutes)</label>
                            <input type="number" name="duration" class="form-control" value="30" min="10" max="60" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Generate Slots Action -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">2. Generate Slots</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Once you have set your weekly schedule, click below to generate actual bookable slots for the next 30 days.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="generate_slots">
                        <button type="submit" class="btn btn-success w-100 p-3">Generate Slots Now</button>
                    </form>
                </div>
            </div>

            <!-- List Active Schedules -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Your Weekly Schedule</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week");
                    $stmt->execute([$_SESSION['user_id']]);
                    while ($row = $stmt->fetch()) {
                        echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                                {$row['day_of_week']}: {$row['start_time']} - {$row['end_time']} ({$row['slot_duration']} mins)
                              </li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- View Generated Slots -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Upcoming Generated Slots (Preview)</h5>
        </div>
        <div class="card-body">
             <div class="row">
                <?php
                $stmt = $pdo->prepare("SELECT slot_datetime, is_booked FROM appointment_slots WHERE doctor_id = ? AND slot_datetime >= NOW() ORDER BY slot_datetime LIMIT 20");
                $stmt->execute([$_SESSION['user_id']]);
                $slots = $stmt->fetchAll();

                if (count($slots) > 0) {
                    foreach ($slots as $slot) {
                        $statusClass = $slot['is_booked'] ? 'bg-danger text-white' : 'bg-light text-dark border';
                        $statusText = $slot['is_booked'] ? 'Booked' : 'Open';
                        echo "<div class='col-md-3 mb-2'>
                                <div class='p-2 rounded text-center $statusClass'>
                                    " . date('D, M d H:i', strtotime($slot['slot_datetime'])) . "<br>
                                    <small>$statusText</small>
                                </div>
                              </div>";
                    }
                } else {
                    echo "<p class='text-muted'>No slots generated yet.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
