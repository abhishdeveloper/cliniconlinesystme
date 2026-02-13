## 2024-03-24 - Unassociated Visual Labels
**Learning:** The application frequently uses visual headers (e.g., "1. Select Doctor") as form labels, but they are not programmatically associated with the input fields (which are often search/filter inputs or lists). This creates a disconnect for screen reader users who land on the inputs without context.
**Action:** Always associate the primary visual label with the main input using `for="id"`. For secondary inputs (like filters), add an `aria-label` or `aria-describedby` to clarify their purpose, especially if the visual label is generic.

## 2024-03-24 - SQLite vs MySQL Compatibility
**Learning:** The application uses SQLite for local development (`config/database.php`) but contains MySQL-specific functions like `NOW()` in queries (e.g., `patient/dashboard.php`). This breaks local verification of features dependent on these queries.
**Action:** When testing locally, be aware that `NOW()` is not supported in SQLite. Use `datetime('now')` or pass the current time from PHP (`date('Y-m-d H:i:s')`) for compatibility. If backend changes are out of scope, temporary modifications are needed for verification but must be reverted before submission.
