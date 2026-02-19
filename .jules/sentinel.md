## 2026-02-19 - Testing Legacy PHP Frontend Controls
**Vulnerability:** Difficult validation of frontend security controls (like CSRF tokens) in scripts that mix PHP logic and HTML output (`login.php`, `register.php`), leading to potential regressions or overlooked controls.
**Learning:** Legacy PHP files rely on `$_SERVER` superglobals and direct output, making standard unit testing difficult without a web server.
**Prevention:** Use `ob_start()` and `ob_get_clean()` to capture output, and mock `$_SERVER` variables (e.g., `REQUEST_METHOD`, `SCRIPT_NAME`) before including the target file. This allows verifying HTML structure (like hidden inputs) in a CLI environment.
