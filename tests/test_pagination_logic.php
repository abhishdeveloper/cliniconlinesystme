<?php
// tests/test_pagination_logic.php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=test_pagination.db');

// Start session and mock user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';

// Mock $_SERVER['REQUEST_METHOD']
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../config/database.php';

try {
    // Setup Table
    $pdo->exec("DROP TABLE IF EXISTS medicines");
    $pdo->exec("CREATE TABLE medicines (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'Powder',
        default_dosage TEXT DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Populate 25 rows
    $stmt = $pdo->prepare("INSERT INTO medicines (name, type, default_dosage, description) VALUES (?, ?, ?, ?)");
    for ($i = 1; $i <= 25; $i++) {
        $stmt->execute([
            "Medicine " . str_pad($i, 2, '0', STR_PAD_LEFT),
            'Powder',
            "Dosage $i",
            "Description $i"
        ]);
    }

    echo "Testing Pagination Logic...\n";

    // Test Page 1
    $_GET['page'] = 1;
    ob_start();
    include __DIR__ . '/../admin/medicines.php';
    $output = ob_get_clean();

    // Verify Page 1 content
    if (strpos($output, 'Medicine 01') !== false && strpos($output, 'Medicine 10') !== false) {
        echo "PASS: Page 1 contains Medicines 1-10.\n";
    } else {
        echo "FAIL: Page 1 does not contain expected medicines.\n";
    }
    if (strpos($output, 'Medicine 11') === false) {
        echo "PASS: Page 1 does not contain Medicine 11.\n";
    } else {
        echo "FAIL: Page 1 contains Medicine 11.\n";
    }

    // Test Page 2
    $_GET['page'] = 2;
    ob_start();
    include __DIR__ . '/../admin/medicines.php';
    $output = ob_get_clean();

    if (strpos($output, 'Medicine 11') !== false && strpos($output, 'Medicine 20') !== false) {
        echo "PASS: Page 2 contains Medicines 11-20.\n";
    } else {
        echo "FAIL: Page 2 does not contain expected medicines.\n";
    }

    // Test Page 3
    $_GET['page'] = 3;
    ob_start();
    include __DIR__ . '/../admin/medicines.php';
    $output = ob_get_clean();

    if (strpos($output, 'Medicine 21') !== false && strpos($output, 'Medicine 25') !== false) {
        echo "PASS: Page 3 contains Medicines 21-25.\n";
    } else {
        echo "FAIL: Page 3 does not contain expected medicines.\n";
    }

    // Test Pagination Controls
    if (strpos($output, '<a class="page-link" href="?page=2">2</a>') !== false) {
        echo "PASS: Pagination controls found.\n";
    } else {
        echo "FAIL: Pagination controls NOT found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Cleanup
if (file_exists('test_pagination.db')) {
    unlink('test_pagination.db');
}
?>
