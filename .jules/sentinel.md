## 2024-05-21 - Information Disclosure in Exception Handling
**Vulnerability:** Raw `PDOException` messages were being displayed directly to users in `catch` blocks across multiple files (`login.php`, `register.php`, dashboards, etc.).
**Learning:** Developers often default to echoing `$e->getMessage()` for debugging convenience, forgetting to remove it for production, which leaks database schema details.
**Prevention:** Always use a generic error message for the user (e.g., "An unexpected error occurred") and log the specific exception details server-side using `error_log()`.
