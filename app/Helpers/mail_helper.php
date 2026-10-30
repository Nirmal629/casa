<?php

declare(strict_types=1);

use App\Helpers\Mailer;

if (!function_exists('sendEmail')) {
    /** Generic helper function to send emails globally across the application. */
    function sendEmail(string|array $to, string $subject, string $body, array $options = []): bool
    {
        return Mailer::send($to, $subject, $body, $options);
    }
}
