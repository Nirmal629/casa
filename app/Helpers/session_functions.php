<?php

declare(strict_types=1);

use App\Helpers\Session;

if (!function_exists('startSession'))           { function startSession(): void { Session::start(); } }
if (!function_exists('getCurrentUser'))         { function getCurrentUser($conn = null): ?array { return Session::getCurrentUser($conn); } }
if (!function_exists('getCurrentUserId'))       { function getCurrentUserId(): ?int { return Session::getCurrentUserId(); } }
if (!function_exists('getCurrentUserType'))     { function getCurrentUserType(): ?string { return Session::getCurrentUserType(); } }
if (!function_exists('requireLogin'))           { function requireLogin(string $redirect = 'index.php'): void { Session::requireLogin($redirect); } }
if (!function_exists('requireUserType'))        { function requireUserType(array $allowedTypes, string $redirect = 'index.php'): void { Session::requireUserType($allowedTypes, $redirect); } }
if (!function_exists('logoutUser'))             { function logoutUser(): void { Session::logout(); } }
if (!function_exists('normalizeRole'))          { function normalizeRole(?string $rawRole): string { return Session::normalizeRole($rawRole); } }
if (!function_exists('normalizeUserData'))      { function normalizeUserData(array $user): object { return Session::normalizeUserData($user); } }
if (!function_exists('getUserInitials'))        { function getUserInitials(string $userName): string { return Session::getUserInitials($userName); } }
if (!function_exists('getCsrfToken'))           { function getCsrfToken(): string { return Session::getCsrfToken(); } }
if (!function_exists('verifyCsrfToken'))        { function verifyCsrfToken(mixed $token): bool { return Session::verifyCsrfToken($token); } }
if (!function_exists('canViewProductListing')) { function canViewProductListing(): bool { return Session::canViewProductListing(); } }
if (!function_exists('checkRateLimit'))         { function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool { return Session::checkRateLimit($key, $maxAttempts, $decaySeconds); } }
if (!function_exists('clearRateLimit'))         { function clearRateLimit(string $key): void { Session::clearRateLimit($key); } }
