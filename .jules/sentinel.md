## 2024-05-24 - [Information Leakage via PDOException]
**Vulnerability:** Raw database exceptions (`PDOException->getMessage()`) were being exposed directly to the end-user via `die()`, `echo()`, and assignment to user-facing error variables. This leaked sensitive information like database schema details, queries, or connection strings.
**Learning:** This pattern existed because the previous error handling directly mapped the database error to the user interface for convenience, rather than sanitizing it for production environments.
**Prevention:** Always log the raw exception message using `error_log()` for debugging, and provide a generic, safe error message to the user (e.g., "An unexpected error occurred. Please try again later.").
