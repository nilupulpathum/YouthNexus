<?php
/**
 * Central outbound mail helper.
 *
 * Uses SMTP when credentials are configured in app/core/config.local.php.
 * When they are missing, local requests get a written outbox (so the whole
 * OTP flow can be tested without a real mailbox), while non-local requests
 * fail loudly instead of pretending that an email was delivered.
 */

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

class Mailer {

    private static string $lastError = '';
    private static string $lastMode  = '';

    public static function lastError(): string {
        return self::$lastError;
    }

    /** 'smtp' or 'outbox' after a successful send. */
    public static function mode(): string {
        return self::$lastMode;
    }

    /** Gmail displays app passwords in spaced groups; the spaces are not part of it. */
    private static function smtpPassword(): string {
        return preg_replace('/\s+/', '', MAIL_PASS);
    }

    public static function isConfigured(): bool {
        return MAIL_USER !== '' && self::smtpPassword() !== '';
    }

    /**
     * Send an HTML email.
     *
     * @param string      $to
     * @param string      $toName
     * @param string      $subject
     * @param string      $html
     * @param string      $alt       Plain-text alternative
     * @param string|null $devCode   OTP, only written to the local outbox (never to SMTP logs)
     * @return bool                  True only when the message was handed off successfully
     */
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $html,
        string $alt = '',
        ?string $devCode = null
    ): bool {
        self::$lastError = '';
        self::$lastMode  = '';

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Invalid recipient email address.';
            return false;
        }

        if (!self::isConfigured()) {
            if (!self::isLocalRequest()) {
                self::$lastError = 'Outgoing mail is not configured on this server.';
                error_log('[YouthNexus] Mail not configured; refused to report a message as sent.');
                return false;
            }
            return self::writeToOutbox($to, $subject, $html, $alt, $devCode);
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = self::smtpPassword();
            $mail->Port       = MAIL_PORT;

            switch (MAIL_ENCRYPTION) {
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                case 'none':
                    $mail->SMTPSecure = '';
                    break;
                default:
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
            }

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to, $toName ?: null);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $alt !== '' ? $alt : strip_tags($html);

            $mail->send();
            self::$lastMode = 'smtp';
            return true;
        } catch (Throwable $e) {
            self::$lastError = (isset($mail) && $mail->ErrorInfo !== '')
                ? $mail->ErrorInfo
                : $e->getMessage();
            error_log('[YouthNexus] SMTP send failed for ' . $to . ': ' . self::$lastError);
            return false;
        }
    }

    private static function writeToOutbox(
        string $to,
        string $subject,
        string $html,
        string $alt,
        ?string $devCode
    ): bool {
        try {
            $dir = dirname(MAIL_OUTBOX);
            if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
                throw new RuntimeException('Could not create the local mail outbox directory.');
            }

            $separator = str_repeat('=', 72);
            $message = PHP_EOL . $separator . PHP_EOL
                . '[' . date('Y-m-d H:i:s') . '] To: ' . $to . PHP_EOL
                . 'Subject: ' . $subject . PHP_EOL;
            if ($devCode !== null && $devCode !== '') {
                $message .= 'OTP: ' . $devCode . PHP_EOL;
            }
            $message .= 'Plain text: ' . $alt . PHP_EOL
                . 'HTML: ' . $html . PHP_EOL
                . $separator . PHP_EOL;

            if (file_put_contents(MAIL_OUTBOX, $message, FILE_APPEND | LOCK_EX) === false) {
                throw new RuntimeException('Could not write to the local mail outbox.');
            }

            self::$lastMode = 'outbox';
            return true;
        } catch (Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('[YouthNexus] Local outbox write failed: ' . self::$lastError);
            return false;
        }
    }

    private static function isLocalRequest(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        return (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host)
            || php_sapi_name() === 'cli'
            || php_sapi_name() === 'cli-server';
    }
}
