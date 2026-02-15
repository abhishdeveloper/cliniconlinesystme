<?php
// Mock session environment
if (session_status() === PHP_SESSION_NONE) {
    // Start session if not started.
    // In CLI, we need to ensure we don't have issues with headers sent.
    @session_start();
}

// Include functions
require_once __DIR__ . '/../includes/functions.php';

echo "--------------------------------------------------\n";
echo "Running CSRF Implementation Tests\n";
echo "--------------------------------------------------\n";

$failed = false;

function assertTest($condition, $message) {
    global $failed;
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
        $failed = true;
    }
}

// Test 1: Generate Token
$token = generate_csrf_token();
assertTest(!empty($token), "Token should be generated.");
assertTest(isset($_SESSION['csrf_token']), "Token should be in session.");
assertTest($_SESSION['csrf_token'] === $token, "Returned token matches session.");

// Test 2: Verify Valid Token
assertTest(verify_csrf_token($token), "Valid token should be verified.");

// Test 3: Verify Invalid Token
assertTest(!verify_csrf_token("invalid_token"), "Invalid token should be rejected.");
assertTest(!verify_csrf_token(""), "Empty token should be rejected.");
assertTest(!verify_csrf_token(null), "Null token should be rejected.");

// Test 4: Token Persistence
$token2 = generate_csrf_token();
assertTest($token === $token2, "Token should persist in session.");

// Test 5: CSRF Input Generation
$input = csrf_input();
assertTest(strpos($input, 'type="hidden"') !== false, "Input should be hidden.");
assertTest(strpos($input, 'name="csrf_token"') !== false, "Input name should be csrf_token.");
assertTest(strpos($input, $token) !== false, "Input value should contain the token.");

if ($failed) {
    echo "\nSome tests failed.\n";
    exit(1);
} else {
    echo "\nAll CSRF tests passed successfully.\n";
    exit(0);
}
?>
