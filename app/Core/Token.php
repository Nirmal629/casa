<?php

declare(strict_types=1);

namespace App\Core;

// HMAC-signed token. Format: base64(json_payload).hmac_sha256(payload, APP_SECRET)
class Token
{
    private static function secret(): string
    {
        $secret = (string) env('APP_SECRET', '');
        if (strlen($secret) < 32 || $secret === 'fallback_secret_change_me') {
            throw new \RuntimeException('APP_SECRET must contain at least 32 random bytes.');
        }
        return $secret;
    }

    // Generate signed token. Payload can hold any data (id, name, usertype, etc.)
    public static function generate(array $payload, int $ttlSeconds = 2592000): string
    {
        $payload['exp'] = time() + $ttlSeconds;
        $encoded        = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig            = hash_hmac('sha256', $encoded, self::secret());

        return "{$encoded}.{$sig}";
    }

    // Verify token signature + expiry. Returns payload array or null if invalid.
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $sig] = $parts;

        // Timing-safe check
        if (!hash_equals(hash_hmac('sha256', $encoded, self::secret()), $sig)) {
            return null;
        }

        $json = base64_decode($encoded, strict: true);
        if ($json === false) {
            return null;
        }

        $payload = json_decode($json, associative: true);
        if (!is_array($payload)) {
            return null;
        }

        // Expired?
        if (!isset($payload['exp']) || !is_int($payload['exp']) || time() >= $payload['exp']) {
            return null;
        }

        return $payload;
    }
}
