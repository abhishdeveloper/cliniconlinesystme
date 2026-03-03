
## 2025-03-03 - Bulk Database Operations in SQLite
**Learning:** During the slot generation process, executing a `SELECT` and `INSERT` inside a nested loop for a 30-day period created a severe N+1 query issue. Because the dev environment heavily relies on SQLite, executing hundreds of individual statements causes severe disk-sync I/O overhead per query.
**Action:** Always fetch existing database state in a single query outside the loop and map it into memory for O(1) lookups. When inserting bulk rows, ALWAYS wrap them in a single database transaction (`$pdo->beginTransaction()` and `$pdo->commit()`) to minimize I/O overhead. This drastically reduces execution time (from O(N) database calls to O(1) batched inserts).
