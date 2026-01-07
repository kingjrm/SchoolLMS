<?php
/**
 * Mailer helper using PHPMailer
 * 
 * @phpstan-ignore-next-line
 * @psalm-suppress UndefinedClass
 */

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {
    private $config;
    private $lastError = '';

    public function __construct() {
        $cfg = require __DIR__ . '/mail-config.php';
        $this->config = $cfg['mail'] ?? [];
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function send($toEmail, $toName, $subject, $htmlBody, $altBody = '') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'] ?? '';
            $mail->Password = $this->config['password'] ?? '';

            // Map encryption values to PHPMailer constants
            $enc = strtolower((string)($this->config['encryption'] ?? 'tls'));
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // implicit SSL on 465
                if (empty($this->config['port'])) { $mail->Port = 465; } else { $mail->Port = (int)$this->config['port']; }
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS on 587
                $mail->Port = (int)($this->config['port'] ?? 587);
            }

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Optional verbose SMTP debug
            $debug = getenv('MAIL_DEBUG');
            if ($debug && (int)$debug === 1) {
                $mail->SMTPDebug = 2; // client and server messages
                $mail->Debugoutput = function ($str, $level) {
                    $logDir = __DIR__ . '/../logs';
                    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
                    @file_put_contents($logDir . '/mail_smtp_debug.log', '[' . date('c') . "] [$level] $str\n", FILE_APPEND);
                };
            }

            // Allow insecure TLS for local dev (Windows/XAMPP) if requested
            $insecure = getenv('MAIL_TLS_INSECURE');
            if ($insecure && (strtolower($insecure) === '1' || strtolower($insecure) === 'true' )) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $fromAddress = $this->config['from']['address'] ?? 'noreply@example.com';
            $fromName = $this->config['from']['name'] ?? 'Mailer';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail, $toName ?: $toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $sent = $mail->send();
            if (!$sent) {
                $this->lastError = $mail->ErrorInfo ?: 'Unknown error sending mail';
                $this->logError($this->lastError);
            }
            return $sent;
        } catch (PHPMailerException $e) {
            $this->lastError = $e->getMessage();
            $this->logError('PHPMailerException: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->logError('General exception: ' . $e->getMessage());
            return false;
        }
    }

    private function logError($message) {
        error_log('Mailer error: ' . $message);
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        @file_put_contents($logDir . '/mail_error.log', '[' . date('c') . '] ' . $message . "\n", FILE_APPEND);
    }
}

?>
