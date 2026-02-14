<?php
// Test Session Security Configuration

// Mock HTTPS if provided in env
if (getenv('MOCK_HTTPS') === 'on') {
    $_SERVER['HTTPS'] = 'on';
}

require_once __DIR__ . '/../includes/functions.php';

echo "Testing session configuration...\n";

$params = session_get_cookie_params();
$failures = [];

// 1. Check HttpOnly
if ($params['httponly'] !== true) {
    $failures[] = "FAILURE: httponly is not set to true.";
} else {
    echo "SUCCESS: httponly is true.\n";
}

// 2. Check SameSite
if (strtolower($params['samesite']) !== 'strict') {
    $failures[] = "FAILURE: samesite is not set to Strict. Got: " . $params['samesite'];
} else {
    echo "SUCCESS: samesite is Strict.\n";
}

// 3. Check Strict Mode
if (ini_get('session.use_strict_mode') != 1) {
    $failures[] = "FAILURE: session.use_strict_mode is not enabled.";
} else {
    echo "SUCCESS: session.use_strict_mode is enabled.\n";
}

// 4. Check Secure Flag
$expected_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($params['secure'] !== $expected_secure) {
    $failures[] = "FAILURE: secure flag mismatch. Expected " . ($expected_secure ? 'true' : 'false') . ", got " . ($params['secure'] ? 'true' : 'false');
} else {
    echo "SUCCESS: secure flag is " . ($params['secure'] ? 'true' : 'false') . " (Expected).\n";
}

// 5. Test Header Inclusion
// This verifies that including header.php after functions.php doesn't cause issues
try {
    ob_start(); // Buffer output to avoid clutter
    require_once __DIR__ . '/../includes/header.php';
    ob_end_clean();
    echo "SUCCESS: header.php included without error.\n";
} catch (Throwable $e) {
    $failures[] = "FAILURE: Including header.php caused an error: " . $e->getMessage();
}

if (!empty($failures)) {
    echo "\nTest Failed with " . count($failures) . " errors.\n";
    exit(1);
}

echo "\nAll checks passed successfully.\n";
exit(0);
