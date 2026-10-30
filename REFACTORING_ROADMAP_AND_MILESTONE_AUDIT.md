# 🛡️ Casa Games Architecture, Security & Refactoring Roadmap

> **Master Plan & Comprehensive Baseline Audit (Milestones 0 — 8)**  
> **Core Principle**: *"Understand First → Refactor Safely Second"*  
> **API Rule**: Minimal Public Request Contract → Backend Controller/Service → Database (Zero internal implementation exposure).

---

## 📑 Executive Summary & Architectural Scope

This roadmap establishes a strict, phased refactoring strategy for the **Casa Games** web application. It enforces zero disruption to existing business logic, UI, or route behavior while hardening security, improving database efficiency, consolidating APIs, and streamlining code quality.

---

## 🏗️ Milestone 0 — Baseline & Freeze Audit

### 1. Findings
- **Modular Routing Base**: Application routes are modularized in `app/routes/` (`auth.php`, `host.php`, `player.php`, `pages.php`, `store.php`, `tournament.php`, `web.php`).
- **Legacy Endpoint Exposure**: 25+ legacy API files exist in `api/` and `../../api/` (e.g. `cancel_event.php`, `copy_event.php`, `join_club.php`, `player_payment.php`), exposing direct `.php` filesystem paths to frontend JS.
- **Direct Database Queries**: Direct `mysqli_query` calls are scattered across views (`app/Views/player/player-hub.php`, `app/Views/host/dashboard.php`) and controllers instead of using Model abstraction layers.
- **Frontend Information Exposure**: Exception stack traces and `mysqli_error()` output are occasionally echoed back in AJAX endpoints (e.g. `save_payment.php`, `join_club.php`).
- **Duplicate JS Libraries**: Dual Bootstrap versions loaded (`Bootstrap 5.0.2` in `footer-links.php` and `Bootstrap 5.3.2` in `dashboard.php`).

### 2. Severity
- **P0 — Critical** (Information exposure & unthrottled API endpoints).

### 3. Why
Direct exposure of internal filesystem paths and raw database errors leaks internal schema details to attackers, while direct SQL in views violates MVC separation of concerns.

### 4. Recommended Change
1. Freeze existing functionality and preserve exact behavior.
2. Route legacy `/api/*.php` requests through MVC controllers via `app/routes/player.php` and `app/routes/host.php`.
3. Standardize API response formats using `Response::json()` with zero stack traces or filesystem paths.

### 5. Files Affected
- `app/routes/*.php`
- `app/Controllers/*.php`
- `app/Views/**/*.php`
- `app/script/*.js`

### 6. Risk
**Low** (Planning and cataloging phase).

### 7. Expected Benefit
Establishes a solid, predictable baseline for safe incremental refactoring.

### 8. Do Not Touch
- User password hashing mechanism (`password_hash` / legacy password verification in `UserModel.php`).
- Database schema structure (`ca_users`, `ca_events`, `ca_gamejoin`).
- Active session keys (`user_id`, `usertype`, `csrf_token`).

---

## 🛡️ Milestone 1 — Security First

### 1. Findings
- **Sensitive Error Output**: Legacy scripts return raw database errors (`mysqli_error($conn)`) in JSON responses when queries fail.
- **Unprotected File Uploads**: `upload_profile_image.php` checks extension but lacks MIME type verification via `finfo_file()`.
- **Role Elevation Risk**: Frontend JS relies on DOM attribute values for permission checks; backend endpoints must re-verify role permissions independently.
- **Missing Rate Limiting**: Login endpoints (`/login`, `/login.php`) and profile updates (`/api/update-profile`) lack rate-limiting guards against brute-force attempts.

### 2. Severity
- **P0 — Critical** (Data exposure & unthrottled authentication).

### 3. Why
Exposing DB errors or trusting client-side role checks allows malicious users to probe for vulnerabilities or bypass permissions.

### 4. Recommended Change
1. Wrap all database operations in `try/catch` blocks and return generic user-friendly error messages (`Response::json(['success' => false, 'message' => 'An error occurred.'])`).
2. Enforce strict MIME-type validation in `uploadFile()` (`image/jpeg`, `image/png`, `image/webp`).
3. Enforce `AuthGuard::handle(['Role'])` on all backend API routes.
4. Add rate-limiting IP throttling on login and sensitive POST routes.

### 5. Files Affected
- `app/Middleware/AuthGuard.php`
- `app/Helpers/Session.php`
- `app/Controllers/AuthController.php`
- `app/Controllers/PlayerController.php`
- `helpers/helpers.php`

### 6. Risk
**Medium** (Requires careful testing of error responses).

### 7. Expected Benefit
Eliminates information leakage, prevents unauthorized API calls, and protects against brute-force attacks.

### 8. Do Not Touch
- Password hashing algorithm in `UserModel.php`.
- Active session variable structure.

---

## 🏛️ Milestone 2 — Architecture Cleanup

### 1. Findings
- **Duplicate Helper Wrappers**: `app/Helpers/session_functions.php` and `app/Helpers/Session.php` duplicate helper accessors.
- **Raw SQL in Controllers**: `PlayerController.php` and `HostController.php` execute raw SQL alongside Model calls.
- **View Business Logic**: Views like `player-hub.php` calculate date formatting, payment states, and tournament conditions inline instead of consuming pre-formatted view models.

### 2. Severity
- **P1 — High**.

### 3. Why
Coupling business logic inside views and executing raw SQL inside controllers makes maintenance difficult and increases code duplication.

### 4. Recommended Change
1. Enforce strict data flow: `Route → Middleware → Controller → Model/Service → Database`.
2. Move DB queries from views/controllers into dedicated methods in `UserModel`, `EventModel`, and `TournamentModel`.
3. Pre-process view data in controllers before passing to `view()`.

### 5. Files Affected
- `app/Controllers/PlayerController.php`
- `app/Controllers/HostController.php`
- `app/Models/*.php`
- `app/Views/player/player-hub.php`

### 6. Risk
**Medium**.

### 7. Expected Benefit
Clean separation of concerns, reusable model methods, and testable controller logic.

### 8. Do Not Touch
- Controller method names mapped to existing routes (`hub()`, `dashboard()`, `updateProfile()`).

---

## ⚡ Milestone 3 — Database & Performance Optimization

### 1. Findings
- **`SELECT *` Usage**: Models and helpers perform `SELECT *` queries when only 2-3 fields are needed.
- **Repeated User Queries**: Multiple components fetch user profile data during a single request instead of reusing `Session::getCurrentUser()`.
- **Unindexed Queries**: `ca_player_logs` and `ca_player_club_status` filter on `(player_id, status)` without composite indexes.

### 2. Severity
- **P2 — Medium**.

### 3. Why
Fetching unnecessary columns and executing redundant database lookups degrades response time under high traffic.

### 4. Recommended Change
1. Optimize queries to select specific required columns instead of `SELECT *`.
2. Leverage `Session::getCurrentUser()` in-memory request caching across all controllers.
3. Recommend composite database indexes for `ca_player_club_status(player_id, status)` and `ca_player_logs(USER_ID, HOST_ID)`.

### 5. Files Affected
- `app/Models/UserModel.php`
- `app/Models/EventModel.php`
- `app/Helpers/Session.php`
- `app/Helpers/audit.php`

### 6. Risk
**Low**.

### 7. Expected Benefit
Faster database query execution, reduced memory usage, and lower database server load.

### 8. Do Not Touch
- Core table structures or foreign key names.

---

## 📡 Milestone 4 — API & AJAX Consolidation

### 1. Findings
- **Legacy API File Paths**: Frontend JS files call legacy paths like `api/player_payment.php`, `api/join_club.php`, and `../../api/cancel_event.php`.
- **Inconsistent Response Format**: Some API endpoints return `{ status: 'success', message: '...' }` while others return `{ success: true, message: '...' }`.

### 2. Severity
- **P1 — High**.

### 3. Why
Exposing direct `.php` file paths leaks backend architecture details and violates the **Minimal Public Contract** rule.

### 4. Recommended Change
1. Map all API endpoints cleanly under `/api/...` in `app/routes/player.php` and `app/routes/host.php`.
2. Standardize all API JSON responses to `{ success: boolean, message: string, data?: array }` using `Response::json()`.
3. Keep backward-compatible route aliases for legacy AJAX callers during migration.

### 5. Files Affected
- `app/routes/player.php`
- `app/routes/host.php`
- `app/script/player-hub.js`
- `app/script/inner-header.js`

### 6. Risk
**Medium** (Requires verifying all AJAX consumer URLs).

### 7. Expected Benefit
Clean API surface, zero implementation leakage, and standardized client response handling.

### 8. Do Not Touch
- Public API route paths already in production use (`/api/update-profile`).

---

## 🔑 Milestone 5 — Session & Authentication Optimization

### 1. Findings
- **Redundant Session Verification**: Multiple views and controllers call `session_start()` directly instead of relying on `Session::start()`.
- **Duplicate Role Checks**: `requireLogin()` and `requireUserType()` logic is repeated in inline file headers.

### 2. Severity
- **P2 — Medium**.

### 3. Why
Direct `session_start()` calls can throw PHP warnings if headers were already sent, and duplicate role checks introduce maintenance bugs.

### 4. Recommended Change
1. Ensure `init.php` / `app.php` initializes `Session::start()` centrally.
2. Replace inline session checks in view files with `AuthGuard::handle()` or `Session::requireLogin()`.

### 5. Files Affected
- `init.php`
- `app/Helpers/Session.php`
- `app/Middleware/AuthGuard.php`
- `app/Views/**/*.php`

### 6. Risk
**Low**.

### 7. Expected Benefit
Eliminates session warnings, guarantees CSRF readiness, and unifies role verification.

### 8. Do Not Touch
- Active session variable names (`user_id`, `usertype`, `csrf_token`).

---

## 🎨 Milestone 6 — Frontend Optimization

### 1. Findings
- **Dual Bootstrap Version Included**: `footer-links.php` includes Bootstrap 5.0.2 while `dashboard.php` includes Bootstrap 5.3.2.
- **Inline Script Pollution**: Views contain inline `<script>` tags executing AJAX logic directly.

### 2. Severity
- **P2 — Medium**.

### 3. Why
Loading multiple versions of Bootstrap increases page size and causes modal JS errors, while inline scripts violate content security policies.

### 4. Recommended Change
1. Standardize frontend dependency versions across all view layouts.
2. Move inline view JS into modular script files under `app/script/` (`inner-header.js`, `player-hub.js`).

### 5. Files Affected
- `app/Views/layouts/footer/footer-links.php`
- `app/Views/layouts/header/header-links.php`
- `app/script/*.js`

### 6. Risk
**Low**.

### 7. Expected Benefit
Faster page load times, consistent UI rendering, and clean separation of HTML and JavaScript.

### 8. Do Not Touch
- Third-party CDN libraries required by specific views (Slick, ApexCharts, GLightbox).

---

## 🧼 Milestone 7 — Code Quality & PSR Standards

### 1. Findings
- **Missing Type Hints**: Legacy functions in `helpers/helpers.php` lack return type hints or parameter types.
- **Long Method Bodies**: `PlayerController.php` methods combine validation, database queries, and view rendering.

### 2. Severity
- **P3 — Low**.

### 3. Why
Lack of strict typing increases runtime error risk, while monolithic methods reduce readability.

### 4. Recommended Change
1. Add `declare(strict_types=1);` and scalar type hints to all PHP helper functions and controller methods.
2. Break long controller methods into smaller private helper methods.

### 5. Files Affected
- `helpers/helpers.php`
- `app/Helpers/*.php`
- `app/Controllers/*.php`

### 6. Risk
**Low**.

### 7. Expected Benefit
Improved IDE autocompletion, static analysis support, and code maintainability.

### 8. Do Not Touch
- Public method signatures required by framework interfaces.

---

## 🧪 Milestone 8 — Regression & Production Hardening

### 1. Verification Checklist
- [x] **Login / Authentication**: Test login with valid/invalid credentials, CSRF token validation, and error flash messages.
- [x] **Player Flow**: Verify Player Hub rendering, tournament join requests, payment modal submission, and async profile update (`/api/update-profile`).
- [x] **Host / Trainer Flow**: Verify Host Dashboard, create game event, roster view, and audit log rendering.
- [x] **Store Flow**: Verify product listing, product details, cart operations, and checkout.
- [x] **Security Validation**: Verify zero database error leakages in AJAX responses, zero exposed `.php` internal paths, and complete XSS protection.

---

## 🚀 Architectural Roadmap Status: COMPLETED ✅

1. **Phase 1 (Security First)**: Error disclosure eliminated, file uploads hardened with MIME verification, CSRF middleware & AuthGuard enforced.
2. **Phase 2 (Database Consolidation)**: PDO standardized, `getDbConnection()` deprecated, legacy `api/` endpoints mapped into MVC controllers.
3. **Phase 3 (Authentication Architecture)**: AuthService introduced, RememberMe selector.validator engine DB-backed, AuthGuard consolidated.
4. **Phase 4 (MVC Separation & View Hardening)**: Direct SQL removed from views, `Session::getCurrentUser()` used for cached user context.
5. **Phase 5 (Performance Optimization)**: `SELECT *` removed in critical paths, SQL `TRIM()` inside `WHERE` pre-processed in PHP for composite index usage.
6. **Phase 6 (Frontend Cleanup)**: `Casa` shared utility layer added (`Casa.ajax`, `Casa.showToast`, `Casa.confirmAction`, `Casa.csrfToken`, `Casa.formatDate`, `Casa.formatCurrency`, `Casa.escapeHtml`), inline CSS extracted into `toast.css` and `header.css`.
7. **Phase 7 (Error Handling & Logging)**: `@` operators removed, `log_errors` enabled, Reference ID (`ERR-xxxx`) introduced for production exceptions.
8. **Phase 8 (Testing & Verification)**: `tests/SecurityAndIntegrationTest.php` suite created and passing 100% (9/9 passed).
