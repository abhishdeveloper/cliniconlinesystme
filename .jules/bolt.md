
## 2024-05-28 - [Batch database operations overhead]
**Learning:** SQLite development databases combined with nested DB calls within loops cause severe disk-sync I/O overhead. This is caused by executing individual inserts and fetching duplicates within loop logic for each generated object.
**Action:** When creating logic to generate many database entries within loops (such as calendar slots), fetch all existing relevant records in a single query outside the loop into a PHP memory array (`$map = array_flip($results);`). Use this hash map for O(1) validations, and always wrap the execution within a single database transaction using `$pdo->beginTransaction()` and `$pdo->commit()`.
