<?php
$files = [
    'doctor/dashboard.php',
    'patient/dashboard.php',
    'admin/prescriptions.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Replace die(...)
    $content = preg_replace_callback(
        '/die\("([^"]+)"\s*\.\s*\$e->getMessage\(\)\);/',
        function($matches) {
            return "error_log(\"{$matches[1]}\" . \$e->getMessage());\n        die(\"An unexpected database error occurred. Please try again later.\");";
        },
        $content
    );

    // Replace $error = ...
    $content = preg_replace_callback(
        '/\$error\s*=\s*"([^"]+)"\s*\.\s*\$e->getMessage\(\);/',
        function($matches) {
            return "error_log(\"{$matches[1]}\" . \$e->getMessage());\n            \$error = \"An unexpected error occurred. Please try again later.\";";
        },
        $content
    );

    // Replace setFlashMessage(...)
    $content = preg_replace_callback(
        '/setFlashMessage\(\'danger\',\s*"([^"]+)"\s*\.\s*\$e->getMessage\(\),\s*\'danger\'\);/',
        function($matches) {
            return "error_log(\"{$matches[1]}\" . \$e->getMessage());\n        setFlashMessage('danger', \"An unexpected error occurred. Please try again later.\", 'danger');";
        },
        $content
    );

    // Replace echo "<tr><td..."
    $content = preg_replace_callback(
        '/echo\s*"<tr><td colspan=\'(\d+)\' class=\'text-danger\'>([^"]+)"\s*\.\s*\$e->getMessage\(\)\s*\.\s*"<\/td><\/tr>";/',
        function($matches) {
            return "error_log(\"{$matches[2]}\" . \$e->getMessage());\n                        echo \"<tr><td colspan='{$matches[1]}' class='text-danger'>An unexpected error occurred. Please try again later.</td></tr>\";";
        },
        $content
    );

    file_put_contents($file, $content);
}
echo "Done.\n";
