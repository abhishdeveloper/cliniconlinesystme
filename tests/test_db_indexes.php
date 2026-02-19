<?php
require_once __DIR__ . '/../config/database.php';

echo "Verifying indexes for driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

$indexes = [
    'idx_appointments_doctor_date' => false,
    'idx_appointments_patient_date' => false,
];

try {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $stmt = $pdo->query("PRAGMA index_list('appointments')");
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($list as $idx) {
            if (isset($indexes[$idx['name']])) {
                $indexes[$idx['name']] = true;
            }
        }
    } else {
        // MySQL
        $stmt = $pdo->prepare("SHOW INDEX FROM appointments");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyName = $row['Key_name'];
            if (isset($indexes[$keyName])) {
                $indexes[$keyName] = true;
            }
        }
    }

    $allFound = true;
    foreach ($indexes as $name => $found) {
        if ($found) {
            echo "Index '$name' FOUND.\n";
        } else {
            echo "Index '$name' NOT FOUND.\n";
            $allFound = false;
        }
    }

    if ($allFound) {
        echo "SUCCESS: All required indexes are present.\n";
        exit(0);
    } else {
        echo "FAILURE: Missing indexes.\n";
        exit(1);
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
