# Bolt's Journal

## 2024-05-18 - [SQLite I/O Bottleneck in N+1 Slot Generation]
**Learning:** During appointment slot generation for a 30-day period, executing individual `SELECT` queries for existence checks and `INSERT` queries for each slot without a transaction causes a massive I/O bottleneck, especially prominent when using SQLite. By executing queries one by one, the database has to sync to disk for every single insert.
**Action:** When performing bulk generation or inserts, always query existing data beforehand to build an in-memory hash map for O(1) existence checks. Furthermore, always wrap bulk `INSERT` statements within a single database transaction (`beginTransaction()` and `commit()`) to avoid individual disk syncs and significantly improve performance.
