<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if admin
if (!isLoggedIn('admin')) {
    setFlashMessage('danger', "Access Denied. Admins only.", 'danger');
    redirect('/login.php');
}

$error = '';
$success = '';

// Handle Add Medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_medicine') {
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
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Delete Medicine
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        setFlashMessage('success', "Medicine deleted successfully!", 'success');
        redirect('medicines.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', "Error deleting medicine: " . $e->getMessage(), 'danger');
    }
}

require_once __DIR__ . '/../includes/header.php';
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
                                    <th>Dosage</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Pagination Logic
                                $limit = 10;
                                $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                                $offset = ($page - 1) * $limit;

                                // Get total records for pagination
                                $total_stmt = $pdo->query("SELECT COUNT(*) FROM medicines");
                                $total_rows = $total_stmt->fetchColumn();
                                $total_pages = ceil($total_rows / $limit);

                                // Fetch medicines with limit
                                $stmt = $pdo->prepare("SELECT * FROM medicines ORDER BY name ASC LIMIT :limit OFFSET :offset");
                                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                                $stmt->execute();

                                while ($med = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($med['name']) . "</strong><br><small class='text-muted'>" . htmlspecialchars($med['description']) . "</small></td>";
                                    echo "<td><span class='badge bg-secondary'>" . htmlspecialchars($med['type']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($med['default_dosage']) . "</td>";
                                    echo "<td>
                                            <a href='?delete={$med['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></a>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
