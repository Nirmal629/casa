<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/Config/env.php';
require_once __DIR__ . '/../app/Helpers/Session.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require_once $file;
    }
});
\App\Helpers\Session::start();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function apiSecurityDeny(int $code, string $message): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'status' => 'error', 'message' => $message]);
    exit;
}
function requireApiUser(array $roles = []): int {
    if (!\App\Helpers\Session::getCurrentUserId()) apiSecurityDeny(401, 'Authentication required.');
    if (!\App\Middleware\AuthGuard::check()) apiSecurityDeny(401, 'Authentication required.');
    $role = strtolower(trim((string) ($_SESSION['usertype'] ?? '')));
    if ($roles && !in_array($role, array_map('strtolower', $roles), true)) apiSecurityDeny(403, 'Forbidden.');
    \App\Middleware\CsrfGuard::handle();
    return (int) $_SESSION['user_id'];
}
function requireApiPost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') apiSecurityDeny(405, 'POST required.');
}
function requireOwnedEvent(mysqli $conn, mixed $eventId): int {
    $id = filter_var($eventId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) apiSecurityDeny(400, 'Invalid event.');
    $stmt = $conn->prepare('SELECT HOST_ID FROM ca_events WHERE ID = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || (int) $row['HOST_ID'] !== (int) $_SESSION['user_id']) apiSecurityDeny(403, 'Forbidden event.');
    return $id;
}
