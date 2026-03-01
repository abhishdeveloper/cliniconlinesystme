
## 2024-05-18 - Optimize doctor/schedule.php slot generation
**Learning:** Found an N+1 query vulnerability when bulk-generating appointment slots in `doctor/schedule.php`. The system executed an individual `SELECT` to check for slot existence, followed by an `INSERT` for *each* generated slot within nested loops iterating over weeks of schedule templates.
**Action:** Modified to pre-fetch existing slot dates into a PHP associative array map using a single query beforehand. Then utilized $O(1)$ lookups on this hashmap. Furthermore, grouped all `INSERT` queries into a single database transaction (`$pdo->beginTransaction()` and `$pdo->commit()`), heavily reducing database overhead (especially IO-heavy commits for SQLite).
