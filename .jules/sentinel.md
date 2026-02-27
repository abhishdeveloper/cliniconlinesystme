## 2024-05-24 - CSRF Protection Implementation
**Vulnerability:** Missing Cross-Site Request Forgery (CSRF) protection on critical state-changing endpoints (`login.php`, `register.php`, `profile.php`).
**Learning:** Native PHP applications often default to simple POST handling without token verification. Relying solely on `isset($_POST)` is insufficient to prevent attackers from tricking authenticated users into submitting unwanted actions.
**Prevention:**
1.  Implemented a session-based Synchronizer Token Pattern.
2.  `generate_csrf_token()`: Creates and stores a cryptographic random token in `$_SESSION`.
3.  `verify_csrf_token()`: Validates incoming POST tokens against the session token using `hash_equals` (to prevent timing attacks).
4.  Applied middleware-style checks at the start of POST handlers and injected hidden input fields into forms.
