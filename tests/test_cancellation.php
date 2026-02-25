<?php
// tests/test_cancellation.php

// 1. Setup Environment
require_once 'includes/functions.php';
// Mock Notifications
if (!function_exists('sendSMS')) {
    function sendSMS($to, $body) { echo "[MOCK SMS] To: $to, Body: $body\n"; return true; }
}
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $body) { echo "[MOCK EMAIL] To: $to, Subject: $subject\n"; return true; }
}

echo "Starting Cancellation Tests with SQLite...\n";

// 2. Setup SQLite Database
try {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create Tables (Simplified schema for testing)
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            password TEXT,
            role TEXT
        );
        CREATE TABLE appointment_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            doctor_id INTEGER,
            slot_datetime DATETIME,
            is_booked INTEGER DEFAULT 0
        );
        CREATE TABLE appointments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER,
            doctor_id INTEGER,
            slot_id INTEGER,
            appointment_date DATETIME,
            status TEXT DEFAULT 'pending',
            notes TEXT
        );
    ");

    // 2.1 Create Test Doctor
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'doctor')");
    $stmt->execute(['Test Doctor', 'testdoc@example.com', 'password']);
    $doctor_id = $pdo->lastInsertId();
    echo "Created Test Doctor (ID: $doctor_id)\n";

    // 2.2 Create Test Patient
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')");
    $stmt->execute(['Test Patient', 'testpat@example.com', 'password']);
    $patient_id = $pdo->lastInsertId();
    echo "Created Test Patient (ID: $patient_id)\n";

    // 2.3 Create Test Slot (Future > 2 hours)
    $future_time = date('Y-m-d H:i:s', strtotime('+3 hours'));
    $stmt = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime, is_booked) VALUES (?, ?, 1)");
    $stmt->execute([$doctor_id, $future_time]);
    $slot_id_future = $pdo->lastInsertId();
    echo "Created Future Slot (ID: $slot_id_future)\n";

    // 2.4 Create Test Appointment (Future > 2 hours)
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, status, notes) VALUES (?, ?, ?, ?, 'pending', 'Test Notes')");
    $stmt->execute([$patient_id, $doctor_id, $slot_id_future, $future_time]);
    $appt_id_future = $pdo->lastInsertId();
    echo "Created Future Appointment (ID: $appt_id_future)\n";

    // 2.5 Create Test Slot (Near Future < 2 hours)
    $near_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT INTO appointment_slots (doctor_id, slot_datetime, is_booked) VALUES (?, ?, 1)");
    $stmt->execute([$doctor_id, $near_time]);
    $slot_id_near = $pdo->lastInsertId();
    echo "Created Near Future Slot (ID: $slot_id_near)\n";

    // 2.6 Create Test Appointment (Near Future < 2 hours)
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, status, notes) VALUES (?, ?, ?, ?, 'pending', 'Test Notes')");
    $stmt->execute([$patient_id, $doctor_id, $slot_id_near, $near_time]);
    $appt_id_near = $pdo->lastInsertId();
    echo "Created Near Future Appointment (ID: $appt_id_near)\n";

    // 3. Run Tests

    // Test 1: Cancel Future Appointment (Should Success)
    echo "\nTest 1: Cancel Future Appointment (> 2 hours)...\n";
    $result = cancelAppointment($pdo, $appt_id_future, $patient_id, "Testing Cancellation");

    if ($result['success']) {
        echo "[PASS] Cancellation successful: " . $result['message'] . "\n";

        // Verify DB State
        $stmt = $pdo->prepare("SELECT status FROM appointments WHERE id = ?");
        $stmt->execute([$appt_id_future]);
        $status = $stmt->fetchColumn();
        if ($status === 'cancelled') {
             echo "[PASS] Appointment status updated to 'cancelled'.\n";
        } else {
             echo "[FAIL] Appointment status is '$status'.\n";
        }

        $stmt = $pdo->prepare("SELECT is_booked FROM appointment_slots WHERE id = ?");
        $stmt->execute([$slot_id_future]);
        $is_booked = $stmt->fetchColumn();
        if ($is_booked == 0) {
             echo "[PASS] Slot freed (is_booked = 0).\n";
        } else {
             echo "[FAIL] Slot is_booked = $is_booked.\n";
        }

    } else {
        echo "[FAIL] Cancellation failed: " . $result['message'] . "\n";
    }

    // Test 2: Cancel Near Future Appointment (Should Fail)
    echo "\nTest 2: Cancel Near Future Appointment (< 2 hours)...\n";
    $result = cancelAppointment($pdo, $appt_id_near, $patient_id, "Testing Late Cancellation");
    if (!$result['success']) {
        echo "[PASS] Cancellation correctly denied: " . $result['message'] . "\n";
    } else {
        echo "[FAIL] Cancellation should have failed but succeeded.\n";
    }

    // Test 3: Cancel Already Cancelled Appointment (Should Fail)
    echo "\nTest 3: Cancel Already Cancelled Appointment...\n";
    $result = cancelAppointment($pdo, $appt_id_future, $patient_id, "Double Cancellation");
    if (!$result['success']) {
        echo "[PASS] Double cancellation correctly denied: " . $result['message'] . "\n";
    } else {
        echo "[FAIL] Double cancellation should have failed but succeeded.\n";
    }

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
