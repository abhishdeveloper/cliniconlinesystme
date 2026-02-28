## 2023-10-25 - Missing CSRF Protection on Sensitive Forms
**Vulnerability:** The application was missing Cross-Site Request Forgery (CSRF) protection on the `login.php` and `register.php` endpoints (as well as generally missing centralized CSRF generation and verification functions).
**Learning:** This architectural gap leaves the application vulnerable to basic CSRF attacks, specifically login CSRF and potentially other state-changing requests if they were similarly unprotected.
**Prevention:** CSRF tokens must be implemented system-wide. I've added `generate_csrf_token()` and `verify_csrf_token()` to `includes/functions.php` and applied them to the authentication forms. Future state-changing forms should also incorporate this token.
