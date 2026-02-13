<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php'; // Include Notification System

// Check if patient
if (!isLoggedIn('patient')) {
    setFlashMessage('danger', "Access Denied. Patients only.", 'danger');
    redirect('/login.php');
}

// Initialize CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX Request for Slots
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    $doctor_id = intval($_GET['doctor_id']);
    try {
        $stmt = $pdo->prepare("SELECT id, slot_datetime FROM appointment_slots WHERE doctor_id = ? AND is_booked = 0 AND slot_datetime >= NOW() ORDER BY slot_datetime ASC LIMIT 50");
        $stmt->execute([$doctor_id]);
        $slots = $stmt->fetchAll();

        if (count($slots) > 0) {
            echo '<option value="">Select a Slot</option>';
            foreach ($slots as $slot) {
                $formatted = date('l, F j - g:i A', strtotime($slot['slot_datetime']));
                echo "<option value='{$slot['id']}'>{$formatted}</option>";
            }
        } else {
             echo '<option value="">No available slots found.</option>';
        }
    } catch (PDOException $e) {
        echo '<option value="">Error loading slots</option>';
    }
    exit; // Stop script execution for AJAX - Crucial!
}

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
        redirect('dashboard.php');
    }

    $doctor_id = $_POST['doctor_id'];
    $slot_id = $_POST['slot_id'];
    $notes = trim($_POST['notes']);

    if (empty($doctor_id) || empty($slot_id)) {
        setFlashMessage('danger', "Please select a doctor and a time slot.", 'danger');
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Verify Slot is still open (row locking if possible, usually fine for small scale)
            $check = $pdo->prepare("SELECT slot_datetime FROM appointment_slots WHERE id = ? AND is_booked = 0");
            $check->execute([$slot_id]);
            $slot = $check->fetch();

            if (!$slot) {
                $pdo->rollBack();
                setFlashMessage('danger', "Sorry, this slot was just taken. Please choose another.", 'danger');
            } else {
                // 2. Mark Slot as Booked
                $update = $pdo->prepare("UPDATE appointment_slots SET is_booked = 1 WHERE id = ?");
                $update->execute([$slot_id]);

                // 3. Create Appointment Record
                $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, notes, slot_id) VALUES (?, ?, ?, 'pending', ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $doctor_id, $slot['slot_datetime'], $notes, $slot_id]);

                $pdo->commit();

                // 4. Send Notifications
                // Get Doctor Email/Phone
                $doc_stmt = $pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
                $doc_stmt->execute([$doctor_id]);
                $doctor = $doc_stmt->fetch();

                // Get Patient Email/Phone (Current User)
                $pat_stmt = $pdo->prepare("SELECT email, phone, name FROM users WHERE id = ?");
                $pat_stmt->execute([$_SESSION['user_id']]);
                $patient = $pat_stmt->fetch();

                $appt_time = date('F j, Y g:i A', strtotime($slot['slot_datetime']));

                // Notify Patient
                $msg_pat = "Your appointment with Dr. {$doctor['name']} on $appt_time is confirmed.";

                if (!empty($patient['phone'])) {
                    sendSMS($patient['phone'], $msg_pat);
                }

                sendEmail($patient['email'], "Appointment Confirmed", $msg_pat);

                // Notify Doctor
                $msg_doc = "New appointment: {$patient['name']} on $appt_time.";

                if (!empty($doctor['phone'])) {
                    sendSMS($doctor['phone'], $msg_doc);
                }

                sendEmail($doctor['email'], "New Appointment Request", $msg_doc);

                setFlashMessage('success', "Appointment booked successfully! Confirmation sent.", 'success');
                redirect('dashboard.php');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('danger', "Error booking appointment: " . $e->getMessage(), 'danger');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <h2>Patient Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>

    <!-- Flash Messages handled in header -->

    <div class="row">
        <!-- Booking Form -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Book Appointment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="book_appointment">

                        <div class="mb-3">
                            <label class="form-label fw-bold">1. Select Doctor</label>
                            <select name="doctor_id" id="doctor_select" class="form-select" required onchange="loadSlots(this.value)">
                                <option value="">Select Doctor...</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'doctor'");
                                while ($doc = $stmt->fetch()) {
                                    echo "<option value='" . $doc['id'] . "'>" . htmlspecialchars($doc['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">2. Available Time Slots</label>
                            <select name="slot_id" id="slot_select" class="form-select" required disabled>
                                <option value="">Select a Doctor First</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Slots are based on doctor's schedule.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes / Symptoms</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Briefly describe your reason for visit..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check-circle"></i> Confirm Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> My Appointments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                    SELECT a.*, u.name AS doctor_name, p.id AS prescription_id
                                    FROM appointments a
                                    JOIN users u ON a.doctor_id = u.id
                                    LEFT JOIN prescriptions p ON a.id = p.appointment_id
                                    WHERE a.patient_id = ?
                                    ORDER BY a.appointment_date DESC
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $appointments = $stmt->fetchAll();

                                if (count($appointments) > 0) {
                                    foreach ($appointments as $appt) {
                                        $dateStr = date('M d, Y', strtotime($appt['appointment_date']));
                                        $timeStr = date('g:i A', strtotime($appt['appointment_date']));

                                        $badgeClass = match($appt['status']) {
                                            'confirmed' => 'bg-success',
                                            'pending' => 'bg-warning text-dark',
                                            'completed' => 'bg-primary',
                                            'cancelled' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };

                                        echo "<tr>";
                                        echo "<td><strong>$dateStr</strong><br><small class='text-muted'>$timeStr</small></td>";
                                        echo "<td>Dr. " . htmlspecialchars($appt['doctor_name']) . "</td>";
                                        echo "<td><span class='badge $badgeClass rounded-pill'>" . ucfirst($appt['status']) . "</span></td>";
                                        echo "<td><small>" . htmlspecialchars($appt['notes']) . "</small></td>";

                                        // Show prescription link if available
                                        if ($appt['prescription_id']) {
                                            echo "<br><a href='../prescription_view.php?id=" . $appt['prescription_id'] . "' class='btn btn-sm btn-outline-primary mt-1' target='_blank'><i class='fas fa-file-prescription'></i> View Rx</a>";
                                        }

                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No appointments found. Book one now!</td></tr>";
                                }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='4' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * AJAX Function to load available slots for selected doctor
 */
function loadSlots(doctorId) {
    const slotSelect = document.getElementById('slot_select');

    // Reset if no doctor selected
    if (!doctorId) {
        slotSelect.innerHTML = '<option value="">Select a Doctor First</option>';
        slotSelect.disabled = true;
        return;
    }

    // Show loading state
    slotSelect.disabled = true;
    slotSelect.innerHTML = '<option>Loading available slots...</option>';

    // Fetch slots
    fetch('dashboard.php?action=get_slots&doctor_id=' + doctorId)
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.text();
        })
        .then(html => {
            slotSelect.innerHTML = html;
            slotSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error fetching slots:', error);
            slotSelect.innerHTML = '<option>Error loading slots. Try again.</option>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
