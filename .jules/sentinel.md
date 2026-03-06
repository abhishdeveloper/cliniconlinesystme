## 2024-03-06 - Unhandled PDOException Information Leakage
**Vulnerability:** Information leakage via raw PDOException messages shown directly to end users.
**Learning:** Database errors, specifically PDOExceptions, display their full error strings directly to the end-user via flash messages, `$error` variables, or raw `die()` statements. These error strings often contain sensitive internal system details like database paths, query structures, or table names.
**Prevention:** All database exceptions must be caught, securely logged using `error_log()`, and replaced with generic user-friendly messages for output. Never echo or output `$e->getMessage()` in a production environment context.
