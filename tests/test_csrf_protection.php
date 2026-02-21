<?php
// Tests for CSRF Implementation (Positive and Negative)
require_once __DIR__ . '/../config/database.php';

// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Testing CSRF Protection...\n";

// 1. Setup Test User
$testEmail = 'csrf_test_' . time() . '@test.com';
$testPass = password_hash('password123', PASSWORD_DEFAULT);
try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
    $stmt->execute(['Original Name', $testEmail, $testPass]);
    $userId = $pdo->lastInsertId();
} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}

// 2. Simulate Login
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = 'patient';
$_SESSION['user_name'] = 'Original Name'; // Fix warning

// --- TEST CASE 1: Attack without Token ---
echo "\n[Test 1] POST request without CSRF token...\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_profile';
$_POST['name'] = 'Hacked Name';
$_POST['email'] = $testEmail;
$_POST['phone'] = '1234567890';
unset($_POST['csrf_token']); // Ensure no token

ob_start();
try {
    include __DIR__ . '/../profile.php';
} catch (Exception $e) {}
ob_end_clean();

// Verify Result
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['name'] === 'Original Name') {
    echo "PASS: Update rejected (Name is still 'Original Name').\n";
} else {
    echo "FAIL: Update succeeded without token!\n";
}

// --- TEST CASE 2: Valid Request with Token ---
echo "\n[Test 2] POST request WITH valid CSRF token...\n";

// Generate a valid token manually and put it in session
$validToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $validToken;

// Set up the request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'update_profile';
$_POST['name'] = 'Updated Name'; // New name
$_POST['email'] = $testEmail;
$_POST['phone'] = '1234567890';
$_POST['csrf_token'] = $validToken; // Include token

// Reset $user variable to simulate fresh state if needed, though profile.php re-fetches it.
// profile.php will see the session token we set.

ob_start();
try {
    include __DIR__ . '/../profile.php';
} catch (Exception $e) {}
ob_end_clean();

// Verify Result
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['name'] === 'Updated Name') {
    echo "PASS: Update accepted with valid token.\n";
} else {
    echo "FAIL: Update failed even with valid token (Name is '{$user['name']}').\n";
}

// Cleanup
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
?>
