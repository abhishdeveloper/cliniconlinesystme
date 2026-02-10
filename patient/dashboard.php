<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php'; // Include Notification System

// Check if patient
if (!isLoggedIn('patient')) {
    setFlashMessage('danger', "Access Denied. Patients only.", 'danger');
    redirect('/login.php');
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
    exit;
}

// Handle Booking Initialization (Redirect to Payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $doctor_id = $_POST['doctor_id'];
        $slot_id = $_POST['slot_id'];
        $notes = trim($_POST['notes']);

        if (empty($doctor_id) || empty($slot_id)) {
            setFlashMessage('danger', "Please select a doctor and a time slot.", 'danger');
        } else {
            // Verify slot availability first
            $stmt = $pdo->prepare("SELECT id FROM appointment_slots WHERE id = ? AND is_booked = 0");
            $stmt->execute([$slot_id]);
            if (!$stmt->fetch()) {
                setFlashMessage('danger', "Sorry, this slot is no longer available.", 'danger');
            } else {
                // Redirect to Payment Page with details
                // Ideally, we should encrypt or store this in session to prevent tampering,
                // but for this simple version, passing IDs is acceptable if validated again on payment page.
                // Better: Store in Session.
                $_SESSION['pending_booking'] = [
                    'doctor_id' => $doctor_id,
                    'slot_id' => $slot_id,
                    'notes' => $notes
                ];
                redirect('payment.php');
            }
        }
    }
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_report') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);

        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
            $uploadResult = uploadFile($_FILES['report_file'], '../uploads/reports/');

            if ($uploadResult['success']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO patient_reports (patient_id, title, file_path, description, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                    // Store relative path for web access: uploads/reports/filename.ext
                    // uploadFile returns relative to where it was called (e.g. ../uploads...), we want it relative to root for links
                    // We can strip the leading '../'
                    $webPath = str_replace('../', '', $uploadResult['path']);

                    $stmt->execute([$_SESSION['user_id'], $title, $webPath, $description, $_SESSION['user_id']]);
                    setFlashMessage('success', "Report uploaded successfully!", 'success');
                    redirect('dashboard.php');
                } catch (PDOException $e) {
                    setFlashMessage('danger', "Database error: " . $e->getMessage(), 'danger');
                }
            } else {
                setFlashMessage('danger', $uploadResult['message'], 'danger');
            }
        } else {
            setFlashMessage('danger', "Please select a valid file.", 'danger');
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h2>Patient Dashboard</h2>
        <div>
            <?php
            $stmt = $pdo->prepare("SELECT prakruti FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $prakruti = $stmt->fetchColumn();
            if ($prakruti) {
                echo "<span class='badge bg-info text-dark p-2 fs-6'>Prakruti: " . htmlspecialchars($prakruti) . "</span>";
            }
            ?>
        </div>
    </div>

    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>

    <div class="row">
        <!-- Booking Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100 mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Book Appointment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes / Symptoms</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Describe your issue..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Confirm Booking</button>
                    </form>
                </div>
            </div>

            <!-- Upload Report Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-upload"></i> Upload Report</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="upload_report">

                        <div class="mb-3">
                            <label class="form-label">Report Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Blood Test">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File (PDF/JPG/PNG)</label>
                            <input type="file" name="report_file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-8 mb-4">

            <!-- Appointments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> My Appointments & Prescriptions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT a.id, a.appointment_date, a.status, a.notes,
                                               a.payment_status, a.payment_method,
                                               u.name AS doctor_name,
                                               p.id AS prescription_id
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

                                            $payBadge = ($appt['payment_status'] === 'paid') ? 'bg-success' : 'bg-warning text-dark';

                                            echo "<tr>";
                                            echo "<td><strong>$dateStr</strong><br><small class='text-muted'>$timeStr</small></td>";
                                            echo "<td>Dr. " . htmlspecialchars($appt['doctor_name']) . "</td>";
                                            echo "<td><span class='badge $badgeClass rounded-pill'>" . ucfirst($appt['status']) . "</span></td>";
                                            echo "<td><span class='badge $payBadge'>" . ucfirst($appt['payment_status']) . "</span><br><small class='text-muted'>" . ucfirst($appt['payment_method'] ?? '') . "</small></td>";

                                            echo "<td>";
                                            if (!empty($appt['meeting_link']) && $appt['status'] === 'confirmed') {
                                                 echo "<a href='/video_call.php?appointment_id=" . $appt['id'] . "' class='btn btn-sm btn-danger me-2' target='_blank'><i class='fas fa-video'></i> Join Call</a>";
                                            }

                                            if ($appt['prescription_id']) {
                                                echo "<a href='/prescription_view.php?id=" . $appt['prescription_id'] . "' class='btn btn-sm btn-outline-primary' target='_blank'><i class='fas fa-file-prescription'></i> View Rx</a>";
                                            } else {
                                                echo "<span class='text-muted small'>No Rx</span>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted'>No appointments found.</td></tr>";
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

            <!-- My Reports -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-medical-alt"></i> My Medical Reports</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM patient_reports WHERE patient_id = ? ORDER BY created_at DESC");
                                $stmt->execute([$_SESSION['user_id']]);
                                $reports = $stmt->fetchAll();

                                if (count($reports) > 0) {
                                    foreach ($reports as $report) {
                                        echo "<tr>";
                                        echo "<td>" . date('M d, Y', strtotime($report['created_at'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($report['title']) . "</td>";
                                        echo "<td><small>" . htmlspecialchars($report['description']) . "</small></td>";
                                        echo "<td><a href='/" . htmlspecialchars($report['file_path']) . "' class='btn btn-sm btn-primary' target='_blank'><i class='fas fa-download'></i> Download</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted'>No reports uploaded yet.</td></tr>";
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
function loadSlots(doctorId) {
    const slotSelect = document.getElementById('slot_select');

    if (!doctorId) {
        slotSelect.innerHTML = '<option value="">Select a Doctor First</option>';
        slotSelect.disabled = true;
        return;
    }

    slotSelect.disabled = true;
    slotSelect.innerHTML = '<option>Loading...</option>';

    fetch('dashboard.php?action=get_slots&doctor_id=' + doctorId)
        .then(response => response.text())
        .then(html => {
            slotSelect.innerHTML = html;
            slotSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            slotSelect.innerHTML = '<option>Error loading slots</option>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
