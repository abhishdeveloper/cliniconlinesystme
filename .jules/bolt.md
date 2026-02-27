# Bolt's Journal

## 2024-05-22 - Initial Setup
**Learning:** Started Bolt's optimization journey.
**Action:** Always look for N+1 queries first in PHP applications.

## 2024-05-22 - Schedule Generation Bottleneck
**Learning:** Found a nested loop in `doctor/schedule.php` that executes a SELECT query inside a loop for every potential slot to check existence. This is an N+1 query problem.
**Action:** Refactor to fetch existing slots in bulk and filter in memory, or use `INSERT IGNORE` / `ON CONFLICT DO NOTHING` to minimize DB roundtrips.
