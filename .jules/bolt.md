## 2024-02-15 - [Costly Initialization Checks]
**Learning:** Database schema initialization (CREATE TABLE IF NOT EXISTS) on every request adds significant overhead (~45ms) even in SQLite.
**Action:** Use file existence/size checks or specific migration flags to skip initialization after the first run. Always remember `PRAGMA foreign_keys = ON;` must run per-connection.
