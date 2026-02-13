<?php
// Capture output buffer to prevent session_start from outputting headers or errors
ob_start();
require_once __DIR__ . '/../includes/functions.php';
ob_end_clean();

require_once __DIR__ . '/Assertions.php';

echo "Running tests for includes/functions.php...\n";

// Test Case 1: Standard String
$input = "Hello World";
$expected = "Hello World";
Assertions::assertEquals($expected, escape($input), "Standard string should not change");

// Test Case 2: HTML Special Characters
$input = "<h1>Title</h1>";
$expected = "&lt;h1&gt;Title&lt;/h1&gt;";
Assertions::assertEquals($expected, escape($input), "HTML tags should be escaped");

// Test Case 3: Quotes (Double and Single)
$input = "O'Reilly \"The Book\"";
$expected = "O&#039;Reilly &quot;The Book&quot;";
Assertions::assertEquals($expected, escape($input), "Quotes should be escaped (ENT_QUOTES)");

// Test Case 4: Ampersand
$input = "Tom & Jerry";
$expected = "Tom &amp; Jerry";
Assertions::assertEquals($expected, escape($input), "Ampersand should be escaped");

// Test Case 5: Empty String
$input = "";
$expected = "";
Assertions::assertEquals($expected, escape($input), "Empty string should remain empty");

// Test Case 6: Mixed Content
$input = "<script>alert('XSS')</script>";
$expected = "&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;";
Assertions::assertEquals($expected, escape($input), "Script tags and quotes should be escaped");

// Final Report
Assertions::report();
?>
