## 2024-05-22 - CSRF Protection in Vanilla PHP
**Vulnerability:** Missing CSRF protection on critical forms (Login/Register).
**Learning:** Vanilla PHP applications require explicit implementation of the Synchronizer Token Pattern. `hash_equals()` is crucial for timing-attack resistance when comparing tokens.
**Prevention:** Always include a hidden CSRF token in POST forms and verify it before processing the request.
