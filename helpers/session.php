<?php
/**
 * ============================================
 * Session Management Helper
 * ============================================
 * Provides session start, auth check, and user utilities.
 * ============================================
 */

/**
 * Start session if not already started
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Get current logged-in user data
 * @param mysqli $conn
 * @return array|null
 */
function getCurrentUser($conn): ?array
{
    startSession();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $id = (int)$_SESSION['user_id'];
    $sql = "SELECT * FROM ca_users WHERE ID = $id AND DEL_STATUS = 'N' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Require user to be logged in, redirect if not
 * @param string $redirect
 */
function requireLogin($redirect = 'index.php'): void
{
    startSession();
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require specific user type(s), redirect if not
 * @param array $allowedTypes
 * @param string $redirect
 */
function requireUserType(array $allowedTypes, $redirect = 'index.php'): void
{
    requireLogin($redirect);
    if (!in_array($_SESSION['usertype'] ?? '', $allowedTypes, true)) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId(): ?int
{
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current user type
 * @return string|null
 */
function getCurrentUserType(): ?string
{
    startSession();
    return $_SESSION['usertype'] ?? null;
}

/**
 * Logout current user
 */
function logoutUser(): void
{
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}