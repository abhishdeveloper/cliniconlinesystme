<?php
// tests/test_notifications.php

// Include the notifications file
require_once __DIR__ . '/../includes/notifications.php';

echo "=== Testing Notification System Refactoring ===\n";

// Helper to assert condition
function assertTrue($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

// 1. Test Global Functions (Integration)
echo "\nTesting Global Functions (Default Mock Config)...\n";

$resSMS = sendSMS('+1987654321', 'Global SMS Test');
assertTrue($resSMS === true, "sendSMS returned true");

$resEmail = sendEmail('global@example.com', 'Global Subject', 'Global Message');
assertTrue($resEmail === true, "sendEmail returned true");

// Test for potential function redeclaration issue
// In the original code, server_parse is inside sendEmail.
// If we were to bypass the mock return, calling sendEmail twice would define server_parse twice.
// However, currently the mock return happens *before* function definition, so server_parse is never defined.
// The refactor will move server_parse out, so it will be defined when the file is included.
// We can check if server_parse exists.

if (function_exists('server_parse')) {
    echo "\nPASS: server_parse function exists (Refactored behavior).\n";
} else {
    echo "\nNOTE: server_parse function does not exist (Current behavior, as it is nested and behind early return).\n";
}

echo "\n=== Test Complete ===\n";
