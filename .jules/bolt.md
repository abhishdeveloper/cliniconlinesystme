## 2024-10-15 - Runtime Schema Initialization
**Learning:** The application was running `CREATE TABLE IF NOT EXISTS` and seeding checks on *every single request* via `config/database.php`. This added significant overhead (multiple queries and file locks for SQLite).
**Action:** Always separate schema migration/initialization logic from the runtime database connection setup. Use a dedicated `migration.php` script for setup.
