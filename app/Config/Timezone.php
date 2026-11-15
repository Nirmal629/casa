<?php

declare(strict_types=1);

namespace App\Config;

final class Timezone
{
    public const COOKIE_NAME = 'casa_timezone';
    private static ?string $resolved = null;

    public static function name(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $candidate = trim((string) ($_COOKIE[self::COOKIE_NAME] ?? ''));
        $configured = function_exists('env')
            ? (string) env('APP_TIMEZONE', 'UTC')
            : (string) (getenv('APP_TIMEZONE') ?: 'UTC');

        $resolved = in_array($configured, \DateTimeZone::listIdentifiers(), true) ? $configured : 'UTC';

        if ($candidate !== '' && in_array($candidate, \DateTimeZone::listIdentifiers(), true)) {
            try {
                $pdo = Database::getPdo();
                foreach (['ca_timezone', 'ca_timezones'] as $table) {
                    try {
                        $stmt = $pdo->prepare(
                        "SELECT TIMEZONE_NAME FROM {$table} WHERE TIMEZONE_NAME = ? AND IS_ACTIVE = 1 LIMIT 1"
                        );
                        $stmt->execute([$candidate]);
                        if ($stmt->fetchColumn()) {
                            $resolved = $candidate;
                            break;
                        }
                    } catch (\Throwable) {
                        continue;
                    }
                }
            } catch (\Throwable) {
                // Keep APP_TIMEZONE when the timezone master table is unavailable.
            }
        }

        return self::$resolved = $resolved;
    }

    public static function zone(): \DateTimeZone
    {
        return new \DateTimeZone(self::name());
    }

    public static function configuredZone(): \DateTimeZone
    {
        $configured = function_exists('env')
            ? (string) env('APP_TIMEZONE', 'UTC')
            : (string) (getenv('APP_TIMEZONE') ?: 'UTC');
        if (!in_array($configured, \DateTimeZone::listIdentifiers(), true)) {
            $configured = 'UTC';
        }
        return new \DateTimeZone($configured);
    }

    public static function formatEvent(string $date, string $time = '', ?string $eventTimezone = null, string $format = 'D, d M Y, h:i A'): string
    {
        if (trim($date) === '') {
            return '';
        }
        try {
            $sourceName = $eventTimezone && in_array($eventTimezone, \DateTimeZone::listIdentifiers(), true)
                ? $eventTimezone
                : self::configuredZone()->getName();
            $value = new \DateTimeImmutable(trim($date . ' ' . ($time ?: '00:00:00')), new \DateTimeZone($sourceName));
            return $value->setTimezone(self::zone())->format($format);
        } catch (\Throwable) {
            return '';
        }
    }

    public static function formatEventEnd(string $date, string $startTime, string $endTime, ?string $eventTimezone = null, string $format = 'h:i A'): string
    {
        if (trim($date) === '' || trim($endTime) === '') {
            return '';
        }
        try {
            $sourceName = $eventTimezone && in_array($eventTimezone, \DateTimeZone::listIdentifiers(), true)
                ? $eventTimezone
                : self::configuredZone()->getName();
            $zone = new \DateTimeZone($sourceName);
            $start = new \DateTimeImmutable("$date $startTime", $zone);
            $end = new \DateTimeImmutable("$date $endTime", $zone);
            if ($end < $start) {
                $end = $end->modify('+1 day');
            }
            return $end->setTimezone(self::zone())->format($format);
        } catch (\Throwable) {
            return '';
        }
    }
}
