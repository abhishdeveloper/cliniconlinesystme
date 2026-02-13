<?php
// Database Configuration

$db_connection = getenv('DB_CONNECTION') ?: 'mysql';

if ($db_connection === 'sqlite') {
    $db_path = __DIR__ . '/../database.sqlite';
    try {
        $pdo = new PDO("sqlite:$db_path");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable foreign keys
        $pdo->exec("PRAGMA foreign_keys = ON;");

    } catch (PDOException $e) {
        die("SQLite Connection failed: " . $e->getMessage());
    }
} else {
    $host = '127.0.0.1';
    $dbname = 'clinic_db';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // For production, log the error and show a generic message
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
