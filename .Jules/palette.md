## 2024-05-23 - Password Visibility Pattern
**Learning:** The application consistently uses password fields without visibility toggles, which increases cognitive load and error rates for users, especially on mobile.
**Action:** Implemented a reusable JS pattern in `assets/js/script.js` that targets `.toggle-password` buttons. Going forward, wrap all password inputs in a Bootstrap `.input-group` with a toggle button using this class to ensure consistent behavior and accessibility across the app.
