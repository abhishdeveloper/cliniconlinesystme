
## 2024-03-07 - [Optimize Doctor Schedule Slot Generation]
**Learning:** Using `NOW()` directly in SQL queries (like `SELECT ... WHERE slot_datetime >= NOW()`) will cause SQLite to throw a "no such function" error.
**Action:** Always use PHP's `date('Y-m-d H:i:s')` and pass it as a parameter for compatibility across SQLite and MySQL.
