## 2024-05-24 - Session Fixation Fix in login.php
**Vulnerability:** Session Fixation vulnerability existed where the session ID was not regenerated upon successful user authentication.
**Learning:** `session_regenerate_id(true)` is necessary during authentication transitions (e.g., privilege escalation during login) to invalidate old session IDs and prevent session fixation attacks. It was not called when setting the user session.
**Prevention:** Ensure `session_regenerate_id(true)` is called upon user login across all authentication flows.
