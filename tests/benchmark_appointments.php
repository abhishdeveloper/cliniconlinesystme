<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

echo "Database Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

// Ensure we have a doctor and a patient
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
$stmt->execute();
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    echo "Creating dummy doctor...\n";
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Benchmark Doctor', 'bench_doc@clinic.com', 'password', 'doctor')");
    $doctor_id = $pdo->lastInsertId();
} else {
    $doctor_id = $doctor['id'];
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
$stmt->execute();
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "Creating dummy patient...\n";
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('Benchmark Patient', 'bench_pat@clinic.com', 'password', 'patient')");
    $patient_id = $pdo->lastInsertId();
} else {
    $patient_id = $patient['id'];
}

echo "Using Doctor ID: $doctor_id, Patient ID: $patient_id\n";

// Check appointment count
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$count = $stmt->fetchColumn();
echo "Current appointments count: $count\n";

if ($count < 1000) {
    echo "Seeding 1000 appointments...\n";
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, notes) VALUES (?, ?, ?, 'pending', 'Benchmark Note')");

    for ($i = 0; $i < 1000; $i++) {
        // Random date in past or future
        $date = date('Y-m-d H:i:s', strtotime("now " . ($i % 2 == 0 ? '+' : '-') . rand(1, 365) . " days"));
        $stmt->execute([$patient_id, $doctor_id, $date]);
    }
    $pdo->commit();
    echo "Seeding complete.\n";
}

// Benchmark Doctor Query
echo "Benchmarking Doctor Query (100 iterations)...\n";
$start = microtime(true);
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE doctor_id = ? ORDER BY appointment_date ASC");
for ($i = 0; $i < 100; $i++) {
    $stmt->execute([$doctor_id]);
    $results = $stmt->fetchAll();
}
$end = microtime(true);
$doctor_time = ($end - $start) / 100;
echo "Average Doctor Query Time: " . number_format($doctor_time * 1000, 4) . " ms\n";

// Benchmark Patient Query
echo "Benchmarking Patient Query (100 iterations)...\n";
$start = microtime(true);
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC");
for ($i = 0; $i < 100; $i++) {
    $stmt->execute([$patient_id]);
    $results = $stmt->fetchAll();
}
$end = microtime(true);
$patient_time = ($end - $start) / 100;
echo "Average Patient Query Time: " . number_format($patient_time * 1000, 4) . " ms\n";

?>
