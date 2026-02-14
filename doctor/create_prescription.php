<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

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

// Fetch Appointment & Patient Data (Verify ownership)
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, u.email AS patient_email, u.phone AS patient_phone
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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis = trim($_POST['diagnosis']);
    $notes = trim($_POST['notes']);

    // Process Medicines
    $medicines = [];
    if (isset($_POST['med_name']) && is_array($_POST['med_name'])) {
        for ($i = 0; $i < count($_POST['med_name']); $i++) {
            if (!empty($_POST['med_name'][$i])) {
                $medicines[] = [
                    'name' => $_POST['med_name'][$i],
                    'type' => $_POST['med_type'][$i],
                    'dosage' => $_POST['med_dosage'][$i],
                    'duration' => $_POST['med_duration'][$i]
                ];
            }
        }
    }

    if (empty($diagnosis)) {
        $error = "Diagnosis is required.";
    } else {
        try {
            $medicines_json = json_encode($medicines);

            // Check if prescription already exists (Update vs Insert)
            // For simplicity, let's just Insert. If exists, maybe we should have edited,
            // but requirement implies "Generate". Let's check to prevent duplicates.
            $check = $pdo->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
            $check->execute([$appointment_id]);

            if ($check->fetch()) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE prescriptions SET diagnosis = ?, medicines_json = ?, notes = ? WHERE appointment_id = ?");
                $stmt->execute([$diagnosis, $medicines_json, $notes, $appointment_id]);
                setFlashMessage('success', "Prescription updated successfully!", 'success');
            } else {
                // Create new
                $stmt = $pdo->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, medicines_json, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$appointment_id, $appointment['patient_id'], $_SESSION['user_id'], $diagnosis, $medicines_json, $notes]);

                // Mark appointment as completed if not already
                $upd = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
                $upd->execute([$appointment_id]);

                setFlashMessage('success', "Prescription generated successfully!", 'success');
            }
            redirect('dashboard.php');

        } catch (PDOException $e) {
            error_log("Error saving prescription: " . $e->getMessage()); $error = "An unexpected error occurred.";
        }
    }
}

// Fetch Admin Medicines for Datalist/Dropdown
$admin_meds = $pdo->query("SELECT name, type, default_dosage FROM medicines ORDER BY name ASC")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Prescription (Ayurveda)</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Patient: <?php echo htmlspecialchars($appointment['patient_name']); ?></h5>
            <small>Date: <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></small>
        </div>
        <div class="card-body">
            <form method="POST" id="prescriptionForm">

                <!-- Diagnosis Section -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Diagnosis / Prakriti Analysis</label>
                    <textarea name="diagnosis" class="form-control" rows="2" required placeholder="e.g., Vata-Pitta imbalance, Digestive issues..."></textarea>
                </div>

                <!-- Medicines Section -->
                <h5 class="mb-3 text-success border-bottom pb-2">Prescribed Medicines</h5>

                <table class="table table-bordered" id="medTable">
                    <thead class="table-light">
                        <tr>
                            <th width="30%">Medicine Name</th>
                            <th width="15%">Type</th>
                            <th width="25%">Dosage / Anupana</th>
                            <th width="20%">Duration</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" name="med_name[]" class="form-control" list="medList" placeholder="Select or Type">
                            </td>
                            <td>
                                <select name="med_type[]" class="form-select">
                                    <option value="Powder">Powder</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Syrup">Syrup</option>
                                    <option value="Oil">Oil</option>
                                    <option value="Paste">Paste</option>
                                    <option value="Decoction">Decoction</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="med_dosage[]" class="form-control" placeholder="e.g., 1 tsp with warm water">
                            </td>
                            <td>
                                <input type="text" name="med_duration[]" class="form-control" placeholder="e.g., 7 days">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="button" class="btn btn-outline-primary mb-4" id="addMedBtn"><i class="fas fa-plus"></i> Add Another Medicine</button>

                <!-- Additional Notes -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Pathya / Apathya (Diet & Lifestyle Advice)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Dietary restrictions, yoga recommendations..."></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Save & Generate Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Datalist for Auto-complete -->
<datalist id="medList">
    <?php foreach ($admin_meds as $med): ?>
        <option value="<?php echo htmlspecialchars($med['name']); ?>"><?php echo htmlspecialchars($med['type']); ?></option>
    <?php endforeach; ?>
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#medTable tbody');
    const addBtn = document.getElementById('addMedBtn');

    // Function to add new row
    addBtn.addEventListener('click', function() {
        const row = tableBody.rows[0].cloneNode(true);
        // Clear inputs
        row.querySelectorAll('input').forEach(input => input.value = '');
        tableBody.appendChild(row);
    });

    // Event delegation for delete button
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            if (tableBody.rows.length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('At least one medicine is required.');
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
