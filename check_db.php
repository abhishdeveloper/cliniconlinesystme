<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection successful.\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
