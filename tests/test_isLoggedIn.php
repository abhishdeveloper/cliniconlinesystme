<?php
// tests/test_isLoggedIn.php

// Suppress header warnings since we are running in CLI
// and session_start might try to send headers.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock session start if needed, but standard PHP CLI handles it usually.
// If session_start() is called in functions.php, it will try to start a session.
// We should ensure we don't output anything before requiring the file if we want to be strict,
// but for CLI tests, output before headers is less critical unless it crashes.

require_once __DIR__ . '/../includes/functions.php';

$passed = 0;
$failed = 0;

function assert_test($name, $condition) {
    global $passed, $failed;
    echo "Test: $name ... ";
    if ($condition) {
        echo "\033[32mPASS\033[0m\n";
        $passed++;
    } else {
        echo "\033[31mFAIL\033[0m\n";
        $failed++;
    }
}

echo "Starting tests for isLoggedIn()...\n";

// 1. Not logged in
$_SESSION = []; // Clear session
assert_test('Not logged in (returns false)', isLoggedIn() === false);

// 2. Logged in, no role check
$_SESSION = ['user_id' => 123];
assert_test('Logged in, no role check (returns true)', isLoggedIn() === true);

// 3. Logged in, correct role
$_SESSION = ['user_id' => 123, 'role' => 'admin'];
assert_test('Logged in, correct role (returns true)', isLoggedIn('admin') === true);

// 4. Logged in, incorrect role
$_SESSION = ['user_id' => 123, 'role' => 'doctor'];
assert_test('Logged in, incorrect role (returns false)', isLoggedIn('admin') === false);

// 5. Logged in, role check requested but 'role' key missing
// This tests behavior when user_id is set but role is not.
$_SESSION = ['user_id' => 123];
// We expect false because role doesn't match, but we also want to ensure no notices/warnings.
// The current implementation accesses $_SESSION['role'] directly, which might cause a Notice.
// We will catch output to check for notices.
ob_start();
$result = isLoggedIn('admin');
$output = ob_get_clean();

assert_test('Logged in, role missing in session (returns false)', $result === false);

if (!empty($output)) {
    echo "\033[33mWARNING: Output during test (likely Notice/Warning):\033[0m\n$output\n";
}

echo "\nSummary: $passed passed, $failed failed.\n";

if ($failed > 0) {
    exit(1);
}
