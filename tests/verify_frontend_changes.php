<?php
// Verification Script for Frontend Changes

$files = [
    'login.php',
    'register.php',
    'profile.php',
    'assets/js/script.js'
];

$errors = [];

foreach ($files as $file) {
    if (!file_exists($file)) {
        $errors[] = "File not found: $file";
        continue;
    }

    $content = file_get_contents($file);

    if ($file === 'assets/js/script.js') {
        // Check for JS logic
        if (strpos($content, '.toggle-password') === false) {
            $errors[] = "JS: Missing .toggle-password selector in $file";
        }
        if (strpos($content, 'fa-eye') === false || strpos($content, 'fa-eye-slash') === false) {
            $errors[] = "JS: Missing icon toggle logic in $file";
        }
    } else {
        // Check for HTML structure
        // We look for input-group and toggle-password button

        if (strpos($content, 'toggle-password') === false) {
            $errors[] = "HTML: Missing .toggle-password button in $file";
        }

        if (strpos($content, 'fa-eye') === false) {
             $errors[] = "HTML: Missing fa-eye icon in $file";
        }

        if (strpos($content, 'aria-label="Show') === false) {
             $errors[] = "HTML: Missing aria-label in $file";
        }
    }
}

if (empty($errors)) {
    echo "SUCCESS: All frontend changes verified.\n";
    exit(0);
} else {
    echo "FAILURE: Verification failed.\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    exit(1);
}
?>
