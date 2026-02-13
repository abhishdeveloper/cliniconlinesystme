<?php
// Database Configuration
$host = 'localhost';
$dbname = 'clinic_db';
$username = 'root';
$password = '';

try {
    if (getenv('DB_CONNECTION') === 'sqlite') {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../clinic.db');
    } else {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For production, log the error and show a generic message
    die("Database connection failed: " . $e->getMessage());
}
?>
