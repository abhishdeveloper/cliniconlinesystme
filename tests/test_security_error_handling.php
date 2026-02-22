<?php
// tests/verify_fix.php

// Ensure we are in a testing context
// Backup the DB first.
if (file_exists('clinic.db')) {
    copy('clinic.db', 'clinic.db.test_backup');
}

try {
    require_once 'config/database.php';
    $pdo->exec("ALTER TABLE users RENAME TO users_bak_test");

    // --- TEST LOGIN ---
    echo "--- Testing login.php ---\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['email'] = 'admin@clinic.com';
    $_POST['password'] = 'admin123';

    ob_start();
    include 'login.php';
    $output = ob_get_clean();

    if (strpos($output, "An error occurred. Please try again later.") !== false) {
        echo "LOGIN PASS: Generic error message found.\n";
    } else {
        echo "LOGIN FAIL: Generic error message NOT found.\n";
    }

    // --- TEST REGISTER ---
    echo "--- Testing register.php ---\n";
    $_POST = [];
    $_POST['name'] = 'Test User';
    $_POST['email'] = 'test@example.com';
    $_POST['phone'] = '1234567890';
    $_POST['password'] = 'password123';
    $_POST['confirm_password'] = 'password123';

    ob_start();
    include 'register.php';
    $output_reg = ob_get_clean();

    if (strpos($output_reg, "An error occurred during registration. Please try again later.") !== false) {
        echo "REGISTER PASS: Generic error message found.\n";
    } else {
        echo "REGISTER FAIL: Generic error message NOT found.\n";
    }

} catch (Exception $e) {
    echo "Test Script Error: " . $e->getMessage() . "\n";
} finally {
    // Restore DB
    if (isset($pdo)) {
        try {
            $pdo->exec("ALTER TABLE users_bak_test RENAME TO users");
        } catch (Exception $e) {
        }
    }
    if (file_exists('clinic.db.test_backup')) {
        $pdo = null;
        gc_collect_cycles();
        sleep(1);
        copy('clinic.db.test_backup', 'clinic.db');
        unlink('clinic.db.test_backup');
    }
}
?>
