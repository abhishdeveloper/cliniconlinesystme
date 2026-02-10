<?php
// Database Setup Script for Shared Hosting
// Usage: Upload this file and run it once from your browser (e.g., yoursite.com/setup_database.php)

require_once 'config/database.php';

try {
    echo "<h1>Clinic System Database Setup</h1>";
    echo "<p>Connecting to database...</p>";

    // Check connection
    if ($pdo) {
        echo "<p class='text-success'>Connected to database successfully.</p>";
    }

    // Read SQL file
    $sqlFile = 'database.sql';
    if (!file_exists($sqlFile)) {
        die("<p class='text-danger'>Error: database.sql not found.</p>");
    }

    $sql = file_get_contents($sqlFile);

    // Split into individual queries (basic split by semicolon)
    // Note: This is a simple splitter and might fail with complex stored procedures or semicolons in strings.
    // For this project, it's sufficient.
    $queries = explode(';', $sql);

    echo "<p>Executing queries...</p><ul>";

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                echo "<li>Executed: " . htmlspecialchars(substr($query, 0, 50)) . "...</li>";
            } catch (PDOException $e) {
                // Ignore "table already exists" errors or similar if re-running
                echo "<li style='color:orange'>Warning: " . htmlspecialchars($e->getMessage()) . "</li>";
            }
        }
    }

    echo "</ul>";
    echo "<h3 style='color:green'>Database setup completed successfully!</h3>";
    echo "<p>You can now <a href='login.php'>Login</a>.</p>";
    echo "<p><strong>Default Superadmin:</strong> superadmin@clinic.com / admin123</p>";
    echo "<p><strong>Default Admin:</strong> admin@clinic.com / admin123</p>";
    echo "<p><strong>Default Doctor:</strong> doctor@clinic.com / doctor123</p>";
    echo "<p><strong>Default Patient:</strong> patient@clinic.com / patient123</p>";
    echo "<p style='color:red'><strong>Security Warning: Delete this file (setup_database.php) after use!</strong></p>";

} catch (PDOException $e) {
    die("<h3 style='color:red'>Database Error: " . $e->getMessage() . "</h3>");
}
?>
