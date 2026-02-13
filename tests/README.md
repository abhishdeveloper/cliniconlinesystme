# Tests for Clinic System

This directory contains simple PHP test scripts to verify the functionality of the application.

## Running Tests

To run the tests, execute the PHP script from the command line:

```bash
php tests/test_isLoggedIn.php
```

Ensure you are in the root directory of the project when running this command.

## Test Coverage

- **test_isLoggedIn.php**: Verifies the `isLoggedIn()` function in `includes/functions.php`. It checks:
    - Not logged in state.
    - Logged in state (generic).
    - Role-based access control (correct role).
    - Role-based access control (incorrect role).
    - Edge cases (missing role in session).
