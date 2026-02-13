<?php
require_once __DIR__ . '/test_helper.php';
require_once __DIR__ . '/../includes/functions.php';

echo "Testing getFlashMessage() in includes/functions.php...\n\n";

// Ensure clean slate
if (isset($_SESSION['flash'])) {
    unset($_SESSION['flash']);
}

// Test Case 1: Non-existent key
$result = getFlashMessage('non_existent_key');
assertNull($result, "getFlashMessage should return null for missing key");

// Test Case 2: Setting and retrieving a flash message
$testKey = 'test_key';
$testMessage = 'This is a test message';
$testType = 'success';

setFlashMessage($testKey, $testMessage, $testType);

// Verify session state before retrieval
assertTrue(isset($_SESSION['flash'][$testKey]), "Flash message should be in session before retrieval");
assertEquals($testMessage, $_SESSION['flash'][$testKey]['message'], "Session message should match set message");
assertEquals($testType, $_SESSION['flash'][$testKey]['type'], "Session type should match set type");

// Retrieve the message
$html = getFlashMessage($testKey);

// Test Case 3: Verify HTML output
assertStringContains($testMessage, $html, "Output HTML should contain the message text");
assertStringContains("alert-$testType", $html, "Output HTML should contain the alert type class");
assertStringContains("alert-dismissible", $html, "Output HTML should contain dismissible class");

// Test Case 4: Verify clearing from session
$resultAfter = getFlashMessage($testKey);
assertNull($resultAfter, "getFlashMessage should return null on second call (message cleared)");
assertTrue(!isset($_SESSION['flash'][$testKey]), "Flash key should be removed from session");

// Test Case 5: Default type handling
$defaultKey = 'default_key';
setFlashMessage($defaultKey, 'Default type message');
$defaultHtml = getFlashMessage($defaultKey);
assertStringContains('alert-info', $defaultHtml, "Default type should be 'info'");

// Test Case 6: Verify XSS Prevention
$xssKey = 'xss_key';
$xssMessage = '<script>alert("XSS")</script>';
setFlashMessage($xssKey, $xssMessage, 'danger');
$xssHtml = getFlashMessage($xssKey);

// Should NOT contain the raw script tag
assertStringContains('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $xssHtml, "Output HTML should escape special characters");
// Should NOT contain the unescaped script tag (simple check, though redundant if above passes)
if (strpos($xssHtml, '<script>') !== false) {
    echo "❌ FAIL: Output HTML contains unescaped script tag!\n";
    exit(1);
} else {
    echo "✅ PASS: Output HTML does not contain unescaped script tag\n";
}

echo "\nAll tests passed!\n";
?>
