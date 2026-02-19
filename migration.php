<?php
require_once 'config/database.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // 1. Add specialty column (Existing logic)
    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('specialty', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty TEXT DEFAULT NULL");
            echo "Column 'specialty' added to SQLite database.\n";
        } else {
            echo "Column 'specialty' already exists in SQLite database.\n";
        }

        // 2. Add Indexes for SQLite
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_appointments_doctor_date ON appointments (doctor_id, appointment_date)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_appointments_patient_date ON appointments (patient_id, appointment_date)");
        echo "Indexes for appointments table ensured in SQLite.\n";

    } else {
        // MySQL - Specialty Column
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'specialty'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "Column 'specialty' already exists in MySQL database.\n";
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty VARCHAR(100) DEFAULT NULL AFTER role");
            echo "Column 'specialty' added to MySQL database.\n";
        }

        // MySQL - Indexes
        $indexes = [
            'idx_appointments_doctor_date' => '(doctor_id, appointment_date)',
            'idx_appointments_patient_date' => '(patient_id, appointment_date)'
        ];

        foreach ($indexes as $name => $columns) {
            $stmt = $pdo->prepare("SHOW INDEX FROM appointments WHERE Key_name = ?");
            $stmt->execute([$name]);
            if (!$stmt->fetch()) {
                $pdo->exec("CREATE INDEX $name ON appointments $columns");
                echo "Index '$name' added to MySQL.\n";
            } else {
                 echo "Index '$name' already exists in MySQL.\n";
            }
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
