<?php
/**
 * ============================================
 * PHPMailer Wrapper Helper
 * ============================================
 * Provides easy email sending via PHPMailer.
 * ============================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * MailHelper class - simplified email sending
 */
class MailHelper
{
    private $mail;
    private $defaultFrom;
    private $defaultFromName;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->defaultFrom = env('MAIL_FROM', 'noreply@casa-games.com');
        $this->defaultFromName = env('MAIL_FROM_NAME', 'CASA Games');
        $this->setup();
    }

    private function setup(): void
    {
        $smtpHost = env('SMTP_HOST', '');
        $smtpUser = env('SMTP_USER', '');
        $smtpPass = env('SMTP_PASS', '');

        if ($smtpHost) {
            $this->mail->isSMTP();
            $this->mail->Host = $smtpHost;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $smtpUser;
            $this->mail->Password = $smtpPass;
            $this->mail->SMTPSecure = env('SMTP_SECURE', PHPMailer::ENCRYPTION_SMTPS);
            $this->mail->Port = (int)env('SMTP_PORT', 465);
        } else {
            $this->mail->isMail();
        }

        $this->mail->setFrom($this->defaultFrom, $this->defaultFromName);
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * Send HTML email
     */
    public function send($to, $subject, $body, $altBody = ''): bool
    {
        try {
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody ?: strip_tags($body);
            $this->mail->send();
            $this->mail->clearAddresses();
            return true;
        } catch (Exception $e) {
            error_log("MailHelper Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send plain text email
     */
    public function sendText($to, $subject, $text): bool
    {
        $this->mail->isHTML(false);
        $result = $this->send($to, $subject, $text);
        $this->mail->isHTML(true);
        return $result;
    }

    /**
     * Get underlying PHPMailer instance for advanced usage
     */
    public function getMailer(): PHPMailer
    {
        return $this->mail;
    }
}

/**
 * Quick helper function to send email
 */
function sendEmail($to, $subject, $body, $altBody = ''): bool
{
    $mailer = new MailHelper();
    return $mailer->send($to, $subject, $body, $altBody);
}