<?php
// Mock environment
$_SESSION = [];
// Include the notifications file
require_once __DIR__ . '/../includes/notifications.php';

echo "Testing sendEmail function...\n";

// Test 1: First call
$result1 = sendEmail('test1@example.com', 'Test Subject 1', 'Test Body 1');
if ($result1) {
    echo "First email sent successfully (mocked).\n";
} else {
    echo "First email failed.\n";
}

// Test 2: Second call (this would crash if function redeclaration occurred)
try {
    $result2 = sendEmail('test2@example.com', 'Test Subject 2', 'Test Body 2');
    if ($result2) {
        echo "Second email sent successfully (mocked).\n";
    } else {
        echo "Second email failed.\n";
    }
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test passed!\n";
?>
