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

// User creation is restricted to Superadmin only.

require_once '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Admin Dashboard</h2>
            <p>Welcome, Admin!</p>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row mt-4 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="medicines.php" class="btn btn-primary"><i class="fas fa-pills"></i> Manage Ayurvedic Medicines</a>
                    <a href="prescriptions.php" class="btn btn-info text-white ms-2"><i class="fas fa-file-prescription"></i> View All Prescriptions</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
             <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Existing Doctors</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $stmt = $pdo->query("SELECT name, email FROM users WHERE role = 'doctor'");
                        while ($row = $stmt->fetch()) {
                            echo "<li class='list-group-item'>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['email']) . ")</li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
