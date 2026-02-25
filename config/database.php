<?php
// Load environment variables from .env file if it exists
(function() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Basic quote handling
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                if (getenv($name) === false) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
})();

// Determine connection type
// Default to MySQL for shared hosting if not specified
$connection = getenv('DB_CONNECTION') ?: 'mysql';

if ($connection === 'sqlite') {
    // SQLite Configuration
    try {
        $db_path = __DIR__ . '/../clinic.db';
        $pdo = new PDO("sqlite:$db_path");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable foreign keys for SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");

        // Create tables if not exist (Simulate migration)
        // This is a quick fix to ensure DB exists. In production, use migration scripts.
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT DEFAULT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'patient', -- ENUM simulation
                specialty TEXT DEFAULT NULL, -- Added column
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

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

        // Check if admin exists, if not seed
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
        }

        // Create other tables...
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
            CREATE INDEX IF NOT EXISTS idx_appointments_doctor_date ON appointments (doctor_id, appointment_date);
            CREATE INDEX IF NOT EXISTS idx_appointments_patient_date ON appointments (patient_id, appointment_date);
        ");

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


    } catch (PDOException $e) {
        die("SQLite connection failed: " . $e->getMessage());
    }
} else {
    // MySQL Configuration
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'clinic_db';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';

    // Allow skipping connection for testing purposes
    if (getenv('DB_TEST_NO_CONNECT')) {
        return;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // For production, log the error and show a generic message
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}
?>
