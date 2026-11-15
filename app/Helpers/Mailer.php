<?php

declare(strict_types=1);

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/** Generic Mailer Helper Class for sending transactional & notification emails. */
class Mailer
{
    /** Ensure PHPMailer classes are loaded defensively. */
    private static function ensureLoaded(): void
    {
        if (class_exists(PHPMailer::class)) {
            return;
        }

        $baseDir = dirname(__DIR__, 2) . '/PHPMailer/src';

        if (file_exists($baseDir . '/Exception.php')) {
            require_once $baseDir . '/Exception.php';
        }
        if (file_exists($baseDir . '/PHPMailer.php')) {
            require_once $baseDir . '/PHPMailer.php';
        }
        if (file_exists($baseDir . '/SMTP.php')) {
            require_once $baseDir . '/SMTP.php';
        }
    }

    /** Send an email using configured SMTP or mail settings. */
    public static function send(
        string|array $to,
        string $subject,
        string $body,
        array $options = []
    ): bool {
        self::ensureLoaded();

        if (!class_exists(PHPMailer::class)) {
            error_log('[Mailer Error] PHPMailer library files could not be found.');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Environment configuration with sensible defaults
            $host       = env('SMTP_HOST', 'localhost');
            $port       = (int) env('SMTP_PORT', 25);
            $user       = env('SMTP_USER', 'casainfo@casainfotech.com');
            $pass       = env('SMTP_PASS', 'C@sainfo24#');
            $secure     = strtolower((string) env('SMTP_SECURE', 'false'));
            $fromEmail  = $options['from'] ?? env('SMTP_FROM_EMAIL', $user);
            $fromName   = $options['fromName'] ?? env('SMTP_FROM_NAME', 'Casa Games');

            if (!empty($options['debug'])) {
                $mail->SMTPDebug = 2;
            }

            if (!empty($host) && $host !== 'false') {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->Port       = $port;
                $mail->SMTPAuth   = !empty($user);
                if (!empty($user)) {
                    $mail->Username   = $user;
                    $mail->Password   = $pass;
                }

                if ($secure === 'tls' || $secure === 'ssl') {
                    $mail->SMTPSecure = $secure;
                } else {
                    $mail->SMTPSecure  = false;
                    $mail->SMTPAutoTLS = false;
                }
                $mail->Timeout = (int) env('SMTP_TIMEOUT', 15);
            }

            // Set Sender
            $mail->setFrom($fromEmail, $fromName);

            // Set Recipients
            if (is_array($to)) {
                foreach ($to as $address => $name) {
                    if (is_int($address)) {
                        $mail->addAddress($name);
                    } else {
                        $mail->addAddress($address, $name);
                    }
                }
            } else {
                $mail->addAddress($to);
            }

            // Reply-To
            if (!empty($options['replyTo'])) {
                $mail->addReplyTo($options['replyTo'], $options['replyToName'] ?? '');
            }

            // CC
            if (!empty($options['cc'])) {
                $ccs = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
                foreach ($ccs as $ccEmail) {
                    if (!empty($ccEmail)) {
                        $mail->addCC($ccEmail);
                    }
                }
            }

            // BCC
            if (!empty($options['bcc'])) {
                $bccs = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
                foreach ($bccs as $bccEmail) {
                    if (!empty($bccEmail)) {
                        $mail->addBCC($bccEmail);
                    }
                }
            }

            // Attachments
            if (!empty($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } elseif (is_string($attachment) && file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }

            // Body & HTML Mode
            $isHtml = $options['isHtml'] ?? true;
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            if (!empty($options['altBody'])) {
                $mail->AltBody = $options['altBody'];
            }

            return $mail->send();
        } catch (PHPMailerException $e) {
            error_log('[Mailer Exception] Recipient: ' . (is_array($to) ? json_encode($to) : $to) . ' | Error: ' . $mail->ErrorInfo);
            return false;
        } catch (\Throwable $e) {
            error_log('[Mailer System Error] ' . $e->getMessage());
            return false;
        }
    }
}
