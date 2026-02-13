<?php
// Start session for CLI environment if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/test_helper.php';
require_once __DIR__ . '/../includes/functions.php';

echo "Running tests for includes/functions.php...\n\n";

// Test: Set a flash message with default type
run_test("setFlashMessage - Default Type", function() {
    // Clear session first just in case
    unset($_SESSION['flash']);

    setFlashMessage("test_key", "Test Message");

    if (isset($_SESSION['flash']['test_key'])) {
        echo "PASS: Flash key set in session\n";
        assertEquals("Test Message", $_SESSION['flash']['test_key']['message'], "Message content correct");
        assertEquals("info", $_SESSION['flash']['test_key']['type'], "Default type is 'info'");
    } else {
        echo "FAIL: Flash key not set in session\n";
    }
});

// Test: Set a flash message with custom type
run_test("setFlashMessage - Custom Type", function() {
    unset($_SESSION['flash']);

    setFlashMessage("success_key", "Success Message", "success");

    if (isset($_SESSION['flash']['success_key'])) {
        assertEquals("Success Message", $_SESSION['flash']['success_key']['message'], "Message content correct");
        assertEquals("success", $_SESSION['flash']['success_key']['type'], "Type is 'success'");
    } else {
        echo "FAIL: Flash key not set in session\n";
    }
});

// Test: Overwrite an existing flash message
run_test("setFlashMessage - Overwrite", function() {
    unset($_SESSION['flash']);

    setFlashMessage("overwrite_key", "Initial Message");
    setFlashMessage("overwrite_key", "New Message", "warning");

    if (isset($_SESSION['flash']['overwrite_key'])) {
        assertEquals("New Message", $_SESSION['flash']['overwrite_key']['message'], "Message updated");
        assertEquals("warning", $_SESSION['flash']['overwrite_key']['type'], "Type updated");
    } else {
        echo "FAIL: Flash key not set in session\n";
    }
});

// Test: getFlashMessage retrieves and clears
run_test("getFlashMessage - Retrieve and Clear", function() {
    unset($_SESSION['flash']);

    setFlashMessage("retrieve_key", "Retrieve Me", "danger");

    // Verify it's there first
    if (!isset($_SESSION['flash']['retrieve_key'])) {
        echo "FAIL: Setup failed, key not in session\n";
        return;
    }

    $output = getFlashMessage("retrieve_key");

    // Check output contains message and type
    if (strpos($output, "Retrieve Me") !== false) {
         echo "PASS: Output contains message text\n";
    } else {
         echo "FAIL: Output missing message text. Got: $output\n";
    }

    if (strpos($output, "alert-danger") !== false) {
         echo "PASS: Output contains correct alert class\n";
    } else {
         echo "FAIL: Output missing alert class. Got: $output\n";
    }

    // Check if cleared
    if (!isset($_SESSION['flash']['retrieve_key'])) {
        echo "PASS: Message cleared from session\n";
    } else {
        echo "FAIL: Message still in session\n";
    }
});
?>
