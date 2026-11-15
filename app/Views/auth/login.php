<?php

declare(strict_types=1);

$csrfToken = $_SESSION['csrf_token'] ?? '';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$rememberCookieKey = (function_exists('env') ? env('APP_SESSION_NAME', 'CASA_STAGING') : 'CASA_STAGING') . '_REMEMBER_USER';
$rememberUsername = $_COOKIE[$rememberCookieKey] ?? $_COOKIE['remember_username'] ?? '';

?>

<!-- Login Form -->
<div class="form-2-wrapper">

    <h2 class="text-center mb-4">
        Sign Into Your Account
    </h2>

    <?php if (!empty($loginError)): ?>
        <div
            class="alert alert-danger text-center mb-3 login-error-alert"
            role="alert"
        >
            <i class="fa-solid fa-circle-exclamation me-1"></i>
            <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login">

        <!-- CSRF Protection -->
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
        >

        <!-- Username / Email -->
        <div class="mb-3 form-box">
            <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                placeholder="Enter User Name or Email"
                value="<?= htmlspecialchars($rememberUsername, ENT_QUOTES, 'UTF-8') ?>"
                required
                autocomplete="username"
            >
        </div>

        <!-- Password -->
        <div class="mb-3 position-relative password-field">
            <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="Enter Password"
                required
                autocomplete="current-password"
            >

            <button
                type="button"
                class="btn p-0 border-0 toggle-password-btn"
                aria-label="Show password"
                aria-pressed="false"
            >
                <i class="fa-solid fa-eye password-eye-icon"></i>
            </button>
        </div>

        <!-- Login Options -->
        <div class="mb-3">
            <div class="form-check">

                <input
                    type="checkbox"
                    class="form-check-input"
                    id="rememberMe"
                    name="rememberMe"
                    <?= $rememberUsername !== '' ? 'checked' : '' ?>
                >

                <label
                    class="form-check-label"
                    for="rememberMe"
                >
                    Remember me
                </label>

                <button
                    type="button"
                    class="btn float-end fw-bold p-0 m-0 forgot-pw-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#forgotpwModal"
                >
                    Forgot Password?
                </button>

            </div>
        </div>

        <!-- Login Button -->
        <button
            type="submit"
            class="btn btn-outline-secondary login-btn w-100 mb-3"
        >
            Login
        </button>

    </form>

    <!-- Registration -->
    <p class="text-center register-test mt-3">
        Don't have an account?

        <button
            type="button"
            class="btn btn-link p-0 text-decoration-none register-modal-btn"
            data-bs-toggle="modal"
            data-bs-target="#registerRequestModal"
        >
            Register here
        </button>
    </p>

</div>

<!-- Page Assets -->
<link
    rel="stylesheet"
    href="/app/Views/css/auth/login.css"
>

<script
    src="/app/script/auth.js"
    defer
></script>
