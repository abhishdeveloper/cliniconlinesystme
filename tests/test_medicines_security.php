<?php
// Run this from the 'admin' directory!
if (basename(getcwd()) !== 'admin') {
    die("Please run this script from the 'admin' directory: cd admin && php ../tests/verify_fix.php\n");
}

define('TEST_MODE', true);
define('TEST_DB_PATH', dirname(__DIR__) . '/tests/test_db_verify.sqlite');

// Init DB and Functions at Global Scope
// We need to suppress output from header/functions if any
ob_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
ob_end_clean();

function reset_db($pdo) {
    $pdo->exec("DROP TABLE IF EXISTS medicines");
    $pdo->exec("CREATE TABLE medicines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            type TEXT,
            default_dosage TEXT,
            description TEXT
        )");
    $pdo->exec("INSERT INTO medicines (name, type, default_dosage, description) VALUES ('TestMed', 'Tablet', '1mg', 'Test Description')");
}

function run_test($desc, $setup_fn, $check_fn) {
    global $pdo; // Use the global connection

    echo "Running Test: $desc ... ";
    reset_db($pdo);

    // Reset Session
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [
        'user_id' => 1,
        'role' => 'admin',
        'user_name' => 'Admin',
        'flash' => []
    ];

    // Setup Request
    $setup_fn();

    // Run Script
    ob_start();
    try {
        // We include medicines.php. It inherits current scope.
        // It sees $pdo.
        // It skips require_once '../config/database.php'.
        require 'medicines.php';
    } catch (Exception $e) {
        // unexpected
        echo "Exception: " . $e->getMessage();
    }
    ob_end_clean();

    // Check Result
    if ($check_fn($pdo)) {
        echo "PASS\n";
    } else {
        echo "FAIL\n";
        exit(1);
    }
}

// Test 1: GET Delete should be ignored (or fail securely)
run_test("GET Delete (Old Vulnerability)", function() {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['delete'] = 1;
    $_POST = [];
}, function($pdo) {
    // Should still exist
    $count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE id = 1")->fetchColumn();
    return $count == 1;
});

// Test 2: POST Delete without Token
run_test("POST Delete without CSRF Token", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['action' => 'delete_medicine', 'id' => 1];
    $_GET = [];
}, function($pdo) {
    // Should still exist
    $count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE id = 1")->fetchColumn();
    return $count == 1;
});

// Test 3: POST Delete with Valid Token
run_test("POST Delete with Valid CSRF Token", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    // Generate token
    $token = generate_csrf_token();
    $_POST = ['action' => 'delete_medicine', 'id' => 1, 'csrf_token' => $token];
    $_GET = [];
}, function($pdo) {
    // Should be deleted
    $count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE id = 1")->fetchColumn();
    return $count == 0;
});

// Test 4: POST Add with Valid Token
run_test("POST Add with Valid CSRF Token", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $token = generate_csrf_token();
    $_POST = [
        'action' => 'add_medicine',
        'name' => 'NewMed',
        'type' => 'Syrup',
        'default_dosage' => '10ml',
        'description' => 'New Desc',
        'csrf_token' => $token
    ];
    $_GET = [];
}, function($pdo) {
    // Should exist
    $count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE name = 'NewMed'")->fetchColumn();
    return $count == 1;
});

// Test 5: POST Add without Token
run_test("POST Add without CSRF Token", function() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'action' => 'add_medicine',
        'name' => 'BadMed',
        'type' => 'Syrup',
        'default_dosage' => '10ml',
        'description' => 'New Desc'
    ];
    $_GET = [];
}, function($pdo) {
    // Should NOT exist
    $count = $pdo->query("SELECT COUNT(*) FROM medicines WHERE name = 'BadMed'")->fetchColumn();
    return $count == 0;
});

echo "All tests passed!\n";
?>
