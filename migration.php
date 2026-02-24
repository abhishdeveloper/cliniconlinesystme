<?php
require_once 'config/database.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('specialty', $columns)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty TEXT DEFAULT NULL");
            echo "Column 'specialty' added to SQLite database.\n";
        } else {
            echo "Column 'specialty' already exists in SQLite database.\n";
        }

        // Check and Add Indexes
        $stmt = $pdo->query("PRAGMA index_list('appointments')");
        $indexes = $stmt->fetchAll(PDO::FETCH_COLUMN, 1); // Column 1 is 'name'

        if (!in_array('idx_appointments_doctor_date', $indexes)) {
            $pdo->exec("CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, appointment_date)");
            echo "Index 'idx_appointments_doctor_date' added to SQLite database.\n";
        } else {
            echo "Index 'idx_appointments_doctor_date' already exists in SQLite database.\n";
        }

        if (!in_array('idx_appointments_patient_date', $indexes)) {
            $pdo->exec("CREATE INDEX idx_appointments_patient_date ON appointments(patient_id, appointment_date)");
            echo "Index 'idx_appointments_patient_date' added to SQLite database.\n";
        } else {
            echo "Index 'idx_appointments_patient_date' already exists in SQLite database.\n";
        }

    } else {
        // MySQL
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'specialty'");
        $stmt->execute();
        if ($stmt->fetch()) {
            echo "Column 'specialty' already exists in MySQL database.\n";
        } else {
            $pdo->exec("ALTER TABLE users ADD COLUMN specialty VARCHAR(100) DEFAULT NULL AFTER role");
            echo "Column 'specialty' added to MySQL database.\n";
        }

        // MySQL Index Checks
        $checkIndex = function($pdo, $table, $indexName) {
            $stmt = $pdo->prepare("SHOW INDEX FROM $table WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return $stmt->fetch();
        };

        if (!$checkIndex($pdo, 'appointments', 'idx_appointments_doctor_date')) {
            $pdo->exec("CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, appointment_date)");
            echo "Index 'idx_appointments_doctor_date' added to MySQL database.\n";
        } else {
            echo "Index 'idx_appointments_doctor_date' already exists in MySQL database.\n";
        }

        if (!$checkIndex($pdo, 'appointments', 'idx_appointments_patient_date')) {
            $pdo->exec("CREATE INDEX idx_appointments_patient_date ON appointments(patient_id, appointment_date)");
            echo "Index 'idx_appointments_patient_date' added to MySQL database.\n";
        } else {
            echo "Index 'idx_appointments_patient_date' already exists in MySQL database.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
