## 2024-05-22 - Missing Database Indexes
**Learning:** Core tables like `appointments` were missing indexes on frequently filtered columns (`doctor_id`, `patient_id`) used in high-traffic dashboards. This caused full table scans, which scale linearly (O(n)) and become a bottleneck as data grows.
**Action:** Always verify indexes for `WHERE` and `ORDER BY` clauses on core entities. Added `idx_appointments_doctor_date` and `idx_appointments_patient_date` for O(log n) lookups.
