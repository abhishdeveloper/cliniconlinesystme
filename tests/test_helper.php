<?php

function assertEquals($expected, $actual, $message = '') {
    if ($expected === $actual) {
        echo "PASS: " . ($message ? $message : "Expected $expected, got $actual") . "\n";
    } else {
        echo "FAIL: " . ($message ? $message : "Expected $expected, got $actual") . "\n";
    }
}

function assertTrue($condition, $message = '') {
    if ($condition) {
        echo "PASS: " . ($message ? $message : "Condition is true") . "\n";
    } else {
        echo "FAIL: " . ($message ? $message : "Condition is false") . "\n";
    }
}

function run_test($name, $test_fn) {
    echo "Running test: $name\n";
    $test_fn();
    echo "\n";
}
?>
