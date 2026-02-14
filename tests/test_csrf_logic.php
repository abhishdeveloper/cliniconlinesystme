<?php
// Mock session if needed, but let's try to let functions.php handle it.
// To avoid headers already sent warning in CLI if we echo something before require, we'll capture output.
ob_start();
require_once __DIR__ . '/../includes/functions.php';
ob_end_clean();

echo "Testing CSRF Functions...\n";

// 1. Test Generation
$token = generate_csrf_token();
if (!empty($token) && is_string($token)) {
    echo "PASS: Token generated: " . substr($token, 0, 10) . "...\n";
} else {
    echo "FAIL: Token generation failed.\n";
    exit(1);
}

// 2. Test Persistence
$token2 = generate_csrf_token();
if ($token === $token2) {
    echo "PASS: Token persists in session.\n";
} else {
    echo "FAIL: Token regenerated unexpectedly.\n";
}

// 3. Test Verification (Valid)
if (verify_csrf_token($token)) {
    echo "PASS: Valid token verified.\n";
} else {
    echo "FAIL: Valid token rejected.\n";
}

// 4. Test Verification (Invalid)
if (!verify_csrf_token("invalid_token_string")) {
    echo "PASS: Invalid token rejected.\n";
} else {
    echo "FAIL: Invalid token accepted.\n";
}

// 5. Test Verification (Empty)
if (!verify_csrf_token("")) {
    echo "PASS: Empty token rejected.\n";
} else {
    echo "FAIL: Empty token accepted.\n";
}

echo "CSRF Logic Tests Completed.\n";
?>
