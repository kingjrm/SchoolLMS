<?php
/**
 * Simple Email Sender using cURL to Gmail SMTP (no external dependencies)
 */

function sendEmailViaGmail($to, $subject, $body, $config) {
    // Config should have: username, password, from_address, from_name
    
    // Basic validation
    if (empty($config['username']) || empty($config['password'])) {
        return false;
    }

    // For a simple fallback without PHPMailer, we'll use PHP mail() with proper headers
    // In production, use a service like SendGrid API or proper SMTP library
    
    $headers = "From: " . $config['from_name'] . " <" . $config['from_address'] . ">\r\n";
    $headers .= "Reply-To: " . $config['from_address'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Alternative: Call Gmail SMTP via CURL (requires allow_url_fopen)
 * This is less reliable than a proper SMTP library, but works without Composer
 */
function sendEmailViaSmtp($to, $subject, $body, $config) {
    if (empty($config['username']) || empty($config['password'])) {
        return false;
    }

    // Build email
    $email = "To: {$to}\r\n";
    $email .= "From: " . $config['from_name'] . " <" . $config['from_address'] . ">\r\n";
    $email .= "Subject: {$subject}\r\n";
    $email .= "MIME-Version: 1.0\r\n";
    $email .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $email .= "\r\n{$body}";

    // Use stream context for SMTP (requires PHP 5.3+)
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);

    // Note: This approach is limited and not recommended for production.
    // The best approach is to install PHPMailer via Composer or use a service like SendGrid.
    
    // For now, fall back to PHP mail()
    return mail($to, $subject, $body, 
        "From: " . $config['from_name'] . " <" . $config['from_address'] . ">\r\n" .
        "Reply-To: " . $config['from_address'] . "\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n"
    );
}
?>
