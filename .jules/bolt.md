## 2026-02-26 - N+1 Query Impact on SQLite
**Learning:** A nested loop performing SELECT+INSERT per item caused a 93x performance penalty (0.93s vs 0.01s for 480 items). SQLite's auto-commit overhead per INSERT is significant.
**Action:** Always batch INSERTs in a transaction and fetch existence checks in bulk (O(1) query) rather than checking per item (O(N) queries).
