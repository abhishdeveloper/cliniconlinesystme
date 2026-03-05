
## 2024-05-20 - [SQLite Disk I/O Bottleneck on Batch Inserts]
**Learning:** SQLite suffers from severe disk-sync I/O overhead when executing numerous individual `INSERT` statements because each statement auto-commits by default. In `doctor/schedule.php`, this caused significant delays when generating 30 days of appointment slots.
**Action:** Always wrap bulk database insert operations (especially in loops) within a single database transaction (`$pdo->beginTransaction()` and `$pdo->commit()`). Additionally, fetch existing records in a single `SELECT` query and use `array_flip(PDO::FETCH_COLUMN)` to create a hash map for O(1) existence checks, eliminating N+1 `SELECT` query bottlenecks.
