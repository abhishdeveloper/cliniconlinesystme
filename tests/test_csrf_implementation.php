<?php
// Mock session for CLI environment if not running in web server
if (php_sapi_name() === 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        // Simple array simulation for session in CLI
        $_SESSION = [];
    }
}

require_once 'includes/functions.php';

echo "Testing CSRF Implementation...\n";

// 1. Test Token Generation
echo "1. Testing Token Generation: ";
$token1 = generate_csrf_token();
if (!empty($token1) && strlen($token1) === 64) { // bin2hex(32 bytes) = 64 chars
    echo "PASS (Token: " . substr($token1, 0, 10) . "...)\n";
} else {
    echo "FAIL (Token invalid or wrong length: " . strlen($token1) . ")\n";
    exit(1);
}

// 2. Test Token Persistence
echo "2. Testing Token Persistence: ";
$token2 = generate_csrf_token();
if ($token1 === $token2) {
    echo "PASS (Token persists)\n";
} else {
    echo "FAIL (Token changed unexpectedly)\n";
    exit(1);
}

// 3. Test Verification (Valid)
echo "3. Testing Verification (Valid): ";
if (verify_csrf_token($token1)) {
    echo "PASS\n";
} else {
    echo "FAIL\n";
    exit(1);
}

// 4. Test Verification (Invalid)
echo "4. Testing Verification (Invalid): ";
if (!verify_csrf_token("invalid_token_123")) {
    echo "PASS\n";
} else {
    echo "FAIL (Invalid token accepted)\n";
    exit(1);
}

// 5. Test Verification (Empty)
echo "5. Testing Verification (Empty): ";
if (!verify_csrf_token("")) {
    echo "PASS\n";
} else {
    echo "FAIL (Empty token accepted)\n";
    exit(1);
}

echo "\nAll CSRF tests passed successfully.\n";
?>
