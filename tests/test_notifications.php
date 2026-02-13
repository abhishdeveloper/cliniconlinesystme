<?php
require_once __DIR__ . '/test_framework.php';
require_once __DIR__ . '/../includes/notifications.php';

echo "Testing sendSMS()...\n";

runTest('Test sendSMS Mock Mode Success', function() {
    $to = '+15551234567';
    $body = 'This is a test message from the test suite.';

    // In the default state, SID is 'YOUR_TWILIO_SID', so it should log and return true.
    $result = sendSMS($to, $body);

    assertTrue($result, 'sendSMS should return true when using default mock credentials');
});

// Since we can't easily change the internal variables of the function without reflection or modifying the code,
// and the mock check happens at the very beginning, we can verify that it handles inputs gracefully.

runTest('Test sendSMS Mock Mode with Special Characters', function() {
    $to = '+15559876543';
    $body = 'Special chars: !@#$%^&*()_+{}|:"<>?';

    $result = sendSMS($to, $body);

    assertTrue($result, 'sendSMS should handle special characters in body and return true in mock mode');
});

echo "All Notification tests completed.\n";
