<?php
// Database Configuration

// Load environment variables from .env file if it exists
(function() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Basic quote handling
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                if (getenv($name) === false) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
})();

$host = getenv('DB_HOST');
if ($host === false) {
    die("Environment variable DB_HOST not set.");
}

$dbname = getenv('DB_NAME');
if ($dbname === false) {
    die("Environment variable DB_NAME not set.");
}

$username = getenv('DB_USER');
if ($username === false) {
    die("Environment variable DB_USER not set.");
}

$password = getenv('DB_PASS');
if ($password === false) {
    die("Environment variable DB_PASS not set.");
}

// Allow skipping connection for testing purposes
if (getenv('DB_TEST_NO_CONNECT')) {
    return;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // For production, log the error and show a generic message
    die("Database connection failed: " . $e->getMessage());
}
?>
