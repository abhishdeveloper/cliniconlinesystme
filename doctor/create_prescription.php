<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php'; // Include Notification System

// Check if doctor
if (!isLoggedIn('doctor')) {
    setFlashMessage('danger', "Access Denied. Doctors only.", 'danger');
    redirect('/login.php');
}

// Get Appointment Details
if (!isset($_GET['appointment_id']) || !is_numeric($_GET['appointment_id'])) {
    setFlashMessage('danger', "Invalid appointment.", 'danger');
    redirect('dashboard.php');
}

$appointment_id = $_GET['appointment_id'];

// Fetch Appointment & Patient Data
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, u.email AS patient_email, u.phone AS patient_phone, u.prakruti, u.id as patient_id
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.id = ? AND a.doctor_id = ?
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlashMessage('danger', "Appointment not found or access denied.", 'danger');
    redirect('dashboard.php');
}

$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $diagnosis = trim($_POST['diagnosis']);
        $notes = trim($_POST['notes']);

        // Ayurvedic Fields
        $prakruti = trim($_POST['prakruti']);
        $vikruti = trim($_POST['vikruti']);
        $pulse = trim($_POST['pulse']);
        $tongue = trim($_POST['tongue']);
        $skin = trim($_POST['skin']);
    }

    // Process Medicines
    $medicines = [];
    if (isset($_POST['med_name']) && is_array($_POST['med_name'])) {
        for ($i = 0; $i < count($_POST['med_name']); $i++) {
            if (!empty($_POST['med_name'][$i])) {
                $medicines[] = [
                    'name' => $_POST['med_name'][$i],
                    'type' => $_POST['med_type'][$i],
                    'dosage' => $_POST['med_dosage'][$i],
                    'quantity' => intval($_POST['med_quantity'][$i] ?? 1),
                    'duration' => $_POST['med_duration'][$i]
                ];
            }
        }
    }

    if ($error) {
        // Do nothing, error already set
    } elseif (empty($diagnosis)) {
        $error = "Diagnosis is required.";
    } else {
        try {
            $pdo->beginTransaction();

            $medicines_json = json_encode($medicines);

            // Update Patient Prakruti if changed
            if (!empty($prakruti) && $prakruti !== $appointment['prakruti']) {
                $updUser = $pdo->prepare("UPDATE users SET prakruti = ? WHERE id = ?");
                $updUser->execute([$prakruti, $appointment['patient_id']]);
            }

            // Check if prescription already exists
            $check = $pdo->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
            $check->execute([$appointment_id]);

            if ($check->fetch()) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE prescriptions SET
                    diagnosis = ?, medicines_json = ?, notes = ?,
                    vikruti = ?, pulse = ?, tongue = ?, skin = ?
                    WHERE appointment_id = ?");
                $stmt->execute([$diagnosis, $medicines_json, $notes, $vikruti, $pulse, $tongue, $skin, $appointment_id]);
                setFlashMessage('success', "Prescription updated successfully!", 'success');
            } else {
                // Create new
                $stmt = $pdo->prepare("INSERT INTO prescriptions
                    (appointment_id, patient_id, doctor_id, diagnosis, medicines_json, notes, vikruti, pulse, tongue, skin)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $appointment_id, $appointment['patient_id'], $_SESSION['user_id'],
                    $diagnosis, $medicines_json, $notes, $vikruti, $pulse, $tongue, $skin
                ]);

                // Deduct Stock
                $deduct = $pdo->prepare("UPDATE medicines SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE name = ?");
                foreach ($medicines as $med) {
                    $deduct->execute([$med['quantity'], $med['name']]);
                }

                // Mark appointment as completed
                $upd = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
                $upd->execute([$appointment_id]);

                setFlashMessage('success', "Prescription generated successfully!", 'success');
            }

            // Notify Patient of Prescription
            $msg = "Hello {$appointment['patient_name']}, your prescription is ready. Please log in to view it.";
            sendEmail($appointment['patient_email'], "Prescription Ready", $msg);
            sendSMS($appointment['patient_phone'], $msg);

            $pdo->commit();
            redirect('dashboard.php');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error saving prescription: " . $e->getMessage();
        }
    }
}

// Fetch Admin Medicines for Datalist
try {
    $stmt = $pdo->query("SELECT name, type, default_dosage FROM medicines ORDER BY name ASC");
    $admin_meds = $stmt->fetchAll();
} catch (PDOException $e) {
    $admin_meds = [];
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Ayurvedic Prescription</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Patient Details</h5>
        </div>
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong>Name:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                <div class="col-md-4"><strong>Email:</strong> <?php echo htmlspecialchars($appointment['patient_email']); ?></div>
                <div class="col-md-4"><strong>Date:</strong> <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
            </div>

            <!-- View Reports Button -->
             <div class="row mt-3">
                 <div class="col-md-12">
                     <button type="button" class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#patientReports">
                         <i class="fas fa-file-medical"></i> View Patient Reports
                     </button>

                     <div class="collapse mt-3" id="patientReports">
                         <div class="card card-body bg-light">
                             <h6>Uploaded Medical Reports</h6>
                             <ul class="list-group">
                                 <?php
                                 $stmt = $pdo->prepare("SELECT * FROM patient_reports WHERE patient_id = ? ORDER BY created_at DESC");
                                 $stmt->execute([$appointment['patient_id']]);
                                 $reports = $stmt->fetchAll();

                                 if (count($reports) > 0) {
                                     foreach ($reports as $report) {
                                         echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                                                <div>
                                                    <strong>" . htmlspecialchars($report['title']) . "</strong>
                                                    <small class='text-muted d-block'>" . htmlspecialchars($report['description']) . " (" . date('M d, Y', strtotime($report['created_at'])) . ")</small>
                                                </div>
                                                <a href='/" . htmlspecialchars($report['file_path']) . "' class='btn btn-sm btn-primary' target='_blank'>Download</a>
                                              </li>";
                                     }
                                 } else {
                                     echo "<li class='list-group-item'>No reports found.</li>";
                                 }
                                 ?>
                             </ul>
                         </div>
                     </div>
                 </div>
             </div>
        </div>
    </div>

    <form method="POST" id="prescriptionForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="row">
            <!-- Left Column: Examination -->
            <div class="col-md-4">
                <div class="card shadow mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Rog Pariksha (Examination)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Prakruti (Constitution)</label>
                            <div class="input-group">
                                <input type="text" name="prakruti" id="prakruti" class="form-control" value="<?php echo htmlspecialchars($appointment['prakruti'] ?? ''); ?>" placeholder="e.g. Vata-Pitta">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#prakrutiModal">Calc</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vikruti (Imbalance)</label>
                            <input type="text" name="vikruti" class="form-control" placeholder="Current State">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nadi (Pulse)</label>
                            <input type="text" name="pulse" class="form-control" placeholder="Pulse reading">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jihva (Tongue)</label>
                            <input type="text" name="tongue" class="form-control" placeholder="Coating, color...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sparsha (Skin)</label>
                            <input type="text" name="skin" class="form-control" placeholder="Temperature, texture...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Diagnosis & Medicines -->
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Treatment Plan</h5>
                    </div>
                    <div class="card-body">

                        <!-- Diagnosis -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Diagnosis / Samprapti</label>
                            <textarea name="diagnosis" class="form-control" rows="2" required placeholder="Disease pathology and conclusion..."></textarea>
                        </div>

                        <!-- Medicines Table -->
                        <h5 class="mb-3 border-bottom pb-2">Medicines</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="medTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Medicine</th>
                                        <th width="15%">Type</th>
                                        <th width="10%">Qty</th>
                                        <th width="25%">Dosage</th>
                                        <th width="15%">Duration</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="med_name[]" class="form-control" list="medList" placeholder="Select"></td>
                                        <td>
                                            <select name="med_type[]" class="form-select">
                                                <option value="Powder">Powder</option>
                                                <option value="Tablet">Tablet</option>
                                                <option value="Syrup">Syrup</option>
                                                <option value="Oil">Oil</option>
                                                <option value="Paste">Paste</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="med_quantity[]" class="form-control" value="1" min="1"></td>
                                        <td><input type="text" name="med_dosage[]" class="form-control"></td>
                                        <td><input type="text" name="med_duration[]" class="form-control"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="addMedBtn"><i class="fas fa-plus"></i> Add Medicine</button>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Pathya / Apathya (Advice)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Diet and Lifestyle recommendations..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Generate Prescription</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Datalist for Medicines -->
<datalist id="medList">
    <?php foreach ($admin_meds as $med): ?>
        <option value="<?php echo htmlspecialchars($med['name']); ?>"><?php echo htmlspecialchars($med['type']); ?></option>
    <?php endforeach; ?>
</datalist>

<!-- Prakruti Calculator Modal -->
<div class="modal fade" id="prakrutiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Prakruti Calculator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Select the dominant trait for each category:</p>
                <form id="prakrutiForm">
                    <div class="mb-3">
                        <label class="form-label">Body Frame</label>
                        <select class="form-select" name="frame">
                            <option value="vata">Thin, lean</option>
                            <option value="pitta">Medium, athletic</option>
                            <option value="kapha">Large, broad</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Skin Type</label>
                        <select class="form-select" name="skin">
                            <option value="vata">Dry, rough</option>
                            <option value="pitta">Sensitive, warm</option>
                            <option value="kapha">Oily, smooth</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Digestion</label>
                        <select class="form-select" name="digestion">
                            <option value="vata">Irregular</option>
                            <option value="pitta">Strong, fast</option>
                            <option value="kapha">Slow, steady</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="calculatePrakruti()">Calculate</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#medTable tbody');
    const addBtn = document.getElementById('addMedBtn');

    addBtn.addEventListener('click', function() {
        const row = tableBody.rows[0].cloneNode(true);
        row.querySelectorAll('input').forEach(input => input.value = '');
        tableBody.appendChild(row);
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            if (tableBody.rows.length > 1) {
                e.target.closest('tr').remove();
            }
        }
    });
});

function calculatePrakruti() {
    const form = document.getElementById('prakrutiForm');
    const formData = new FormData(form);
    let counts = { vata: 0, pitta: 0, kapha: 0 };

    for (let value of formData.values()) {
        if (counts[value] !== undefined) {
            counts[value]++;
        }
    }

    let max = 0;
    let types = [];

    // Check Vata
    if (counts.vata > max) {
        max = counts.vata;
        types = ['Vata'];
    } else if (counts.vata === max && max > 0) {
        types.push('Vata');
    }

    // Check Pitta
    if (counts.pitta > max) {
        max = counts.pitta;
        types = ['Pitta'];
    } else if (counts.pitta === max && max > 0) {
        types.push('Pitta');
    }

    // Check Kapha
    if (counts.kapha > max) {
        max = counts.kapha;
        types = ['Kapha'];
    } else if (counts.kapha === max && max > 0) {
        types.push('Kapha');
    }

    document.getElementById('prakruti').value = types.join('-');

    // Close modal
    var myModalEl = document.getElementById('prakrutiModal');
    var modal = bootstrap.Modal.getInstance(myModalEl);
    if (modal) {
        modal.hide();
    } else {
        // Fallback if instance not found (shouldn't happen with data-bs-toggle)
        new bootstrap.Modal(myModalEl).hide();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
