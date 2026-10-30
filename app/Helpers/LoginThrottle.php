<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Server-side atomic rate limiter and throttle storage.
 * Supports IP request budgets, failed attempt tracking, and atomic counter reset.
 */
final class LoginThrottle
{
    /**
     * Resolve default storage directory for throttles.
     */
    public static function defaultDirectory(): string
    {
        return sys_get_temp_dir() . '/casa-login-' . substr(hash('sha256', __DIR__), 0, 16);
    }

    /**
     * Consume an attempt in a fixed window.
     * Increments on check and returns retry-after seconds if limit is reached, or 0 if allowed.
     * Preserves backward compatibility with existing tests and callers.
     */
    public static function consume(
        string $key,
        int $limit = 5,
        int $window = 900,
        ?string $directory = null,
        ?int $now = null
    ): int {
        $now ??= time();
        $limit = max(1, $limit);
        $window = max(1, $window);
        $directory ??= self::defaultDirectory();

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Login protection storage unavailable.');
        }

        $file = @fopen($directory . '/' . hash('sha256', $key) . '.json', 'c+');
        if (!$file) {
            throw new \RuntimeException('Login protection storage unavailable.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new \RuntimeException('Login protection lock unavailable.');
            }

            $content = stream_get_contents($file);
            $state = is_string($content) && $content !== '' ? json_decode($content, true) : null;

            if (!is_array($state) || !isset($state['until'], $state['attempts']) || $now >= (int) $state['until']) {
                $state = ['until' => $now + $window, 'attempts' => 0];
            }

            if ((int) $state['attempts'] >= $limit) {
                return max(1, (int) $state['until'] - $now);
            }

            $state['attempts'] = ((int) $state['attempts']) + 1;
            rewind($file);

            if (!ftruncate($file, 0) || fwrite($file, json_encode($state, JSON_THROW_ON_ERROR)) === false || !fflush($file)) {
                throw new \RuntimeException('Login protection storage unavailable.');
            }

            return 0;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    /**
     * Check if a key is currently locked out without mutating the attempt counter.
     * Returns remaining lockout seconds (> 0) if locked out, or 0 if allowed.
     */
    public static function check(
        string $key,
        int $limit = 5,
        ?string $directory = null,
        ?int $now = null
    ): int {
        $now ??= time();
        $limit = max(1, $limit);
        $directory ??= self::defaultDirectory();
        $filePath = $directory . '/' . hash('sha256', $key) . '.json';

        if (!file_exists($filePath)) {
            return 0;
        }

        $file = @fopen($filePath, 'r');
        if (!$file) {
            return 0;
        }

        try {
            if (!flock($file, LOCK_SH)) {
                return 0;
            }

            $content = stream_get_contents($file);
            if (!is_string($content) || $content === '') {
                return 0;
            }

            $state = json_decode($content, true);
            if (!is_array($state) || !isset($state['until'], $state['attempts'])) {
                return 0;
            }

            if ($now >= (int) $state['until']) {
                return 0;
            }

            if ((int) $state['attempts'] >= $limit) {
                return max(1, (int) $state['until'] - $now);
            }

            return 0;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    /**
     * Increment the failed attempt counter for a key.
     * Returns remaining lockout seconds (> 0) if threshold was reached/exceeded, or 0 if still within limit.
     */
    public static function increment(
        string $key,
        int $limit = 5,
        int $window = 300,
        ?string $directory = null,
        ?int $now = null
    ): int {
        $now ??= time();
        $limit = max(1, $limit);
        $window = max(1, $window);
        $directory ??= self::defaultDirectory();

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Login protection storage unavailable.');
        }

        $file = @fopen($directory . '/' . hash('sha256', $key) . '.json', 'c+');
        if (!$file) {
            throw new \RuntimeException('Login protection storage unavailable.');
        }

        try {
            if (!flock($file, LOCK_EX)) {
                throw new \RuntimeException('Login protection lock unavailable.');
            }

            $content = stream_get_contents($file);
            $state = is_string($content) && $content !== '' ? json_decode($content, true) : null;

            if (!is_array($state) || !isset($state['until'], $state['attempts']) || $now >= (int) $state['until']) {
                $state = ['until' => $now + $window, 'attempts' => 0];
            }

            $state['attempts'] = ((int) $state['attempts']) + 1;
            rewind($file);

            if (!ftruncate($file, 0) || fwrite($file, json_encode($state, JSON_THROW_ON_ERROR)) === false || !fflush($file)) {
                throw new \RuntimeException('Login protection storage unavailable.');
            }

            if ((int) $state['attempts'] >= $limit) {
                return max(1, (int) $state['until'] - $now);
            }

            return 0;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    /**
     * Atomically clear/reset attempts for a key (e.g. upon successful authentication).
     */
    public static function clear(string $key, ?string $directory = null): bool
    {
        $directory ??= self::defaultDirectory();
        $filePath = $directory . '/' . hash('sha256', $key) . '.json';

        if (!file_exists($filePath)) {
            return true;
        }

        $file = @fopen($filePath, 'c+');
        if (!$file) {
            return @unlink($filePath);
        }

        try {
            if (flock($file, LOCK_EX)) {
                ftruncate($file, 0);
                fwrite($file, json_encode(['until' => 0, 'attempts' => 0], JSON_THROW_ON_ERROR));
                fflush($file);
            }
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }

        @unlink($filePath);
        return true;
    }

    /**
     * Alias for clear().
     */
    public static function reset(string $key, ?string $directory = null): bool
    {
        return self::clear($key, $directory);
    }

    /**
     * Get the current attempt count for a key.
     */
    public static function attempts(string $key, ?string $directory = null, ?int $now = null): int
    {
        $now ??= time();
        $directory ??= self::defaultDirectory();
        $filePath = $directory . '/' . hash('sha256', $key) . '.json';

        if (!file_exists($filePath)) {
            return 0;
        }

        $file = @fopen($filePath, 'r');
        if (!$file) {
            return 0;
        }

        try {
            if (!flock($file, LOCK_SH)) {
                return 0;
            }

            $content = stream_get_contents($file);
            if (!is_string($content) || $content === '') {
                return 0;
            }

            $state = json_decode($content, true);
            if (!is_array($state) || !isset($state['until'], $state['attempts'])) {
                return 0;
            }

            if ($now >= (int) $state['until']) {
                return 0;
            }

            return (int) $state['attempts'];
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    /**
     * Get remaining attempts before lockout.
     */
    public static function remaining(
        string $key,
        int $limit = 5,
        ?string $directory = null,
        ?int $now = null
    ): int {
        $attempts = self::attempts($key, $directory, $now);
        return max(0, $limit - $attempts);
    }
}
