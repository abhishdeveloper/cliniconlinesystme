<?php
// Ensure we don't have output before headers if session_start sends headers (CLI usually doesn't, but good practice)
// In CLI, session_start() works but doesn't send headers.

// Include functions. It will start session if not started.
require_once __DIR__ . '/../includes/functions.php';

echo "Testing CSRF Protection...\n";

// 1. Generate Token
$token = generate_csrf_token();
echo "Generated Token: " . substr($token, 0, 10) . "...\n";

if (!empty($token) && isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
    echo "PASS: Token generated and stored in session.\n";
} else {
    echo "FAIL: Token generation failed.\n";
    exit(1);
}

// 2. Verify Valid Token
if (verify_csrf_token($token)) {
    echo "PASS: Valid token verified successfully.\n";
} else {
    echo "FAIL: Valid token verification failed.\n";
    exit(1);
}

// 3. Verify Invalid Token
if (!verify_csrf_token("invalid_token_123")) {
    echo "PASS: Invalid token rejected.\n";
} else {
    echo "FAIL: Invalid token accepted.\n";
    exit(1);
}

// 4. Verify Empty Token
if (!verify_csrf_token("")) {
    echo "PASS: Empty token rejected.\n";
} else {
    echo "FAIL: Empty token accepted.\n";
    exit(1);
}

// 5. Verify reused token (should still work until regenerated)
if (verify_csrf_token($token)) {
    echo "PASS: Token reuse works (per session).\n";
} else {
    echo "FAIL: Token reuse failed.\n";
}

echo "All CSRF tests passed!\n";
?>
