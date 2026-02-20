<?php
// Database Configuration
$host = 'localhost';
$dbname = 'clinic_db';
$username = 'root';
$password = '';

// Check if we are in a testing environment or if SQLite is preferred
$use_sqlite = true; // Set to true for local development without MySQL

if ($use_sqlite) {
    try {
        $db_path = __DIR__ . '/../clinic.db';
        $pdo = new PDO("sqlite:$db_path");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable foreign keys for SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");

    } catch (PDOException $e) {
        die("SQLite connection failed: " . $e->getMessage());
    }
} else {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
