<?php
// Start session and include functions/config first, before any output
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if already logged in
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'doctor') {
        redirect('doctor/dashboard.php');
    } else {
        redirect('patient/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                setFlashMessage('success', "Welcome back, " . htmlspecialchars($user['name']) . "!", 'success');

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } elseif ($user['role'] === 'doctor') {
                    redirect('doctor/dashboard.php');
                } else {
                    redirect('patient/dashboard.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            // Log error in production, show generic message
            error_log("System error: " . $e->getMessage());
            $error = "System error occurred.";
        }
    }
}

// Now include the header which starts output
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                <small>Don't have an account? <a href="/register.php">Register here</a></small>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
