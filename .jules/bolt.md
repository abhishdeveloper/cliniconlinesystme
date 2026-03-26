## 2024-05-18 - [Hoist count() out of for loops]
**Learning:** Hoisting `count()` out of `for` loop conditions in PHP avoids redundant O(1) function calls across many iterations.
**Action:** Always compute array length before the loop and use the variable in the loop condition.