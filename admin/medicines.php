<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin
if (!isLoggedIn('admin')) {
    setFlashMessage('danger', "Access Denied. Admins only.", 'danger');
    redirect('/login.php');
}

$error = '';
$success = '';
$csrf_token = generate_csrf_token();

// Handle Add Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_medicine') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $default_dosage = trim($_POST['default_dosage']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $error = "Medicine name is required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO medicines (name, type, default_dosage, description) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $type, $default_dosage, $description])) {
                    setFlashMessage('success', "Medicine '$name' added successfully!", 'success');
                    redirect('medicines.php');
                } else {
                    $error = "Failed to add medicine.";
                }
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage()); $error = "An unexpected error occurred.";
            }
        }
    }
}

// Handle Delete Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_medicine') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        $delete_id = $_POST['id'];
        if (is_numeric($delete_id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
                $stmt->execute([$delete_id]);
                setFlashMessage('success', "Medicine deleted successfully!", 'success');
                redirect('medicines.php');
            } catch (PDOException $e) {
                error_log("Error deleting medicine: " . $e->getMessage()); setFlashMessage('danger', "An unexpected error occurred.", 'danger');
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Ayurvedic Medicines</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="row">
        <!-- Add Medicine Form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Add New Medicine</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_medicine">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="mb-3">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Ashwagandha">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="Powder">Powder (Churna)</option>
                                <option value="Tablet">Tablet (Vati)</option>
                                <option value="Syrup">Syrup (Arishta/Asava)</option>
                                <option value="Oil">Oil (Taila)</option>
                                <option value="Paste">Paste (Lehya)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default Dosage</label>
                            <input type="text" name="default_dosage" class="form-control" placeholder="e.g., 1 tsp twice daily">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description/Notes</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Medicine</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Medicines -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Existing Medicines</h5>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <form method="GET" action="" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Search by name, type, or description..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                            <?php if(isset($_GET['q']) && $_GET['q'] !== ''): ?>
                                <a href="medicines.php" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Dosage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $search = isset($_GET['q']) ? trim($_GET['q']) : '';
                                if ($search) {
                                    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE name LIKE ? OR type LIKE ? OR description LIKE ? ORDER BY name ASC");
                                    $params = ["%$search%", "%$search%", "%$search%"];
                                    $stmt->execute($params);
                                } else {
                                    $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
                                }

                                $meds = $stmt->fetchAll();

                                if (count($meds) > 0) {
                                    foreach ($meds as $med) {
                                        echo "<tr>";
                                        echo "<td><strong>" . htmlspecialchars($med['name']) . "</strong><br><small class='text-muted'>" . htmlspecialchars($med['description']) . "</small></td>";
                                        echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($med['type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($med['default_dosage']) . "</td>";
                                        echo "<td>
                                                <form method='POST' action='' style='display:inline-block;'>
                                                    <input type='hidden' name='action' value='delete_medicine'>
                                                    <input type='hidden' name='id' value='{$med['id']}'>
                                                    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($csrf_token) . "'>
                                                    <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></button>
                                                </form>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center text-muted'>No medicines found.</td></tr>";
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

<?php require_once '../includes/footer.php'; ?>
