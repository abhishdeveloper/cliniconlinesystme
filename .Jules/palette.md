## 2024-05-22 - Password Visibility Pattern
**Learning:** Standardized password visibility toggle using Bootstrap 5 `input-group` and a `.toggle-password` button class. The JS handler in `script.js` automatically binds to these buttons and toggles the sibling input's type.
**Action:** Use this structure for any future password fields: wrap input and button in `.input-group`, add `.toggle-password` to button, and ensure initial `aria-label="Show password"`.
