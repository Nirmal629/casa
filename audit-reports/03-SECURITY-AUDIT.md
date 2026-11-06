# Security audit

Priority scope: `app/Middleware/AuthGuard.php`, `app/Core/Token.php`, `app/Helpers/RememberMe.php`, `api/`, upload handlers, and tournament endpoints. Graphify identifies authentication/session/database bridges but does not prove runtime authorization correctness.

Status: not yet confirmed; source-level tracing and isolated tests remain.

Review lead (Needs Verification): `app/Controllers/AuthController.php` accepts a non-hash legacy password via `hash_equals($legacyPassword, $password)` when `PASSWORD_HASH` is empty and then upgrades it. This is an intentional compatibility path if legacy plaintext storage exists, but it creates a high-impact credential exposure risk if the database is read; verify schema/data reality before classifying severity. Evidence: lines 219-242.

Review lead (Needs Verification): `AuthController::rememberUser()` writes an identity cookie named from `APP_SESSION_NAME . '_REMEMBER_USER'`, while `RememberMe` uses `APP_REMEMBER_COOKIE` (default `CASA_STAGING_REMEMBER`) for the selector/validator token. This may be intentional UI prefill behavior, but the split naming and dual cookie lifecycle require verification. Evidence: `app/Controllers/AuthController.php:293-307` and `app/Helpers/RememberMe.php:14-20,170-179`.
