<?php
// Mock necessary server variables
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/login.php';

// Capture output
ob_start();
try {
    // Correctly reference login.php from tests/ directory
    require __DIR__ . '/../login.php';
} catch (Exception $e) {
    // Ignore execution stoppage if any
}
$output = ob_get_clean();

// Check for hidden input
if (strpos($output, 'name="csrf_token"') !== false) {
    echo "PASS: CSRF token field found in login form.\n";
} else {
    echo "FAIL: CSRF token field NOT found in login form.\n";
    // Check if output is empty
    if (empty($output)) {
        echo "Output was empty.\n";
    } else {
        echo "Output snippet: " . substr($output, 0, 500) . "\n";
    }
    exit(1);
}
?>
