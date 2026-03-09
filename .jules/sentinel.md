## 2024-05-24 - Remove Hardcoded Secrets
**Vulnerability:** Hardcoded API keys and SMTP credentials were left in the codebase in `includes/notifications.php`.
**Learning:** Hardcoded credentials are a critical security risk as they can be extracted by unauthorized actors with source code access, leading to account compromise.
**Prevention:** Always use environment variables via `getenv()` (or a dedicated configuration loader) to inject secrets at runtime. Ensure no hardcoded fallback values remain in the source.
