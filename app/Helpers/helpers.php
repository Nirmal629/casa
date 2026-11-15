<?php
// Central Utility Helper Functions
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/session_functions.php';

// Global HTML escaping helper
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Generate unique secure booking number
function generateBookingNo(): string
{
    try {
        $random = strtoupper(bin2hex(random_bytes(3)));
    } catch (\Throwable $e) {
        $random = strtoupper(substr(uniqid(), -6));
    }
    return 'CASA' . date('YmdHis') . $random;
}

// Format date
function formatDate($date, string $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    $timestamp = strtotime((string) $date);
    return $timestamp ? date($format, $timestamp) : (string) $date;
}

// Format time
function formatTime($time, string $format = 'h:i A'): string
{
    if (empty($time)) {
        return 'N/A';
    }
    $timestamp = strtotime((string) $time);
    return $timestamp ? date($format, $timestamp) : (string) $time;
}

// Format currency
function formatCurrency($amount, string $currency = 'CAD'): string
{
    $symbols = ['CAD' => 'C$', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . ' ' . number_format((float) $amount, 2);
}

if (!function_exists('formatShortName')) {
    /**
     * Format a display name into "First L." format or "First" if single word.
     */
    function formatShortName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }
        $parts = preg_split('/\s+/u', $trimmed);
        $count = \is_array($parts) ? \count($parts) : 0;
        if ($count > 1) {
            $last = end($parts);
            $lastInitial = function_exists('mb_substr') && function_exists('mb_strtoupper')
                ? mb_strtoupper(mb_substr($last, 0, 1, 'UTF-8'), 'UTF-8')
                : strtoupper(substr($last, 0, 1));
            return "{$parts[0]} {$lastInitial}.";
        }
        return $parts[0] ?? '';
    }
}

// Calendar and event period functions are located in app/Helpers/calendar_helper.php
require_once __DIR__ . '/calendar_helper.php';

// Secure file upload handler
function uploadFile(array $file, string $targetDir, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'], int $maxSizeBytes = 5242880): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . ($file['error'] ?? 'unknown')];
    }

    if (!empty($file['size']) && (int) $file['size'] > $maxSizeBytes) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed limit of 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    // Blacklist executable extensions
    $forbidden = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'htaccess', 'exe', 'pl', 'cgi', 'sh', 'bat', 'cmd', 'js'];
    if (in_array($ext, $forbidden, true)) {
        return ['success' => false, 'message' => 'File type is not permitted.'];
    }

    $allowedExtensions = array_map('strtolower', $allowedExtensions);

    if (!empty($allowedExtensions) && !in_array($ext, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'File extension not allowed: ' . $ext];
    }

    // Validate MIME type for additional security
    if (!empty($file['tmp_name'])) {
        $mime = null;
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = @mime_content_type($file['tmp_name']);
        }

        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];

        if ($mime && isset($allowedMimes[$ext]) && $mime !== $allowedMimes[$ext]) {
            return ['success' => false, 'message' => 'Invalid file MIME type.'];
        }
    }

    try {
        $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    } catch (\Throwable) {
        $fileName = date('Ymd_His') . '_' . uniqid('', true) . '.' . $ext;
    }

    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.'];
        }
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'fileName' => $fileName, 'filePath' => $targetPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

// Debug print helper
function debugPrint($data, bool $exit = true): void
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($exit) {
        exit;
    }
}

// Get current page script name
function getCurrentPage(): string
{
    return basename($_SERVER['PHP_SELF'] ?? '');
}

if (!function_exists('renderToast')) {
    function renderToast(?array $toast): string
    {
        if (empty($toast) || empty($toast['message'])) {
            return '';
        }

        $type = json_encode((string) ($toast['type'] ?? 'info'));
        $msg = json_encode((string) ($toast['message'] ?? ''));
        $redirect = !empty($toast['redirect']) ? json_encode((string) $toast['redirect']) : 'null';

        return "<script>
(function() {
    function fireToast() {
        var type = {$type};
        var msg = {$msg};
        var redirect = {$redirect};
        if (typeof window.showToast === 'function') {
            window.showToast(type, msg);
        } else if (window.Casa && typeof window.Casa.showToast === 'function') {
            window.Casa.showToast(msg, type);
        } else if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
            toastr[type](msg);
        }
        if (redirect) {
            setTimeout(function() {
                window.location.href = redirect;
            }, 1200);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireToast);
    } else {
        fireToast();
    }
})();
</script>";
    }
}

if (!function_exists('setFlashToast')) {
    // Store a toast notification in session flash data.
    function setFlashToast(string $type, string $message, ?string $redirect = null): void
    {
        \App\Helpers\Session::setFlash('flash_toast', [
            'type' => $type,
            'message' => $message,
            'redirect' => $redirect
        ]);
    }
}

if (!function_exists('renderFlashToast')) {
    // Read and render any pending flash toast from session.
    function renderFlashToast(): string
    {
        $toast = \App\Helpers\Session::getFlash('flash_toast');
        return is_array($toast) ? renderToast($toast) : '';
    }
}
