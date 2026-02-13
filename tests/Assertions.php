<?php

class Assertions {
    private static $passed = 0;
    private static $failed = 0;

    public static function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            self::pass($message);
        } else {
            self::fail($message, "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
        }
    }

    public static function assertStringContainsString($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false) {
            self::pass($message);
        } else {
            self::fail($message, "Expected string to contain '$needle', but got '$haystack'");
        }
    }

    private static function pass($message) {
        self::$passed++;
        echo "✅ PASS: " . $message . "\n";
    }

    private static function fail($message, $detail = '') {
        self::$failed++;
        echo "❌ FAIL: " . $message . "\n";
        if ($detail) {
            echo "   " . $detail . "\n";
        }
    }

    public static function report() {
        echo "\nTests Completed.\n";
        echo "Passed: " . self::$passed . "\n";
        echo "Failed: " . self::$failed . "\n";

        if (self::$failed > 0) {
            exit(1);
        }
    }
}
?>
