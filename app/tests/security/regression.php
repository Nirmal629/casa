<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Isolated tests: no .env read, network or real database connection.
$root = dirname(__DIR__, 2);
$settings = ['APP_SECRET' => str_repeat('test-only-', 5)];
function env(string $key, mixed $default = null): mixed { return $GLOBALS['settings'][$key] ?? $default; }
spl_autoload_register(function(string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) {
        $file = $root . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    }
});
use App\Helpers\Session;
use App\Helpers\RememberMe;
use App\Helpers\WebAccess;
use App\Core\Token;
use App\Middleware\AuthGuard;
use App\Controllers\AuthController;

$results = [];
function check(bool $condition, string $name): void {
    $GLOBALS['results'][] = ['test' => $name, 'passed' => $condition];
}
$runtime = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'casa-security-regression';
if (!is_dir($runtime)) mkdir($runtime, 0700, true);
$sessionDir = $runtime . '/sessions-' . bin2hex(random_bytes(8));
mkdir($sessionDir, 0700);
session_save_path($sessionDir);
Session::start();
check(ini_get('session.use_strict_mode') === '1', 'strict session mode');
check(ini_get('session.use_only_cookies') === '1' && ini_get('session.use_trans_sid') === '0', 'session IDs cannot travel in URLs');
$cookie = session_get_cookie_params();
check($cookie['httponly'] && $cookie['samesite'] === 'Lax', 'session cookie HttpOnly and SameSite');
foreach ([0, -1, 'garbage', [], null] as $bad) {
    $_SESSION['user_id'] = $bad;
    check(Session::getCurrentUserId() === null, 'reject invalid user id ' . get_debug_type($bad));
}
unset($_SESSION['user_id']);
$csrf = Session::getCsrfToken();
check(strlen($csrf) === 64 && Session::verifyCsrfToken($csrf), 'valid CSRF accepted');
foreach ([null, '', 'wrong', [], 12, new stdClass()] as $bad) check(!Session::verifyCsrfToken($bad), 'malformed CSRF rejected: ' . get_debug_type($bad));
$before = session_id();
$_SESSION['role'] = 'old-admin';
Session::establishUser(['ID' => 7, 'USERTYPE' => 'Player', 'EMAIL' => 'fixture@example.invalid']);
check(session_id() !== $before, 'login rotates session identifier');
check(!isset($_SESSION['role']) && Session::userId() === 7, 'login clears old privileges');
check(!Session::verifyCsrfToken($csrf), 'login rotates CSRF token');
check(Session::userType() === 'Player', 'legacy userType alias exists');
$_SESSION['_last_activity'] = time() - 1801;
Session::start();
check(Session::userId() === null, 'idle session expires');
Session::establishUser(['ID' => 7]);
$_SESSION['_authenticated_at'] = time() - 43201;
Session::start();
check(Session::userId() === null, 'absolute session expires');

$auth = (new ReflectionClass(AuthController::class))->newInstanceWithoutConstructor();
$hash = password_hash('correct-password', PASSWORD_DEFAULT);
check($auth->verifyPassword('correct-password', ['PASSWORD_HASH' => $hash, 'PASSWORD' => 'old-password']), 'modern password accepted');
check(!$auth->verifyPassword('old-password', ['PASSWORD_HASH' => $hash, 'PASSWORD' => 'old-password']), 'stale legacy password cannot bypass modern hash');
check(!$auth->verifyPassword($hash, ['PASSWORD' => $hash]), 'stored hash cannot be used as plaintext password');
check($auth->verifyPassword('correct-password', ['PASSWORD' => $hash]), 'legacy hashed password verifies');
check($auth->verifyPassword('legacy-password', ['PASSWORD' => 'legacy-password']), 'legacy plaintext migration compatibility');
check(!$auth->verifyPassword('legacy-password ', ['PASSWORD' => 'legacy-password']), 'password whitespace preserved');

$token = Token::generate(['id' => 7], 60);
check((Token::verify($token)['id'] ?? null) === 7, 'valid signed token');
check(Token::verify($token . 'x') === null, 'tampered signature rejected');
check(Token::verify(Token::generate(['id' => 7], -1)) === null, 'expired token rejected');
check(Token::verify(Token::generate(['id' => 7], 0)) === null, 'token expires exactly at boundary');
foreach (['', '.', 'bad.bad', 'a.b.c'] as $bad) check(Token::verify($bad) === null, 'malformed signed token rejected');
$settings['APP_SECRET'] = '';
try { Token::generate(['id' => 7]); check(false, 'missing signing secret fails closed'); }
catch (RuntimeException $e) { check(true, 'missing signing secret fails closed'); }
$settings['APP_SECRET'] = str_repeat('test-only-', 5);

class SecurityFakePDO extends PDO {
    public ?array $user = ['ID' => 7, 'USERTYPE' => 'Player', 'LEVEL' => 'B'];
    public ?array $token = null;
    public bool $fail = false;
    public array $lastParams = [];
    public string $lastQuery = "";
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if ($this->fail) throw new RuntimeException('fixture unavailable');
        return new SecurityFakeStatement($this, $query);
    }
}
class SecurityFakeStatement extends PDOStatement {
    private int $affected = 0;
    public function __construct(private SecurityFakePDO $db, private string $query) {}
    public function execute(?array $params = null): bool {
        $this->db->lastParams = $params ?? []; $this->db->lastQuery = $this->query;
        if (str_contains($this->query, 'SET validator_hash')) {
            if ($this->db->token && $this->db->token['validator_hash'] === $params[':old_hash']) {
                $this->db->token['validator_hash'] = $params[':new_hash']; $this->affected = 1;
            }
        }
        if (str_contains($this->query, 'SET revoked_at')) $this->db->token = null;
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return []; }
    public function rowCount(): int { return $this->affected; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
        return (str_contains($this->query, 'FROM ca_remember_tokens') ? $this->db->token : $this->db->user) ?: false;
    }
}
$db = new SecurityFakePDO();
(new ReflectionProperty(App\Config\Database::class, 'pdo'))->setValue(null, $db);
Session::establishUser(['ID' => 7, 'USERTYPE' => 'Host']);
check(AuthGuard::check() && Session::userType() === 'Player', 'guard refreshes stale role from authoritative record');
$db->user = null;
check(!AuthGuard::check() && Session::userId() === null, 'deleted or disabled account loses authentication');
$db->user = ['ID' => 7, 'USERTYPE' => 'Player'];
Session::establishUser($db->user);
$db->fail = true;
check(!AuthGuard::check(), 'database failure denies authentication');
$db->fail = false;
$_SESSION = [];
$selector = str_repeat('a', 24); $validator = str_repeat('b', 64);
$db->token = ['id' => 1, 'user_id' => 7, 'validator_hash' => hash('sha256', $validator), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)];
$_COOKIE[RememberMe::getCookieName()] = $selector . '.' . $validator;
check(RememberMe::verify() === 7, 'valid remember token accepted');
check($db->token['validator_hash'] !== hash('sha256', $validator), 'remember validator rotates after use');
check(RememberMe::verify() === null, 'replayed remember validator rejected');
$_COOKIE[RememberMe::getCookieName()] = [];
check(RememberMe::verify() === null, 'array remember cookie rejected without exception');
RememberMe::revokeCurrent();
foreach (['/.env', '/.env.production', '/.git/config', '/sql/dump.sql', '/APP_FULL_CODE.md', '/pos/admin/writable/session/ci_session123', '/app/Views/host/host-dashboard.php', '/uploads/shell.php', '/profile_img/shell.phtml', '/../.env', '/%2eenv'] as $path) check(WebAccess::blocked($path), 'deny private path ' . $path);
foreach (['/assets/css/style.css','/app/script/common.js','/app/Views/css/sports-session-card.css','/api/remove_member.php','/player/player-hub.php'] as $path) check(!WebAccess::blocked($path), 'allow public route or asset ' . $path);
$hostController = (new ReflectionClass(App\Controllers\HostController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(App\Controllers\HostController::class, 'db'))->setValue($hostController, $db);
$_REQUEST = ['host_id' => 999, 'player_id' => 888];
Session::establishUser(['ID' => 7, 'USERTYPE' => 'Host']);
ob_start(); $hostController->getHostPaymentList(); ob_end_clean();
check(($db->lastParams[':host_id1'] ?? null) === 7 && ($db->lastParams[':host_id2'] ?? null) === 7, 'host cannot request another host payment listing');
Session::establishUser(['ID' => 7, 'USERTYPE' => 'Player']);
ob_start(); $hostController->getHostPaymentList(); ob_end_clean();
check(($db->lastParams[':player_id'] ?? null) === 7, 'player payment listing always scoped to self');
$_REQUEST = [];
foreach (['/tests/security/regression.php','/admin/assets/images/player-hub.php','/tournament/[11-07-2026]badminton-scorer.php','/tournament/~~backup.php'] as $path) check(WebAccess::blocked($path), 'deny backup or development executable ' . $path);
$throttleDir = $sessionDir . '/throttle';
check(App\Helpers\LoginThrottle::consume('fixture-ip', 2, 60, $throttleDir, 1000) === 0, 'first login attempt allowed');
check(App\Helpers\LoginThrottle::consume('fixture-ip', 2, 60, $throttleDir, 1001) === 0, 'second login attempt allowed');
$_SESSION = [];
check(App\Helpers\LoginThrottle::consume('fixture-ip', 2, 60, $throttleDir, 1002) === 58, 'new session cannot bypass login throttle');
check(App\Helpers\LoginThrottle::consume('other-ip', 2, 60, $throttleDir, 1002) === 0, 'login throttle keys isolated');
check(App\Helpers\LoginThrottle::consume('fixture-ip', 2, 60, $throttleDir, 1060) === 0, 'login throttle expiry boundary');
foreach (glob($throttleDir . '/*') as $file) unlink($file);
rmdir($throttleDir);
Session::logout();
check(session_status() === PHP_SESSION_NONE && $_SESSION === [], 'logout clears and destroys session');
foreach (glob($sessionDir . '/*') as $file) unlink($file);
rmdir($sessionDir);
$failed = count(array_filter($results, fn($r) => !$r['passed']));
echo json_encode(['total' => count($results), 'failed' => $failed, 'tests' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit($failed ? 1 : 0);
