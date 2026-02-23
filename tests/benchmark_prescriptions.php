<?php
require_once __DIR__ . '/../config/database.php';

// Check if we have enough prescriptions, if not seed them
echo "Checking prescription count...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions");
$count = $stmt->fetchColumn();

if ($count < 10000) {
    echo "Seeding prescriptions (target: 10,000)...\n";

    // Get a doctor and a patient
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
    $doctor_id = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
    $patient_id = $stmt->fetchColumn();

    if (!$doctor_id || !$patient_id) {
        die("Error: Need at least one doctor and one patient to seed data.\n");
    }

    // Prepare statement for insertion
    $insert = $pdo->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, medicines_json, notes, created_at) VALUES (1, ?, ?, ?, ?, ?, ?)");

    // Create a dummy appointment if not exists (appointment_id is FK)
    // We'll just use ID 1 for all dummy prescriptions if possible, or we need to insert appointments too.
    // Let's check if appointment 1 exists.
    $stmt = $pdo->query("SELECT id FROM appointments WHERE id = 1");
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO appointments (id, patient_id, doctor_id, appointment_date, status) VALUES (1, ?, ?, CURRENT_TIMESTAMP, 'completed')")->execute([$patient_id, $doctor_id]);
    }

    $pdo->beginTransaction();
    for ($i = $count; $i < 10000; $i++) {
        $diagnosis = "Diagnosis " . $i;
        $medicines = json_encode([['name' => 'Med ' . $i, 'dosage' => '1 daily']]);
        $notes = "Note " . $i;
        $created_at = date('Y-m-d H:i:s');
        $insert->execute([$patient_id, $doctor_id, $diagnosis, $medicines, $notes, $created_at]);

        if ($i % 1000 == 0) {
            echo "Seeded $i prescriptions...\n";
            $pdo->commit();
            $pdo->beginTransaction();
        }
    }
    $pdo->commit();
    echo "Seeding complete.\n";
} else {
    echo "Existing prescriptions count: $count\n";
}

// Measure baseline (fetch all)
echo "\nMeasuring baseline (fetch all)...\n";
$start = microtime(true);

$stmt = $pdo->query("
    SELECT p.id, p.created_at, p.diagnosis,
           u_pat.name AS patient_name, u_pat.email AS patient_email,
           u_doc.name AS doctor_name
    FROM prescriptions p
    JOIN users u_pat ON p.patient_id = u_pat.id
    JOIN users u_doc ON p.doctor_id = u_doc.id
    ORDER BY p.created_at DESC
");
$results = $stmt->fetchAll();

$end = microtime(true);
$baseline_time = $end - $start;
echo "Baseline time: " . number_format($baseline_time, 4) . " seconds\n";
echo "Fetched count: " . count($results) . "\n";

// Measure optimized (fetch page 1, limit 20)
echo "\nMeasuring optimized (fetch page 1, limit 20)...\n";
$start = microtime(true);

$limit = 20;
$offset = 0;
$stmt = $pdo->prepare("
    SELECT p.id, p.created_at, p.diagnosis,
           u_pat.name AS patient_name, u_pat.email AS patient_email,
           u_doc.name AS doctor_name
    FROM prescriptions p
    JOIN users u_pat ON p.patient_id = u_pat.id
    JOIN users u_doc ON p.doctor_id = u_doc.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll();

$end = microtime(true);
$optimized_time = $end - $start;
echo "Optimized time: " . number_format($optimized_time, 4) . " seconds\n";
echo "Fetched count: " . count($results) . "\n";

// Calculate improvement
if ($optimized_time > 0) {
    $improvement = $baseline_time / $optimized_time;
    echo "\nImprovement: " . number_format($improvement, 1) . "x faster\n";
}
?>