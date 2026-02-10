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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $appt_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes']);

    try {
        // Ensure this appointment belongs to the logged-in doctor
        $stmt = $pdo->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ? AND doctor_id = ?");
        $stmt->execute([$new_status, $notes, $appt_id, $_SESSION['user_id']]);
        setFlashMessage('success', "Appointment updated successfully!", 'success');
        redirect('dashboard.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', "Error updating appointment: " . $e->getMessage(), 'danger');
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <h2>Doctor Dashboard</h2>
    <p>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>

    <div class="card mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Upcoming Appointments</h5>
            <a href="schedule.php" class="btn btn-light btn-sm">Manage Schedule</a>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient Name</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT a.*, u.name AS patient_name
                            FROM appointments a
                            JOIN users u ON a.patient_id = u.id
                            WHERE a.doctor_id = ?
                            ORDER BY a.appointment_date ASC
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $appointments = $stmt->fetchAll();

                        if (count($appointments) > 0) {
                            foreach ($appointments as $appt) {
                                echo "<tr>";
                                echo "<td>" . date('M d, Y g:i A', strtotime($appt['appointment_date'])) . "</td>";
                                echo "<td>" . htmlspecialchars($appt['patient_name']) . "</td>";
                                echo "<td><span class='badge bg-" . ($appt['status'] == 'confirmed' ? 'success' : ($appt['status'] == 'pending' ? 'warning' : 'secondary')) . "'>" . ucfirst($appt['status']) . "</span></td>";
                                echo "<td>" . htmlspecialchars($appt['notes']) . "</td>";
                                echo "<td>
                                        <form method='POST' action='' class='d-flex gap-2'>
                                            <input type='hidden' name='action' value='update_status'>
                                            <input type='hidden' name='appointment_id' value='" . $appt['id'] . "'>
                                            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                            <select name='status' class='form-select form-select-sm'>
                                                <option value='pending' " . ($appt['status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                                                <option value='confirmed' " . ($appt['status'] == 'confirmed' ? 'selected' : '') . ">Confirmed</option>
                                                <option value='completed' " . ($appt['status'] == 'completed' ? 'selected' : '') . ">Completed</option>
                                                <option value='cancelled' " . ($appt['status'] == 'cancelled' ? 'selected' : '') . ">Cancelled</option>
                                            </select>
                                            <input type='text' name='notes' class='form-control form-control-sm' placeholder='Notes' value='" . htmlspecialchars($appt['notes']) . "'>
                                            <button type='submit' class='btn btn-sm btn-primary'>Update</button>
                                            <a href='create_prescription.php?appointment_id=" . $appt['id'] . "' class='btn btn-sm btn-success'><i class='fas fa-file-prescription'></i> Rx</a>
                                        </form>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No appointments found.</td></tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5' class='text-danger'>Error loading appointments: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
