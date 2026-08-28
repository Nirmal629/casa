<?php
/**
 * ============================================
 * Common Utility Helper Functions
 * ============================================
 */

/**
 * Sanitize input string
 */
function sanitize($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize an array of inputs
 */
function sanitizeArray(array $data): array
{
    return array_map('sanitize', $data);
}

/**
 * Generate unique booking number
 */
function generateBookingNo(): string
{
    return 'CASA' . date('YmdHis') . rand(100, 999);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') return 'N/A';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Format time
 */
function formatTime($time, $format = 'h:i A'): string
{
    if (empty($time)) return 'N/A';
    $timestamp = strtotime($time);
    return $timestamp ? date($format, $timestamp) : $time;
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'CAD'): string
{
    $symbols = ['CAD' => 'C$', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . ' ' . number_format((float)$amount, 2);
}

/**
 * Get month options for select
 */
function getMonthOptions(): array
{
    return [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
}

/**
 * Get year options for select
 */
function getYearOptions($startYear = null, $endYear = null): array
{
    $startYear = $startYear ?? date('Y');
    $endYear = $endYear ?? date('Y') + 5;
    $years = [];
    for ($y = $startYear; $y <= $endYear; $y++) {
        $years[$y] = $y;
    }
    return $years;
}

/**
 * JavaScript redirect
 */
function jsRedirect($url): void
{
    echo "<script>window.location.href='$url';</script>";
    exit;
}

/**
 * Alert and redirect
 */
function alertAndRedirect($message, $url): void
{
    echo "<script>alert('" . addslashes($message) . "'); window.location.href='$url';</script>";
    exit;
}

/**
 * Upload file to server
 */
function uploadFile($file, $targetDir, $allowedTypes = []): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed: ' . $ext];
    }

    $fileName = time() . '_' . uniqid() . '.' . $ext;
    $targetPath = rtrim($targetDir, '/') . '/' . $fileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'fileName' => $fileName, 'filePath' => $targetPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Debug print
 */
function debugPrint($data, $exit = true): void
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($exit) exit;
}

/**
 * Get current page name
 */
function getCurrentPage(): string
{
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Check if user is logged in (legacy)
 */
function checkLogin(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Execute a database query (MySQLi) - legacy helper
 */
function dbQuery($conn, $sql)
{
    return mysqli_query($conn, $sql);
}

/**
 * Fetch a single row - legacy helper
 */
function dbFetchRow($result)
{
    return mysqli_fetch_assoc($result);
}

/**
 * Fetch all rows - legacy helper
 */
function dbFetchAll($result)
{
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}