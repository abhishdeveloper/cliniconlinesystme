## 2024-05-28 - Missing CSRF Protection
**Vulnerability:** Missing CSRF token protection on sensitive forms (login, register, profile updates, appointment booking).
**Learning:** The application lacks a centralized CSRF protection mechanism, leaving state-changing requests vulnerable to cross-site request forgery attacks.
**Prevention:** Implement `generate_csrf_token()` and `verify_csrf_token()` in `includes/functions.php` and require CSRF tokens on all POST requests that modify state.
