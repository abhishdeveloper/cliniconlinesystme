<?php
// Database Configuration
// Switched to SQLite as per environment constraints
$db_path = dirname(__DIR__) . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
