## 2024-05-18 - [Fix N+1 query in doctor scheduling]
**Learning:** Found an N+1 query bottleneck when generating scheduling slots in PHP. SQLite is heavily impacted by disk-sync I/O when executing loop queries without an explicit transaction. A loop checking `SELECT ...` followed by conditional `INSERT ...` per slot caused a ~0.5s performance cost for generating 30 days of slots.
**Action:** Always fetch existing database items into an array hash map and use a single transaction block for batch inserts in SQLite to improve performance by >99%.
