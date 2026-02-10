<?php
/**
 * Test suite for Notifications (SMS and Email)
 */

require_once __DIR__ . '/../includes/notifications.php';

/**
 * Simple assertion function
 */
function assert_true($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
        exit(1);
    }
}

function assert_false($condition, $message) {
    assert_true(!$condition, $message);
}

// --- Email Tests ---

echo "Running Email Tests...\n";

// Test 1: Happy path (mock mode)
assert_true(
    sendEmail('patient@example.com', 'Appointment Confirmation', 'Your appointment is confirmed.'),
    "Email: Mock success with valid parameters"
);

// Test 2: Header injection in 'to'
assert_false(
    sendEmail("patient@example.com\r\nBcc: spy@example.com", 'Subject', 'Message'),
    "Email: Should fail with header injection in 'to'"
);

// Test 3: Header injection in 'subject'
assert_false(
    sendEmail('patient@example.com', "Subject\nBcc: spy@example.com", 'Message'),
    "Email: Should fail with header injection in 'subject'"
);


// --- SMS Tests ---

echo "\nRunning SMS Tests...\n";

// Test 4: Happy path (mock mode)
assert_true(
    sendSMS('+15551234567', 'Your appointment is confirmed.'),
    "SMS: Mock success with valid parameters"
);

// Test 5: Header injection in 'to'
assert_false(
    sendSMS("+15551234567\r\nSome: Header", 'Message'),
    "SMS: Should fail with header injection in 'to'"
);

echo "\nAll tests passed successfully!\n";
