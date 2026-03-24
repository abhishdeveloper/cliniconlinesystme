## 2024-05-14 - Optimize PHP loop condition
**Learning:** Hoisting `count()` out of `for` loop conditions in PHP is a documented performance best practice to avoid redundant O(1) function calls across many iterations.
**Action:** When iterating over arrays with `for` loops in PHP, assign `count($array)` to a variable before the loop and use that variable in the loop condition.
