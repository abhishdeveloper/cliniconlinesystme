<?php
// Mocking session for CLI environment if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];

require_once __DIR__ . '/../includes/functions.php';

echo "Running CSRF Implementation Tests...\n";

// Test 1: Generate Token
echo "Testing token generation...\n";
$token = generate_csrf_token();
if (empty($token)) {
    echo "❌ Test 1 Failed: Token is empty.\n";
    exit(1);
}
if ($token !== $_SESSION['csrf_token']) {
    echo "❌ Test 1 Failed: Token not stored in session.\n";
    exit(1);
}
echo "✅ Test 1 Passed: Token generated and stored.\n";

// Test 2: Verify Token (Success)
echo "Testing token verification (success case)...\n";
if (!verify_csrf_token($token)) {
    echo "❌ Test 2 Failed: Valid token verification failed.\n";
    exit(1);
}
echo "✅ Test 2 Passed: Valid token verified.\n";

// Test 3: Verify Token (Failure)
echo "Testing token verification (failure case)...\n";
if (verify_csrf_token('invalid_token')) {
    echo "❌ Test 3 Failed: Invalid token verification succeeded.\n";
    exit(1);
}
echo "✅ Test 3 Passed: Invalid token rejected.\n";

// Test 4: Check Login Page for Token Field
echo "Checking login.php for CSRF implementation...\n";
$login_content = file_get_contents(__DIR__ . '/../login.php');
if (strpos($login_content, 'generate_csrf_token()') !== false &&
    strpos($login_content, 'verify_csrf_token') !== false) {
    echo "✅ Test 4 Passed: login.php contains CSRF function calls.\n";
} else {
    echo "❌ Test 4 Failed: login.php missing CSRF function calls.\n";
    exit(1);
}

// Test 5: Check Profile Page for Token Field
echo "Checking profile.php for CSRF implementation...\n";
$profile_content = file_get_contents(__DIR__ . '/../profile.php');
if (strpos($profile_content, 'generate_csrf_token()') !== false &&
    strpos($profile_content, 'verify_csrf_token') !== false) {
    echo "✅ Test 5 Passed: profile.php contains CSRF function calls.\n";
} else {
    echo "❌ Test 5 Failed: profile.php missing CSRF function calls.\n";
    exit(1);
}

echo "\nAll CSRF tests passed! 🛡️\n";
