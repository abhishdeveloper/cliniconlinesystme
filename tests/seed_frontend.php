<?php
// scripts/seed_frontend_test.php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=frontend_test.db');

require_once __DIR__ . '/../config/database.php';

try {
    // Setup Tables
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT DEFAULT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'patient',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("DROP TABLE IF EXISTS medicines");
    $pdo->exec("CREATE TABLE medicines (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'Powder',
        default_dosage TEXT DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create Admin
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@example.com', $password, 'admin']);

    // Create Medicines
    $stmt = $pdo->prepare("INSERT INTO medicines (name, type, default_dosage, description) VALUES (?, ?, ?, ?)");
    for ($i = 1; $i <= 25; $i++) {
        $stmt->execute([
            "Medicine " . str_pad($i, 2, '0', STR_PAD_LEFT),
            'Powder',
            "Dosage $i",
            "Description $i"
        ]);
    }

    echo "Frontend test database seeded.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
