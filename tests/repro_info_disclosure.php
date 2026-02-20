<?php
// tests/repro_info_disclosure.php

$files = [
    'config/database.php',
    'login.php',
    'register.php',
    'doctor/dashboard.php',
    'patient/dashboard.php'
];

$vulnerable_files = [];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    $lines = file($file);
    foreach ($lines as $lineNum => $line) {
        // Check for risky patterns on the same line
        // 1. Assignment of getMessage() to a variable (usually $error)
        if (preg_match('/\$error\s*=.*\$e->getMessage\(\)/', $line)) {
            $vulnerable_files[$file][] = $lineNum + 1;
        }
        // 2. Echoing getMessage()
        if (preg_match('/echo\s+.*\$e->getMessage\(\)/', $line)) {
            $vulnerable_files[$file][] = $lineNum + 1;
        }
        // 3. Die with getMessage()
        if (preg_match('/die\(.*\$e->getMessage\(\)/', $line)) {
            $vulnerable_files[$file][] = $lineNum + 1;
        }
        // 4. setFlashMessage with getMessage()
        if (preg_match('/setFlashMessage\(.*\$e->getMessage\(\)/', $line)) {
            $vulnerable_files[$file][] = $lineNum + 1;
        }
    }
}

if (count($vulnerable_files) > 0) {
    echo "VULNERABILITY FOUND: Information Disclosure (Database Errors)\n";
    foreach ($vulnerable_files as $f => $lines) {
        echo "- $f (Lines: " . implode(', ', $lines) . ")\n";
    }
    exit(1);
} else {
    echo "SUCCESS: No obvious information disclosure patterns found.\n";
    exit(0);
}
?>
