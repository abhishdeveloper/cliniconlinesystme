## 2024-05-24 - [Information Leakage via Database Exceptions]
**Vulnerability:** Raw `PDOException` messages were being exposed directly to the user in the frontend (e.g., `login.php`, `register.php`), potentially leaking sensitive database structure, table names, and query details.
**Learning:** This existed because the catch block assigned `$e->getMessage()` directly to the user-facing `$error` variable for quick debugging, rather than using a proper logging mechanism.
**Prevention:** Always log the actual exception message internally using `error_log()` (or a logging library) and return a generic, non-descriptive error message (e.g., "An unexpected error occurred. Please try again later.") to the user.
