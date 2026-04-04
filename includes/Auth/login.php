<?php
session_start();

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database constants
const DATABASE_NAME = 'casa_db';
const USERNAME = "casa_sports";
const PASSWORD = "C@sa_sports24#";

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

                unset($_SESSION['csrf_token']); // refresh CSRF

                // ✅ Use PHP redirect (not JS)
                if ($user['USERTYPE'] === 'Host' || $user['USERTYPE'] === 'Trainer') {
                    header("Location: https://casainfotech.com/host-dashboard.php");
                } elseif ($user['USERTYPE'] === 'Player') {
                    header("Location: https://casainfotech.com/player-dashboard.php");
                } else {
                    header("Location: https://casainfotech.com/trainer-dashboard.php");
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
        <div class="mb-3">
            <input type="password" class="form-control" id="password" name="password"
                placeholder="Enter Password"
                value="<?php echo isset($_COOKIE['remember_password']) ? htmlspecialchars($_COOKIE['remember_password']) : ''; ?>"
                required>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe"
                    <?php if (isset($_COOKIE['remember_username'])) echo 'checked'; ?>>
                <label class="form-check-label" for="rememberMe">Remember me</label>
                <a href="#" class="text-decoration-none float-end">Forgot Password?</a>
            </div>
        </div>

        <button type="submit" class="btn btn-outline-secondary login-btn w-100 mb-3">Login</button>
    </form>

    <p class="text-center register-test mt-3">
        Don't have an account? <a href="#" id="resisterEvent" class="text-decoration-none">Register here</a>
    </p>
</div>
