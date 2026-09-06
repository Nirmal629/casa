<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database constants (guarded so they don't collide with config/database.php when init.php is already loaded)
if (!defined('DATABASE_NAME')) define('DATABASE_NAME', 'casa_test');
if (!defined('USERNAME'))      define('USERNAME', 'casa_test');
if (!defined('PASSWORD'))      define('PASSWORD', 'casa_test123#');

$host = "localhost";
$conn = new mysqli($host, USERNAME, PASSWORD, DATABASE_NAME);
if ($conn->connect_error) {
    die("Connection failed.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security validation failed.");
    }

    $USERNAME = strtolower(trim($_POST['username']));
    $PASSWORD = trim($_POST['password']);

    if ($USERNAME && $PASSWORD) {
        $stmt = $conn->prepare("SELECT * FROM ca_users WHERE EMAIL = ? AND LOG_STATUS = 'Y' AND DEL_STATUS = 'N'");
        $stmt->bind_param("s", $USERNAME);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($PASSWORD === trim($user['PASSWORD'])) {
                // ✅ Remember Me cookies
                if (!empty($_POST['rememberMe'])) {
                    setcookie("remember_username", $USERNAME, time() + (86400 * 30), "/", "", false, true);
                    setcookie("remember_password", $PASSWORD, time() + (86400 * 30), "/", "", false, true);
                } else {
                    setcookie("remember_username", "", time() - 3600, "/");
                    setcookie("remember_password", "", time() - 3600, "/");
                }

                // ✅ Set session
                $_SESSION['user_id'] = $user['ID'];
                $_SESSION['name'] = $user['NAME'];
                $_SESSION['username'] = $USERNAME;
                $_SESSION['usertype'] = $user['USERTYPE'];
                $_SESSION['gender'] = $user['GENDER'];
                $_SESSION['vlevel'] = $user['VERIFIED_LEVEL'];
                $_SESSION['level'] = $user['LEVEL'];
                $_SESSION['country'] = $user['COUNTRY'];
                $_SESSION['province'] = $user['PROVINCE'];
                $_SESSION['city'] = $user['CITY'];
                $_SESSION['games'] = $user['GAMES'];
                $_SESSION['profileImage'] = $user['PROFILE_IMAGE'] ?? '';

                unset($_SESSION['csrf_token']); // refresh CSRF

                // ✅ Log Activity
                mysqli_query($conn, "INSERT INTO `ca_player_logs` (`USER_ID`, `ACTIVITY_TYPE`, `DESCRIPTION`) VALUES ('".$user['ID']."', 'LOGIN', 'Successful login')");

                // ✅ Use PHP redirect (not JS)
                if ($user['USERTYPE'] === 'Host' || $user['USERTYPE'] === 'Trainer') {
                    header("Location: host-dashboard.php");
                } elseif ($user['USERTYPE'] === 'Player') {
                    // Auto-sync player with matching Home Clubs
                    require_once __DIR__ . '/../../api/sync_home_clubs.php';
                    syncPlayerToHomeClubs($conn, $user['ID'], $user['COUNTRY'], $user['PROVINCE'], $user['CITY'], $user['GAMES']);

                    // Always redirect players to the hub
                    header("Location: player-hub.php");
                } else {
                    header("Location: trainer-dashboard.php");
                }
                exit;
            } else {
                echo "<script>alert('Invalid username or password.');</script>";
            }
        } else {
            echo "<script>alert('Invalid credentials or inactive account.');</script>";
        }
    } else {
        echo "<script>alert('Please enter both username and password.');</script>";
    }
}
?>

<!-- Login Form -->
<div class="form-2-wrapper">
    <h2 class="text-center mb-4">Sign Into Your Account</h2>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="mb-3 form-box">
            <input type="text" class="form-control" id="username" name="username"
                placeholder="Enter User Name or Email"
                value="<?php echo isset($_COOKIE['remember_username']) ? htmlspecialchars($_COOKIE['remember_username']) : ''; ?>"
                required>
        </div>
        <div class="mb-3 position-relative password-field">
            <input type="password" class="form-control" name="password"
                placeholder="Enter Password"
                value="<?php echo isset($_COOKIE['remember_password']) ? htmlspecialchars($_COOKIE['remember_password']) : ''; ?>"
                required>
            <button type="button" class="btn p-0 border-0 toggle-password-btn" onclick="toggleCasaPassword(this); return false;" aria-label="Toggle password visibility">
                <i class="fa-solid fa-eye password-eye-icon"></i>
            </button>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe"
                    <?php if (isset($_COOKIE['remember_username'])) echo 'checked'; ?>>
                <label class="form-check-label" for="rememberMe">Remember me</label>
                <button type="button" class="btn float-end fw-bold p-0 m-0" style="color: #043a63; font-size: 90%; text-decoration: underline;" 
                data-bs-toggle="modal" data-bs-target="#forgotpwModal">Forgot Password?</button>
            </div>
        </div>

        <button type="submit" class="btn btn-outline-secondary login-btn w-100 mb-3">Login</button>
    </form>

    <p class="text-center register-test mt-3">
        Don't have an account? <a href="javascript:void(0)" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#registerRequestModal">Register here</a>
    </p>
</div>

<style>
.form-2-wrapper .password-field .form-control {
    padding-right: 46px;
}
.form-2-wrapper .toggle-password-btn {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #94a3b8;
    z-index: 30;
    cursor: pointer;
    line-height: 1;
    border-radius: 50%;
    outline: none;
    box-shadow: none;
    transition: background-color .25s ease, color .25s ease, transform .15s ease;
}
.form-2-wrapper .toggle-password-btn:hover {
    background: rgba(4, 58, 99, 0.08);
    color: #043a63;
}
.form-2-wrapper .toggle-password-btn:active {
    transform: translateY(-50%) scale(0.88);
}
.form-2-wrapper .toggle-password-btn:focus-visible {
    outline: 2px solid #043a63;
    outline-offset: 2px;
}
.form-2-wrapper .toggle-password-btn .password-eye-icon {
    font-size: 15px;
    pointer-events: none;
    transition: color .25s ease;
}
.form-2-wrapper .toggle-password-btn .fa-eye-slash {
    color: #043a63;
}
.form-2-wrapper .toggle-password-btn.is-toggling .password-eye-icon {
    animation: casaEyePop .3s ease;
}
@keyframes casaEyePop {
    0% { transform: scale(0.6) rotate(-15deg); opacity: 0.4; }
    60% { transform: scale(1.15) rotate(5deg); opacity: 1; }
    100% { transform: scale(1) rotate(0deg); }
}
</style>

<script>
window.toggleCasaPassword = window.toggleCasaPassword || function(btn) {
    if (!btn) return;
    var container = btn.closest('.position-relative') || btn.parentElement;
    if (!container) return;
    var input = container.querySelector('input[name="password"], input[type="password"], input[type="text"]');
    var icon = btn.querySelector('.password-eye-icon') || btn.querySelector('i');
    if (!input) return;

    if (input.getAttribute('type') === 'password') {
        input.setAttribute('type', 'text');
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.setAttribute('type', 'password');
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    btn.classList.remove('is-toggling');
    void btn.offsetWidth; // restart animation
    btn.classList.add('is-toggling');
};
</script>

