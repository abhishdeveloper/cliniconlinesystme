## 2024-05-28 - SQLite Batch Insert Performance
**Learning:** Performing multiple individual `INSERT` or `SELECT` queries inside nested loops creates a severe N+1 query bottleneck and massive disk-sync I/O overhead, particularly on SQLite.
**Action:** Instead of iterating row by row with database operations, fetch existing data into an O(1) PHP hash map (`array_flip(PDO::FETCH_COLUMN)`) and wrap bulk inserts in a database transaction (`$pdo->beginTransaction()` and `$pdo->commit()`) to drastically reduce I/O wait times.
