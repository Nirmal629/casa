# App-only security tests

Scope: staging/app source only. No real database or external application endpoint is exercised.
PHP tests use fake PDO; HTTP tests use an isolated fixture that loads only app/Helpers/Session.php and app/Middleware/CsrfGuard.php.

Run from this directory:

    php regression.php
    node transport.test.cjs
    python http_regression.py

The HTTP suite requires localhost socket access. Results and temporary runtime files stay under this directory.

Latest verification: 71 PHP regression checks, 8 JavaScript transport checks, 6 isolated HTTP checks passed. All 137 app PHP files (including the PHP regression runner) passed syntax validation.
These checks are not a complete database-backed security clearance.
