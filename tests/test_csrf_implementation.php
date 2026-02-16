<?php
// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Manually include the functions file
require_once __DIR__ . '/../includes/functions.php';

echo "Testing CSRF Implementation...\n";

// Test 1: Generate Token
// Clear any existing session token for clean test
unset($_SESSION['csrf_token']);
$token = generate_csrf_token();
if (!empty($token) && isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
    echo "PASS: Token generated and stored in session.\n";
} else {
    echo "FAIL: Token generation failed.\n";
    exit(1);
}

// Test 2: Verify Valid Token
if (verify_csrf_token($token)) {
    echo "PASS: Valid token verification successful.\n";
} else {
    echo "FAIL: Valid token verification failed.\n";
    exit(1);
}

// Test 3: Verify Invalid Token
$invalid_token = "invalid_token_string";
if (!verify_csrf_token($invalid_token)) {
    echo "PASS: Invalid token rejected.\n";
} else {
    echo "FAIL: Invalid token accepted.\n";
    exit(1);
}

// Test 4: Verify Empty Token
if (!verify_csrf_token("")) {
    echo "PASS: Empty token rejected.\n";
} else {
    echo "FAIL: Empty token accepted.\n";
    exit(1);
}

// Test 5: Verify when session token is missing
$temp_token = $_SESSION['csrf_token'];
unset($_SESSION['csrf_token']);
if (!verify_csrf_token($temp_token)) {
    echo "PASS: Verification fails when session token is missing.\n";
} else {
    echo "FAIL: Verification passed despite missing session token.\n";
    exit(1);
}

// Restore session token
$_SESSION['csrf_token'] = $temp_token;

echo "All CSRF tests passed.\n";
?>
