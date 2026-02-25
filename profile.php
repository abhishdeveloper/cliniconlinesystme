<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Generate CSRF token
generate_csrf_token();

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Error loading profile: " . $e->getMessage());
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialty = isset($_POST['specialty']) ? trim($_POST['specialty']) : null;

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if email belongs to another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "Email is already taken by another user.";
            } else {
                if ($user['role'] === 'doctor') {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, specialty = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $specialty, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $user_id]);
                }

                // Update Session Name if changed
                $_SESSION['user_name'] = $name;

                $success = "Profile updated successfully!";
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success = "Password changed successfully!";
            } catch (PDOException $e) {
                $error = "Error changing password: " . $e->getMessage();
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">My Profile</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Profile Details Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Personal Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1234567890">
                            </div>
                        </div>

                        <?php if ($user['role'] === 'doctor'): ?>
                        <div class="mb-3">
                            <label class="form-label">Specialty</label>
                            <input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($user['specialty'] ?? ''); ?>" placeholder="e.g., General Physician, Ayurveda Specialist">
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label text-muted">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled readonly>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Change Password Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning text-dark">Change Password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
