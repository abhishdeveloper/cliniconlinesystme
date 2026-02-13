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

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                    $success = "User ($role) created successfully!";
                } else {
                    $error = "Failed to create user.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Create New User (Doctor/Admin)</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="doctor">Doctor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
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
