<?php
/**
 * Mail Configuration for School LMS
 * Gmail SMTP settings
 */

return [
    'mail' => [
        'driver' => 'smtp',
        'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port' => (int)(getenv('MAIL_PORT') ?: 587),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'username' => getenv('MAIL_USERNAME') ?: '', // Set in .env: your Gmail address
        'password' => getenv('MAIL_PASSWORD') ?: '', // Set in .env: Gmail app-specific password
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@schoollms.com',
            'name' => 'School LMS'
        ]
    ]
];
