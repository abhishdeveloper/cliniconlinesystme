
## 2024-05-24 - N+1 Query Overhead in Nested Loops with SQLite
**Learning:** SQLite disk I/O overhead from auto-commits in tight nested loops severely throttles performance. In `doctor/schedule.php`, thousands of `INSERT`s were being issued with N+1 `SELECT` checks over a 30-day projection matrix.
**Action:** Bulk select required ranges beforehand, map to a PHP `array_flip(PDO::FETCH_COLUMN)` associative hash array for fast O(1) matching checks, and run everything within `$pdo->beginTransaction()`/`$pdo->commit()` for orders of magnitude faster execution. Always use hash sets when tracking newly created components within batch operations (`$existing_slots[$id] = true`) to prevent duplication.
