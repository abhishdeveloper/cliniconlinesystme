<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if doctor
if (!isLoggedIn('doctor')) {
    setFlashMessage('danger', "Access Denied. Doctors only.", 'danger');
    redirect('/login.php');
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $appt_id = $_POST['appointment_id'];
        $new_status = $_POST['status'];
        $notes = trim($_POST['notes']);

        try {
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$new_status, $notes, $appt_id, $_SESSION['user_id']]);
            setFlashMessage('success', "Appointment updated successfully!", 'success');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            setFlashMessage('danger', "Error updating appointment: " . $e->getMessage(), 'danger');
        }
    }
}

// Handle Start Call
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_call') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
         setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $appt_id = $_POST['appointment_id'];

        try {
            // Check if link already exists
            $stmt = $pdo->prepare("SELECT meeting_link FROM appointments WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$appt_id, $_SESSION['user_id']]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                // Generate simple unique room name
                $roomName = "ClinicAppt-" . $appt_id . "-" . bin2hex(random_bytes(4));
                $stmt = $pdo->prepare("UPDATE appointments SET meeting_link = ? WHERE id = ?");
                $stmt->execute([$roomName, $appt_id]);
                setFlashMessage('success', "Video call room created. Joining now...", 'success');
            } else {
                setFlashMessage('info', "Rejoining existing call...", 'info');
            }
            // Redirect to video call page
            redirect('../video_call.php?appointment_id=' . $appt_id);

        } catch (PDOException $e) {
             setFlashMessage('danger', "Error starting call: " . $e->getMessage(), 'danger');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4">Doctor Dashboard</h2>
    <p class="mb-4">Welcome, Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">Manage Schedule</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="schedule.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <!-- Add more stats cards here if needed -->
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-check me-1"></i>
            Upcoming Appointments
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient Name</th>
                            <th>Prakruti</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT a.id, a.appointment_date, a.status, a.notes, a.meeting_link, u.name AS patient_name, u.prakruti
                                FROM appointments a
                                JOIN users u ON a.patient_id = u.id
                                WHERE a.doctor_id = ?
                                ORDER BY a.appointment_date ASC
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            $appointments = $stmt->fetchAll();

                            if (count($appointments) > 0) {
                                foreach ($appointments as $appt) {
                                    $statusColor = match($appt['status']) {
                                        'confirmed' => 'success',
                                        'completed' => 'primary',
                                        'cancelled' => 'danger',
                                        default => 'warning'
                                    };

                                    echo "<tr>";
                                    echo "<td>" . date('M d, Y g:i A', strtotime($appt['appointment_date'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($appt['patient_name']) . "</td>";
                                    echo "<td>" . ($appt['prakruti'] ? htmlspecialchars($appt['prakruti']) : '<span class="text-muted">N/A</span>') . "</td>";
                                    echo "<td><span class='badge bg-{$statusColor}'>" . ucfirst($appt['status']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($appt['notes']) . "</td>";

                                    // Status Update Form
                                    echo "<td>
                                            <form method='POST' action='' class='d-flex gap-2 align-items-center'>
                                                <input type='hidden' name='csrf_token' value='" . generateCsrfToken() . "'>
                                                <input type='hidden' name='action' value='update_status'>
                                                <input type='hidden' name='appointment_id' value='" . $appt['id'] . "'>
                                                <select name='status' class='form-select form-select-sm' style='width: auto;'>
                                                    <option value='pending' " . ($appt['status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                                                    <option value='confirmed' " . ($appt['status'] == 'confirmed' ? 'selected' : '') . ">Confirmed</option>
                                                    <option value='completed' " . ($appt['status'] == 'completed' ? 'selected' : '') . ">Completed</option>
                                                    <option value='cancelled' " . ($appt['status'] == 'cancelled' ? 'selected' : '') . ">Cancelled</option>
                                                </select>
                                                <button type='submit' class='btn btn-sm btn-outline-primary'>Save</button>
                                            </form>
                                          </td>";

                                    // Action Buttons
                                    echo "<td>
                                            <div class='d-flex gap-2'>
                                                <a href='create_prescription.php?appointment_id=" . $appt['id'] . "' class='btn btn-sm btn-success'><i class='fas fa-file-prescription'></i> Rx</a>";

                                    // Video Call Button (only if confirmed)
                                    if ($appt['status'] === 'confirmed') {
                                        $btnText = $appt['meeting_link'] ? '<i class="fas fa-video"></i> Join Call' : '<i class="fas fa-video"></i> Start Call';
                                        $btnClass = $appt['meeting_link'] ? 'btn-danger' : 'btn-primary';

                                        echo "<form method='POST' action=''>
                                                <input type='hidden' name='csrf_token' value='" . generateCsrfToken() . "'>
                                                <input type='hidden' name='action' value='start_call'>
                                                <input type='hidden' name='appointment_id' value='" . $appt['id'] . "'>
                                                <button type='submit' class='btn btn-sm $btnClass'>$btnText</button>
                                              </form>";
                                    }

                                    echo "  </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No appointments found.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='7' class='text-danger'>Error loading appointments: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
