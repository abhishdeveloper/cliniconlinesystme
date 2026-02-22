## 2026-02-22 - Schema Creation in Config
**Learning:** Found critical anti-pattern where schema creation (CREATE TABLE) and seeding logic ran on every database connection in `config/database.php`. This adds measurable overhead to every request.
**Action:** Always check database config files for expensive initialization logic. Move such logic to dedicated migration or setup scripts to improve request latency.
