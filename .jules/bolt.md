
## 2024-10-30 - SQLite Disk-Sync I/O and O(N) Queries
**Learning:** During slot generation in `doctor/schedule.php`, using an N+1 database `SELECT` pattern inside a nested loop causes severe performance degradation, especially critical for the SQLite development database which suffers from huge disk-sync I/O overhead without transactions.
**Action:** Always fetch existing relevant entries in a single query outside loops, map them to an in-memory hash map for O(1) existence checking, and batch all `INSERT` statements inside a single `$pdo->beginTransaction()` and `$pdo->commit()` block. Add newly generated items locally to the map to prevent duplicates during overlapping iteration.
