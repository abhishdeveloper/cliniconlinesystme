<?php
$files = ['config/database.php', 'login.php', 'register.php'];
$vulnerable = false;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    $content = file_get_contents($file);

    // Improved Regex: Ensures that getMessage() is used in an insecure context within the SAME block
    // It looks for catch(PDOException $e) { ... } and checks if inside the braces there is a pattern like:
    // die(... $e->getMessage() ...) OR echo ... $e->getMessage() ... OR $error = ... $e->getMessage() ...

    // We can iterate over all catch blocks manually to be safe.
    preg_match_all('/catch\s*\(\s*PDOException\s+\$e\s*\)\s*\{(.*?)\}/s', $content, $matches);

    foreach ($matches[1] as $block_content) {
        // Check if $e->getMessage() is used in an insecure way inside this block

        // Check for die() with getMessage()
        if (preg_match('/die\s*\(\s*.*\$e->getMessage\(\)/s', $block_content)) {
             echo "Vulnerability found in $file: die() with getMessage().\n";
             $vulnerable = true;
        }

        // Check for echo with getMessage()
        if (preg_match('/echo\s+.*\$e->getMessage\(\)/s', $block_content)) {
             echo "Vulnerability found in $file: echo with getMessage().\n";
             $vulnerable = true;
        }

        // Check for assignment to $error (or similar) with getMessage(), but we need to be careful.
        // If we assign to a variable that is later displayed, it's vulnerable.
        // But for now, let's just check if it's assigned to $error or similar common vars in this codebase.
        // And importantly, if it is NOT wrapped in error_log.

        // A simple heuristic: if $e->getMessage() appears, it should ONLY be inside error_log(...)

        // Remove valid usages (error_log)
        $cleaned_block = preg_replace('/error_log\s*\(.*?\);/s', '', $block_content);

        if (strpos($cleaned_block, '$e->getMessage()') !== false) {
             // It is still present after removing error_log lines. This suggests usage in die, echo, or assignment.
             echo "Vulnerability found in $file: getMessage() usage outside error_log.\n";
             echo "Block content (cleaned):\n$cleaned_block\n";
             $vulnerable = true;
        }
    }
}

if ($vulnerable) {
    exit(1);
} else {
    echo "No obvious information leakage found in target files.\n";
    exit(0);
}
?>
