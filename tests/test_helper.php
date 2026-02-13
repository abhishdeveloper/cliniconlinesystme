<?php
/**
 * Simple test helper functions for PHP assertions.
 */

function assertTrue($condition, $message = '') {
    if ($condition === true) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
        exit(1);
    }
}

function assertEquals($expected, $actual, $message = '') {
    if ($expected === $actual) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
        echo "   Expected: " . var_export($expected, true) . "\n";
        echo "   Actual:   " . var_export($actual, true) . "\n";
        exit(1);
    }
}

function assertStringContains($needle, $haystack, $message = '') {
    if (strpos($haystack, $needle) !== false) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
        echo "   Expected to find: " . var_export($needle, true) . "\n";
        echo "   In string:        " . var_export($haystack, true) . "\n";
        exit(1);
    }
}

function assertNull($value, $message = '') {
    if ($value === null) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
        echo "   Expected: NULL\n";
        echo "   Actual:   " . var_export($value, true) . "\n";
        exit(1);
    }
}

// Ensure session exists for testing functions dependent on $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
