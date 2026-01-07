<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/mail-config.php';

$mailConfig = require 'includes/mail-config.php';
$cfg = $mailConfig['mail'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Mail Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ff6b35; }
        .ok { border-left-color: #22c55e; }
        .error { border-left-color: #ef4444; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Mail Configuration Debug</h1>

    <div class="box">
        <h3>Environment Variables</h3>
        <pre>MAIL_USERNAME: <?php echo htmlspecialchars(getenv('MAIL_USERNAME') ?: 'NOT SET'); ?>
MAIL_PASSWORD: <?php echo htmlspecialchars(getenv('MAIL_PASSWORD') ? '***' : 'NOT SET'); ?>
MAIL_FROM_ADDRESS: <?php echo htmlspecialchars(getenv('MAIL_FROM_ADDRESS') ?: 'NOT SET'); ?></pre>
    </div>

    <div class="box">
        <h3>Mail Config Array</h3>
        <pre><?php print_r($cfg); ?></pre>
    </div>

    <div class="box <?php echo function_exists('mail') ? 'ok' : 'error'; ?>">
        <h3>PHP mail() Function</h3>
        <p><?php echo function_exists('mail') ? '✓ Available' : '✗ Not available'; ?></p>
    </div>

    <div class="box">
        <h3>Test Email Send</h3>
        <?php
        if (empty($cfg['username']) || empty($cfg['password'])) {
            echo '<p style="color: #ef4444;">❌ Gmail credentials not configured</p>';
        } else {
            require_once 'includes/Mailer.php';
            $mailer = new Mailer();
            $testEmail = $cfg['from']['address'];
            $testSubject = '[TEST] School LMS Mail Configuration';
            $testBody = '<p>This is a test email from your School LMS.</p><p>If you received this, mail is working!</p>';
            
            $sent = $mailer->send($testEmail, '', $testSubject, $testBody);
            
            echo '<p style="color: ' . ($sent ? '#22c55e' : '#ef4444') . ';">';
            echo $sent ? '✓ Test email sent successfully' : ('✗ Send failed: ' . $mailer->getLastError());
            echo '</p>';
            
            echo '<p style="font-size: 12px; color: #666;">
                Check your Gmail inbox and spam folder. If you don\'t receive it within 2 minutes, 
                check the logs/mail_error.log file for details.
            </p>';
        }
        ?>
    </div>

    <div class="box">
        <h3>OTP Log File</h3>
        <?php
        $logFile = __DIR__ . '/logs/logs_otp.txt';
        if (file_exists($logFile)) {
            echo '<pre>';
            echo htmlspecialchars(file_get_contents($logFile));
            echo '</pre>';
        } else {
            echo '<p style="color: #666;">No OTP log file created yet. Register an account first.</p>';
        }
        ?>
    </div>

    <div class="box">
        <h3>Database Check</h3>
        <?php
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'email_otps'");
            $exists = $check->fetch();
            echo $exists ? '✓ email_otps table exists' : '✗ email_otps table NOT found';
        } catch (Exception $e) {
            echo '✗ Database error: ' . htmlspecialchars($e->getMessage());
        }
        ?>
    </div>

    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px;">
        <h3>Next Steps</h3>
        <ol>
            <li>Register a test account at <a href="register.php">register.php</a></li>
            <li>Check this page again to see the OTP in the log</li>
            <li>Check your Gmail inbox (and spam)</li>
            <li>If no email arrives, your server's SMTP needs configuration</li>
        </ol>
    </div>
</body>
</html>
