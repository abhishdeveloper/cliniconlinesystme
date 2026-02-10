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

// Handle Add Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_medicine') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        $error = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $default_dosage = trim($_POST['default_dosage']);
        $stock = intval($_POST['stock_quantity']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $error = "Medicine name is required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO medicines (name, type, default_dosage, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $type, $default_dosage, $stock, $description])) {
                    setFlashMessage('success', "Medicine '$name' added successfully!", 'success');
                    redirect('medicines.php');
                } else {
                    $error = "Failed to add medicine.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', "Invalid CSRF token.", 'danger');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
            $stmt->execute([$_POST['delete_id']]);
            setFlashMessage('success', "Medicine deleted successfully!", 'success');
            redirect('medicines.php');
        } catch (PDOException $e) {
            setFlashMessage('danger', "Error deleting medicine: " . $e->getMessage(), 'danger');
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
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="add_medicine">
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
                            <label class="form-label">Initial Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" value="0" min="0">
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
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Existing Medicines</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Stock</th>
                                    <th>Dosage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
                                while ($med = $stmt->fetch()) {
                                    $stockClass = ($med['stock_quantity'] < 10) ? 'text-danger fw-bold' : 'text-success';
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($med['name']) . "</strong><br><small class='text-muted'>" . htmlspecialchars($med['description']) . "</small></td>";
                                    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($med['type']) . "</span></td>";
                                    echo "<td class='$stockClass'>" . $med['stock_quantity'] . "</td>";
                                    echo "<td>" . htmlspecialchars($med['default_dosage']) . "</td>";
                                    echo "<td>
                                            <form method='POST' action='' style='display:inline;'>
                                                <input type='hidden' name='csrf_token' value='" . generateCsrfToken() . "'>
                                                <input type='hidden' name='delete_id' value='" . $med['id'] . "'>
                                                <button type='submit' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></button>
                                            </form>
                                          </td>";
                                    echo "</tr>";
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
