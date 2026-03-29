## 2024-03-29 - [Fix Insecure GET Request for Deletion and CSRF]
**Vulnerability:** State-altering actions (medicine deletion) were performed via HTTP GET requests (`?delete=ID`) and lacked CSRF protection.
**Learning:** This violates HTTP semantics (GET should be safe/idempotent) and exposes the application to Cross-Site Request Forgery (CSRF) vulnerabilities where a user could be tricked into deleting a medicine.
**Prevention:** Always use POST forms for state-altering actions, and ensure CSRF tokens are generated and validated on form submission. Added `generateCsrfToken` and `validateCsrfToken` helpers to `includes/functions.php`.
