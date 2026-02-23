<?php
require_once __DIR__ . '/../config/database.php';

// WARNING: This script is DESTRUCTIVE. It wipes the appointments table.
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Detect Driver
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$ignoreClause = ($driver === 'sqlite') ? 'OR IGNORE' : 'IGNORE';

// Check count
$count = $pdo->query("SELECT count(*) FROM appointments")->fetchColumn();

// If we want to force re-seed for clean benchmark, we should drop if < 100k or just drop always.
// Let's drop always to be consistent.
echo "Cleaning up database (DESTRUCTIVE action)...\n";
$pdo->exec("DELETE FROM appointments");
// Re-enable foreign keys after delete if needed, but we are about to insert.
// SQLite vacuum to reclaim space and reset auto-increment (optional but good for perf testing)
// $pdo->exec("VACUUM");

echo "Seeding 100,000 appointments...\n";

// Ensure Users Exist
$pdo->beginTransaction();
$sql = "INSERT $ignoreClause INTO users (id, name, email, password, role) VALUES (?, ?, ?, 'pass', ?)";
// MySQL INSERT IGNORE syntax is slightly different? No, meant for basic inserts.
// SQLite: INSERT OR IGNORE INTO ...
// MySQL: INSERT IGNORE INTO ...
// So strict replacement works.

$stmt = $pdo->prepare($sql);

$targetDocId = 9999;
$targetPatId = 8888;
$noiseDocId = 1;
$noisePatId = 2;

$stmt->execute([$targetDocId, 'Target Doc', 'targetdoc@test.com', 'doctor']);
$stmt->execute([$targetPatId, 'Target Pat', 'targetpat@test.com', 'patient']);
$stmt->execute([$noiseDocId, 'Noise Doc', 'noisedoc@test.com', 'doctor']);
$stmt->execute([$noisePatId, 'Noise Pat', 'noisepat@test.com', 'patient']);
$pdo->commit();

// Insert Appointments
$pdo->beginTransaction();
$insert = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?)");

for ($i = 0; $i < 100000; $i++) {
    $date = date('Y-m-d H:i:s', strtotime("-1 year + " . ($i * 5) . " minutes"));
    $status = 'completed';

    // 1% chance for target doctor, else noise doctor
    $d = (rand(1, 100) == 1) ? $targetDocId : $noiseDocId;

    // 1% chance for target patient, else noise patient
    $p = (rand(1, 100) == 1) ? $targetPatId : $noisePatId;

    $insert->execute([$p, $d, $date, $status]);
}
$pdo->commit();
echo "Seeding complete.\n";

// Drop indexes to measure baseline
echo "Dropping indexes for baseline measurement...\n";
try {
    $pdo->exec("DROP INDEX IF EXISTS idx_appointments_doctor_date");
    $pdo->exec("DROP INDEX IF EXISTS idx_appointments_patient_date");
} catch (Exception $e) {}

// Benchmark Doctor Query (Baseline)
$start = microtime(true);
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date ASC
");
$stmt->execute([$targetDocId]);
$results = $stmt->fetchAll();
$end = microtime(true);
$baselineDoc = $end - $start;
echo "Baseline Doctor Query: " . number_format($baselineDoc, 6) . "s (" . count($results) . " rows)\n";

// Benchmark Patient Query (Baseline)
$start = microtime(true);
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS doctor_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$targetPatId]);
$results = $stmt->fetchAll();
$end = microtime(true);
$baselinePat = $end - $start;
echo "Baseline Patient Query: " . number_format($baselinePat, 6) . "s (" . count($results) . " rows)\n";

// Create Indexes
echo "Creating indexes...\n";
$startIdx = microtime(true);
$pdo->exec("CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, appointment_date)");
$pdo->exec("CREATE INDEX idx_appointments_patient_date ON appointments(patient_id, appointment_date)");
$endIdx = microtime(true);
echo "Index creation time: " . number_format($endIdx - $startIdx, 6) . "s\n";

// Benchmark Doctor Query (Optimized)
$start = microtime(true);
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date ASC
");
$stmt->execute([$targetDocId]);
$results = $stmt->fetchAll();
$end = microtime(true);
$optDoc = $end - $start;
echo "Optimized Doctor Query: " . number_format($optDoc, 6) . "s\n";

// Benchmark Patient Query (Optimized)
$start = microtime(true);
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS doctor_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$targetPatId]);
$results = $stmt->fetchAll();
$end = microtime(true);
$optPat = $end - $start;
echo "Optimized Patient Query: " . number_format($optPat, 6) . "s\n";

echo "\nImprovement:\n";
if ($baselineDoc > 0)
    echo "Doctor: " . number_format((($baselineDoc - $optDoc) / $baselineDoc) * 100, 2) . "% faster\n";
if ($baselinePat > 0)
    echo "Patient: " . number_format((($baselinePat - $optPat) / $baselinePat) * 100, 2) . "% faster\n";
?>
