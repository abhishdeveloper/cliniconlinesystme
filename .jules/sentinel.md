## 2024-05-20 - Ensure that exception details are not exposed in user-facing error messages

**Vulnerability:** Raw exception details (`$e->getMessage()`) were exposed directly to the user in `login.php`.
**Learning:** Returning stack traces or exception details can lead to information exposure.
**Prevention:** Catch exceptions, log their contents internally using `error_log()`, and show generic, user-friendly messages instead.
