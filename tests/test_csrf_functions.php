<?php
define('TEST_MODE', true);
// Suppress output from functions.php if any (it shouldn't output)
ob_start();
require '../includes/functions.php';
ob_end_clean();

// Test Generation
$token1 = generate_csrf_token();
if (strlen($token1) !== 64) {
    die("Token length mismatch.\n");
}

// Test Verification
if (!verify_csrf_token($token1)) {
    die("Token verification failed.\n");
}

if (verify_csrf_token("invalid")) {
    die("Invalid token verified successfully!\n");
}

// Test Persistence
$token2 = generate_csrf_token();
if ($token1 !== $token2) {
    die("Token should persist in session.\n");
}

echo "CSRF Functions Test Passed.\n";
?>
