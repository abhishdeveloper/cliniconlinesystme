<?php
// tests/benchmark_pagination.php
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=benchmark.db');

require_once __DIR__ . '/../config/database.php';

try {
    // Setup Table
    $pdo->exec("DROP TABLE IF EXISTS medicines");
    $pdo->exec("CREATE TABLE medicines (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'Powder',
        default_dosage TEXT DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Populate Data
    echo "Populating 10,000 rows...\n";
    $stmt = $pdo->prepare("INSERT INTO medicines (name, type, default_dosage, description) VALUES (?, ?, ?, ?)");
    $pdo->beginTransaction();
    for ($i = 0; $i < 10000; $i++) {
        $name = "Medicine " . str_pad($i, 5, '0', STR_PAD_LEFT);
        $type = ['Powder', 'Tablet', 'Syrup', 'Oil'][rand(0, 3)];
        $dosage = "Dosage $i";
        $desc = "Description for medicine $i";
        $stmt->execute([$name, $type, $dosage, $desc]);
    }
    $pdo->commit();
    echo "Data populated.\n\n";

    // Benchmark Fetch All
    $start = microtime(true);
    $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
    $rows = $stmt->fetchAll();
    $end = microtime(true);
    $fetchAllTime = ($end - $start) * 1000; // in ms
    echo "Fetch All (10,000 rows): " . number_format($fetchAllTime, 2) . " ms\n";

    // Benchmark Pagination (Page 1)
    $limit = 10;
    $offset = 0;
    $start = microtime(true);
    $stmt = $pdo->prepare("SELECT * FROM medicines ORDER BY name ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $end = microtime(true);
    $fetchPageTime = ($end - $start) * 1000; // in ms
    echo "Fetch Page 1 (10 rows): " . number_format($fetchPageTime, 2) . " ms\n";

    // Benchmark Pagination (Page 500 - middle)
    $offset = 5000;
    $start = microtime(true);
    $stmt = $pdo->prepare("SELECT * FROM medicines ORDER BY name ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $end = microtime(true);
    $fetchMidPageTime = ($end - $start) * 1000; // in ms
    echo "Fetch Page 500 (10 rows, middle): " . number_format($fetchMidPageTime, 2) . " ms\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Cleanup
if (file_exists('benchmark.db')) {
    unlink('benchmark.db');
}
?>
