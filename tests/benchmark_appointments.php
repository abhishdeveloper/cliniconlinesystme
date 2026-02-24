<?php
require_once __DIR__ . '/../config/database.php';

// Default seed count is 10,000 for quick verification.
// For realistic performance testing, increase this to 100,000.
function seedAppointments($pdo, $count = 10000) {
    // 1. Ensure multiple doctors/patients exist for realistic distribution
    $docCount = $pdo->query("SELECT count(*) FROM users WHERE role = 'doctor'")->fetchColumn();
    if ($docCount < 10) {
        echo "Creating 10 doctors...\n";
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, 'pass', 'doctor')");
        for ($i=0; $i<10; $i++) {
            $stmt->execute(["Dr. Test $i", "doc$i@test.com"]);
        }
    }

    $patCount = $pdo->query("SELECT count(*) FROM users WHERE role = 'patient'")->fetchColumn();
    if ($patCount < 100) {
        echo "Creating 100 patients...\n";
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, 'pass', 'patient')");
        for ($i=0; $i<100; $i++) {
            $stmt->execute(["Pat Test $i", "pat$i@test.com"]);
        }
    }

    // 2. Check appointment count
    echo "Checking appointment count...\n";
    $stmt = $pdo->query("SELECT count(*) FROM appointments");
    $currentCount = $stmt->fetchColumn();

    if ($currentCount >= $count) {
        echo "Already have $currentCount appointments. Skipping seed.\n";
        return;
    }

    echo "Seeding " . ($count - $currentCount) . " appointments...\n";

    $pdo->beginTransaction();
    try {
        $docIds = $pdo->query("SELECT id FROM users WHERE role = 'doctor'")->fetchAll(PDO::FETCH_COLUMN);
        $patIds = $pdo->query("SELECT id FROM users WHERE role = 'patient'")->fetchAll(PDO::FETCH_COLUMN);

        $insertStmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, notes) VALUES (?, ?, ?, 'pending', 'Benchmark Test')");

        for ($i = 0; $i < ($count - $currentCount); $i++) {
            $docId = $docIds[array_rand($docIds)];
            $patId = $patIds[array_rand($patIds)];
            $timestamp = time() + rand(-31536000, 31536000);
            $date = date('Y-m-d H:i:s', $timestamp);

            $insertStmt->execute([$patId, $docId, $date]);

            if ($i % 1000 == 0) echo ".";
        }
        $pdo->commit();
        echo "\nSeeding complete.\n";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Seeding failed: " . $e->getMessage() . "\n");
    }
}

function benchmarkQuery($pdo, $label, $sql, $params) {
    $start = microtime(true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    $duration = microtime(true) - $start;

    echo "[$label] Time: " . number_format($duration * 1000, 2) . " ms (" . count($results) . " rows)\n";
    return $duration;
}

seedAppointments($pdo);

// Pick a doctor with appointments
$docId = $pdo->query("SELECT doctor_id FROM appointments GROUP BY doctor_id ORDER BY count(*) DESC LIMIT 1")->fetchColumn();
if (!$docId) die("No doctor found.\n");

echo "\n--- Benchmarking Doctor Dashboard Query (Doctor ID: $docId) ---\n";
$sqlDoc = "SELECT a.*, u.name AS patient_name
           FROM appointments a
           JOIN users u ON a.patient_id = u.id
           WHERE a.doctor_id = ?
           ORDER BY a.appointment_date ASC";

benchmarkQuery($pdo, "Warmup", $sqlDoc, [$docId]);
benchmarkQuery($pdo, "Run 1", $sqlDoc, [$docId]);
benchmarkQuery($pdo, "Run 2", $sqlDoc, [$docId]);

// Pick a patient with appointments
$patId = $pdo->query("SELECT patient_id FROM appointments GROUP BY patient_id ORDER BY count(*) DESC LIMIT 1")->fetchColumn();
if (!$patId) die("No patient found.\n");

echo "\n--- Benchmarking Patient Dashboard Query (Patient ID: $patId) ---\n";
$sqlPat = "SELECT a.*, u.name AS doctor_name
           FROM appointments a
           JOIN users u ON a.doctor_id = u.id
           WHERE a.patient_id = ?
           ORDER BY a.appointment_date DESC";

benchmarkQuery($pdo, "Warmup", $sqlPat, [$patId]);
benchmarkQuery($pdo, "Run 1", $sqlPat, [$patId]);
benchmarkQuery($pdo, "Run 2", $sqlPat, [$patId]);

?>
