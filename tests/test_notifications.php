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

// 1. Test Mock SMS Provider
if (class_exists('MockSMSProvider')) {
    echo "\nTesting MockSMSProvider...\n";
    $sms = new MockSMSProvider();
    $result = $sms->send('+1234567890', 'Test SMS Body');
    assertTrue($result === true, "MockSMSProvider returned true");
} else {
    echo "\n[SKIP] MockSMSProvider class not found (not refactored yet?)\n";
}

// 2. Test Mock Email Provider
if (class_exists('MockEmailProvider')) {
    echo "\nTesting MockEmailProvider...\n";
    $email = new MockEmailProvider();
    $result = $email->send('test@example.com', 'Test Subject', 'Test Body');
    assertTrue($result === true, "MockEmailProvider returned true");
} else {
    echo "\n[SKIP] MockEmailProvider class not found (not refactored yet?)\n";
}

// 3. Test SMTP Email Provider (Crash Fix)
if (class_exists('SMTPEmailProvider')) {
    echo "\nTesting SMTPEmailProvider (Crash Verification)...\n";
    // Using invalid host to fail fast, but checking for "redeclare" crash
    $smtp = new SMTPEmailProvider('smtp.invalid', 587, 'user', 'pass', 'noreply@clinic.com', 'Clinic System');

    // First call
    echo "Calling send() 1st time...\n";
    $res1 = $smtp->send('test1@example.com', 'Sub 1', 'Msg 1');
    echo "Result 1: " . ($res1 ? 'true' : 'false') . "\n";

    // Second call - should NOT crash
    echo "Calling send() 2nd time...\n";
    $res2 = $smtp->send('test2@example.com', 'Sub 2', 'Msg 2');
    echo "Result 2: " . ($res2 ? 'true' : 'false') . "\n";

    assertTrue(true, "SMTPEmailProvider called twice without crashing");
} else {
    echo "\n[SKIP] SMTPEmailProvider class not found (not refactored yet?)\n";
}

// 4. Test Global Functions (Integration)
echo "\nTesting Global Functions (Default Mock Config)...\n";
// Capturing output to verify logging
ob_start();
$resSMS = sendSMS('+1987654321', 'Global SMS Test');
$outputSMS = ob_get_clean(); // sendSMS writes to error_log, not stdout, so we can't easily capture it here without setting error_log handler.
// However, since we are in CLI, error_log typically goes to stderr. We'll just check return values.

assertTrue($resSMS === true, "sendSMS returned true");

$resEmail = sendEmail('global@example.com', 'Global Subject', 'Global Message');
assertTrue($resEmail === true, "sendEmail returned true");

echo "\n=== Test Complete ===\n";
