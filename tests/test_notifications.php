<?php
// Simple test script for notifications.php

// Include the notifications file
require_once __DIR__ . '/../includes/notifications.php';

echo "Testing sendSMS...\n";
$smsResult = sendSMS('+15551234567', 'Test SMS Body');
if ($smsResult) {
    echo "PASS: sendSMS returned true (Mock mode).\n";
} else {
    echo "FAIL: sendSMS returned false.\n";
    exit(1);
}

echo "\nTesting sendEmail...\n";
$emailResult = sendEmail('test@example.com', 'Test Subject', 'Test Message Body');
if ($emailResult) {
    echo "PASS: sendEmail returned true (Mock mode).\n";
} else {
    echo "FAIL: sendEmail returned false.\n";
    exit(1);
}

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

echo "\nAll tests completed.\n";
