## 2024-05-24 - Session Fixation During Privilege Escalation
**Vulnerability:** The application did not regenerate the session ID upon successful user login in `login.php`.
**Learning:** If an attacker can force a known session ID onto a victim before they log in, the attacker can hijack the victim's session once the privilege escalation (login) occurs.
**Prevention:** Always call `session_regenerate_id(true);` explicitly immediately after verifying credentials and before setting any sensitive or user-specific data in `$_SESSION`.
