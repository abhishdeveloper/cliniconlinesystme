<?php
require_once 'config/database.php';

try {
    $columns = ['prakruti', 'vikruti', 'agni'];
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    foreach ($columns as $col) {
        try {
            $exists = false;

            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info(prescriptions)");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($row['name'] === $col) {
                        $exists = true;
                        break;
                    }
                }
            } else { // MySQL
                $check = $pdo->query("SHOW COLUMNS FROM prescriptions LIKE '$col'");
                if ($check->rowCount() > 0) {
                    $exists = true;
                }
            }

            if (!$exists) {
                // Add column
                // SQLite has limited ALTER TABLE support but adding columns is fine
                // MySQL supports ADD COLUMN ... AFTER ...
                // SQLite supports ADD COLUMN but NOT AFTER

                if ($driver === 'sqlite') {
                    $sql = "ALTER TABLE prescriptions ADD COLUMN $col VARCHAR(50) DEFAULT NULL";
                } else {
                    $sql = "ALTER TABLE prescriptions ADD COLUMN $col VARCHAR(50) DEFAULT NULL AFTER doctor_id";
                }

                $pdo->exec($sql);
                echo "Column '$col' added successfully.\n";
            } else {
                echo "Column '$col' already exists.\n";
            }
        } catch (PDOException $e) {
            echo "Error adding column '$col': " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
