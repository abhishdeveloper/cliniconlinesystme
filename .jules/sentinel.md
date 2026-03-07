## 2024-05-18 - Fix Information Leakage
**Vulnerability:** Information leakage through PDOException `$e->getMessage()`.
**Learning:** Returning database exceptions to users bypasses error handling and exposes database schema and query details.
**Prevention:** Catch database exceptions and replace user-facing messages with generic messages, and use `error_log` for debugging and logging the actual errors.
