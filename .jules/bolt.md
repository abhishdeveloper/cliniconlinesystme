## 2024-05-22 - [Database Config Overhead]
**Learning:** `config/database.php` was executing schema creation and seeding logic on every request, adding significant overhead (~40ms per request).
**Action:** Always check database configuration files for side effects beyond establishing a connection. Schema management belongs in migration scripts.
