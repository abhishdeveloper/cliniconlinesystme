<?php
require_once __DIR__ . '/../config/database.php';

try {
    echo "Verifying database schema...\n";

    // Check if we are using SQLite
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver !== 'sqlite') {
        echo "SKIP: Not using SQLite (Driver: $driver)\n";
        exit(0);
    }

    $tables = ['users', 'medicines', 'doctor_schedules', 'appointment_slots', 'appointments', 'prescriptions'];
    $missing_tables = [];

    // Check for tables
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (!in_array($table, $existing_tables)) {
            $missing_tables[] = $table;
        }
    }

    if (!empty($missing_tables)) {
        echo "FAIL: Missing tables: " . implode(', ', $missing_tables) . "\n";
        exit(1);
    } else {
        echo "PASS: All expected tables exist.\n";
    }

    // Check for users
    $stmt = $pdo->query("SELECT count(*) FROM users");
    $count = $stmt->fetchColumn();
    if ($count < 3) {
        echo "FAIL: Expected at least 3 users (seeded), found $count.\n";
        // Note: If this fails, it might be because the DB was empty and migration.php hasn't been run yet.
        exit(1);
    } else {
        echo "PASS: Found $count users.\n";
    }

    // Check for specialty column
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('specialty', $columns)) {
        echo "FAIL: Missing 'specialty' column in users table.\n";
        exit(1);
    } else {
         echo "PASS: 'specialty' column exists in users table.\n";
    }

    echo "SUCCESS: Database verification passed.\n";

} catch (PDOException $e) {
    echo "ERROR: Database verification failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
