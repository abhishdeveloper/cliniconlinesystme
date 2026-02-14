<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a superadmin
if (!isLoggedIn('superadmin')) {
    setFlashMessage('danger', 'Access denied.', 'danger');
    redirect('/login.php');
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf_token)) {
        setFlashMessage('danger', 'Invalid CSRF token.', 'danger');
    } else {
        $delete_id = $_POST['delete_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            setFlashMessage('success', 'User deleted successfully.', 'success');
            redirect('manage_users.php');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Error deleting user: ' . $e->getMessage(), 'danger');
        }
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role, name");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('danger', 'Error fetching users: ' . $e->getMessage(), 'danger');
    $users = [];
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Manage Users</h2>

        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $key => $msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $msg['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash'][$key]); ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Users</h5>
                <a href="add_user.php" class="btn btn-primary btn-sm">Add New User</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'superadmin' ? 'danger' : ($user['role'] === 'admin' ? 'warning' : ($user['role'] === 'doctor' ? 'success' : 'info')); ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($user['role'] !== 'superadmin'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>Delete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
             <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
