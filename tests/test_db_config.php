<?php
// tests/test_db_config.php

function verify_config() {
    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) {
        die("Config file not found: $configFile\n");
    }

    // Set environment variable to skip connection
    putenv('DB_TEST_NO_CONNECT=1');

    // Include the config file
    // Variables defined in the included file will be available in the current scope (verify_config function scope)
    include $configFile;

    // Expected values from .env
    $expected_host = 'localhost';
    $expected_dbname = 'clinic_db';
    $expected_username = 'root';
    $expected_password = '';

    $errors = [];
    if (!isset($host)) $errors[] = '$host not defined';
    elseif ($host !== $expected_host) $errors[] = "\$host mismatch: expected '$expected_host', got '$host'";

    if (!isset($dbname)) $errors[] = '$dbname not defined';
    elseif ($dbname !== $expected_dbname) $errors[] = "\$dbname mismatch: expected '$expected_dbname', got '$dbname'";

    if (!isset($username)) $errors[] = '$username not defined';
    elseif ($username !== $expected_username) $errors[] = "\$username mismatch: expected '$expected_username', got '$username'";

    if (!isset($password)) $errors[] = '$password not defined';
    elseif ($password !== $expected_password) $errors[] = "\$password mismatch: expected '$expected_password', got '$password'";

    if (!empty($errors)) {
        echo "Configuration verification FAILED:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
        exit(1);
    }

    echo "Configuration verification PASSED.\n";
}

verify_config();
