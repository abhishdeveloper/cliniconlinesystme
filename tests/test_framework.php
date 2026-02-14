<?php

function assertTrue($condition, $message) {
    if (!$condition) {
        throw new Exception("Assertion Failed: $message");
    }
    echo "[PASS] $message\n";
}

function assertFalse($condition, $message) {
    if ($condition) {
        throw new Exception("Assertion Failed: $message");
    }
    echo "[PASS] $message\n";
}

function assertEquals($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new Exception("Assertion Failed: $message. Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
    }
    echo "[PASS] $message\n";
}

function runTest($testName, $callback) {
    try {
        echo "Running: $testName...\n";
        $callback();
        echo "Test Passed: $testName\n\n";
    } catch (Exception $e) {
        echo "Test Failed: $testName\n";
        echo $e->getMessage() . "\n\n";
        exit(1);
    }
}
