<?php
/**
 * OTP helper for creating and verifying email OTPs
 */

require_once __DIR__ . '/config.php';

function otp_generate_code($length = 6) {
    $min = (int) pow(10, $length - 1);
    $max = (int) (pow(10, $length) - 1);
    return (string) random_int($min, $max);
}

function otp_create($email, $purpose = 'register', $ttlMinutes = 10) {
    global $pdo;
    $code = otp_generate_code(6);
    $expiresAt = (new DateTime('+'.$ttlMinutes.' minutes'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("INSERT INTO email_otps (email, code, purpose, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $code, $purpose, $expiresAt]);

    // Log to file for debugging
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
    @file_put_contents($logDir . '/logs_otp.txt', date('c') . " | $email | $purpose | OTP: $code | Expires: $expiresAt\n", FILE_APPEND);

    return ['code' => $code, 'expires_at' => $expiresAt];
}

function otp_verify($email, $code, $purpose = 'register') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, code, expires_at, used_at FROM email_otps WHERE email = ? AND purpose = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$email, $purpose]);
    $rows = $stmt->fetchAll();

    $now = new DateTime();
    foreach ($rows as $row) {
        if ($row['used_at']) continue;
        if ($row['code'] !== $code) continue;

        $exp = new DateTime($row['expires_at']);
        if ($now > $exp) {
            return ['success' => false, 'message' => 'The code has expired.'];
        }
        // Mark as used
        $upd = $pdo->prepare("UPDATE email_otps SET used_at = NOW() WHERE id = ?");
        $upd->execute([$row['id']]);
        return ['success' => true];
    }

    return ['success' => false, 'message' => 'Invalid verification code.'];
}

?>
