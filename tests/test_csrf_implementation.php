<?php
// tests/test_csrf_implementation.php

// Ensure session starts for testing if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the functions file
require_once __DIR__ . '/../includes/functions.php';

echo "Testing CSRF Implementation...\n";

// Check if functions exist
if (!function_exists('generate_csrf_token') || !function_exists('verify_csrf_token')) {
    echo "FAIL: CSRF functions not implemented yet.\n";
    exit(1);
}

// 1. Test Generation
$token = generate_csrf_token();
if (!empty($token) && strlen($token) === 64) { // 32 bytes = 64 hex chars
    echo "PASS: Token generated successfully.\n";
} else {
    echo "FAIL: Token generation failed.\n";
    exit(1);
}

// 2. Test Session Storage
if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
    echo "PASS: Token stored in session.\n";
} else {
    echo "FAIL: Token not found in session.\n";
    exit(1);
}

// 3. Test Verification (Success)
if (verify_csrf_token($token)) {
    echo "PASS: Token verification succeeded with correct token.\n";
} else {
    echo "FAIL: Token verification failed with correct token.\n";
    exit(1);
}

// 4. Test Verification (Failure - Wrong Token)
if (!verify_csrf_token('wrongtoken')) {
    echo "PASS: Token verification correctly rejected wrong token.\n";
} else {
    echo "FAIL: Token verification accepted wrong token.\n";
    exit(1);
}

// 5. Test Verification (Failure - Empty Token)
if (!verify_csrf_token('')) {
    echo "PASS: Token verification correctly rejected empty token.\n";
} else {
    echo "FAIL: Token verification accepted empty token.\n";
    exit(1);
}

echo "All CSRF tests passed!\n";
?>
