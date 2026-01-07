<?php
require_once __DIR__ . '/includes/config.php';

echo "=== Environment Variables Check ===\n";
echo "MAIL_USERNAME: " . (getenv('MAIL_USERNAME') ?: 'NOT SET') . "\n";
echo "MAIL_PASSWORD: " . (getenv('MAIL_PASSWORD') ? 'SET (' . strlen(getenv('MAIL_PASSWORD')) . ' chars)' : 'NOT SET') . "\n";
echo "MAIL_PORT: " . (getenv('MAIL_PORT') ?: 'NOT SET') . "\n";
echo "MAIL_ENCRYPTION: " . (getenv('MAIL_ENCRYPTION') ?: 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . (getenv('MAIL_FROM_ADDRESS') ?: 'NOT SET') . "\n";
echo "MAIL_DEBUG: " . (getenv('MAIL_DEBUG') ?: 'NOT SET') . "\n";
echo "MAIL_TLS_INSECURE: " . (getenv('MAIL_TLS_INSECURE') ?: 'NOT SET') . "\n";

echo "\n=== Mail Config Array ===\n";
$mailCfg = require __DIR__ . '/includes/mail-config.php';
print_r($mailCfg['mail']);

echo "\n=== Attempting to send test email ===\n";
require_once __DIR__ . '/includes/Mailer.php';
$mailer = new Mailer();
$to = getenv('MAIL_FROM_ADDRESS') ?: 'dumpaccnigladio@gmail.com';
$sent = $mailer->send($to, '', '[TEST] School LMS', '<p>Test email from diagnostic script</p>');

if ($sent) {
    echo "✓ EMAIL SENT SUCCESSFULLY!\n";
} else {
    echo "✗ EMAIL FAILED\n";
    echo "Error: " . $mailer->getLastError() . "\n";
}

echo "\n=== Check logs for details ===\n";
echo "mail_error.log:\n";
if (file_exists(__DIR__ . '/logs/mail_error.log')) {
    echo file_get_contents(__DIR__ . '/logs/mail_error.log');
} else {
    echo "No error log found\n";
}
