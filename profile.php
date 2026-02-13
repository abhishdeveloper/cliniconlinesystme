<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // User not found (maybe deleted?)
        session_destroy();
        redirect('login.php');
    }
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Token Verification Failed");
    }

    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Sanitize Phone (Remove non-digits/non-plus) for consistent storage/validation
    $phone_sanitized = preg_replace('/[^+0-9]/', '', $phone);

    // Validation
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($phone) && !empty($phone_sanitized) && !preg_match("/^\+?[0-9]{7,15}$/", $phone_sanitized)) {
        $error = "Invalid phone number format.";
    } else {
        // Use sanitized phone for storage
        $phone = $phone_sanitized;
        // Check if email exists for *another* user
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "Email is already taken by another user.";
            } else {
                // Password Update Logic
                if (!empty($password)) {
                    if ($password !== $confirm_password) {
                        $error = "Passwords do not match.";
                    } elseif (strlen($password) < 6) {
                        $error = "Password must be at least 6 characters.";
                    } else {
                        // Update with password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?";
                        $params = [$name, $email, $phone, $hashed_password, $user_id];
                    }
                } else {
                    // Update without password
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                    $params = [$name, $email, $phone, $user_id];
                }

                if (empty($error)) {
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        $success = "Profile updated successfully!";
                        // Update session name if changed
                        $_SESSION['user_name'] = $name;
                        // Refresh user data for form
                        $user['name'] = $name;
                        $user['email'] = $email;
                        $user['phone'] = $phone;
                    } else {
                        $error = "Failed to update profile.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mt-5">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+15551234567">
                             <div class="form-text">Optional. Format: +1234567890 (Digits only, optional + prefix)</div>
                        </div>

                        <hr>
                        <h5 class="mb-3">Change Password <small class="text-muted">(Leave blank to keep current)</small></h5>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-flex justify-content-between">
                             <a href="<?php echo $_SESSION['role'] == 'admin' ? '/admin/dashboard.php' : ($_SESSION['role'] == 'doctor' ? '/doctor/dashboard.php' : '/patient/dashboard.php'); ?>" class="btn btn-secondary">Back to Dashboard</a>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
