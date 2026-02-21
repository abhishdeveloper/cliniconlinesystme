<?php
require_once 'config/database.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        echo "Running SQLite migrations...\n";

        // Create tables if not exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT DEFAULT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'patient',
                specialty TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        echo "Table 'users' verified.\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS medicines (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT,
                default_dosage TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        echo "Table 'medicines' verified.\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS doctor_schedules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                doctor_id INTEGER NOT NULL,
                day_of_week TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                slot_duration INTEGER DEFAULT 30,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ");
        echo "Table 'doctor_schedules' verified.\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS appointment_slots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                doctor_id INTEGER NOT NULL,
                slot_datetime TEXT NOT NULL,
                is_booked INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE (doctor_id, slot_datetime)
            );
        ");
        echo "Table 'appointment_slots' verified.\n";

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                slot_id INTEGER DEFAULT NULL,
                appointment_date TEXT NOT NULL,
                status TEXT DEFAULT 'pending',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (slot_id) REFERENCES appointment_slots(id) ON DELETE SET NULL
            );
        ");
        echo "Table 'appointments' verified.\n";

        $pdo->exec("
             CREATE TABLE IF NOT EXISTS prescriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                diagnosis TEXT,
                medicines_json TEXT,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id),
                FOREIGN KEY (patient_id) REFERENCES users(id),
                FOREIGN KEY (doctor_id) REFERENCES users(id)
            );
        ");
        echo "Table 'prescriptions' verified.\n";

        // Check for 'specialty' column (migration for existing DBs)
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('specialty', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty TEXT DEFAULT NULL");
            echo "Column 'specialty' added to SQLite database.\n";
        } else {
            echo "Column 'specialty' already exists in SQLite database.\n";
        }

        // Seed Data
        $stmt = $pdo->query("SELECT count(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            // Seed Admin
            $pdo->exec("INSERT INTO users (name, email, phone, password, role) VALUES
                ('Super Admin', 'admin@clinic.com', '+15550000000', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin')");
            // Seed Doctor
            $pdo->exec("INSERT INTO users (name, email, phone, password, role, specialty) VALUES
                ('Dr. Smith', 'doctor@clinic.com', '+15551234567', '" . password_hash('doctor123', PASSWORD_DEFAULT) . "', 'doctor', 'General Physician')");
            // Seed Patient
            $pdo->exec("INSERT INTO users (name, email, phone, password, role) VALUES
                ('John Doe', 'patient@clinic.com', '+15559876543', '" . password_hash('patient123', PASSWORD_DEFAULT) . "', 'patient')");
            echo "Default users seeded.\n";
        } else {
            echo "Users table already has data, skipping seed.\n";
        }

    } else {
        // MySQL
        echo "Running MySQL migrations...\n";
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'specialty'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "Column 'specialty' already exists in MySQL database.\n";
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty VARCHAR(100) DEFAULT NULL AFTER role");
            echo "Column 'specialty' added to MySQL database.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
