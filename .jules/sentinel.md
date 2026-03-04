## 2024-05-24 - Unsanitized Exceptions

**Vulnerability:** Uncaught/raw Exception messages (`$e->getMessage()`) explicitly echoed to user via `die()`.

**Learning:** Emitting generic system errors via output when catching low-level database connection (PDO) exceptions leaks information about inner server and configuration state to the users viewing the UI.

**Prevention:** Always log specific details like `$e->getMessage()` to system logs (`error_log`) while only printing safely sanitized/opaque user-facing errors (e.g., `die("System error occurred. Please try again later.")`).