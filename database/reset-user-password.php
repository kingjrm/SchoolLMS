<?php
// Reset a user's password safely using PHP's password_hash
// Usage examples:
//   Via browser: http://localhost/School-LMS/database/reset-user-password.php?username=admin&password=password123
//   Via CLI: php database/reset-user-password.php admin password123

require_once __DIR__ . '/../includes/config.php';

function respond($msg){ echo $msg . "\n"; }

$username = null; $newPass = null;
if (PHP_SAPI === 'cli') {
    $username = $argv[1] ?? null;
    $newPass = $argv[2] ?? null;
} else {
    $username = $_GET['username'] ?? null;
    $newPass = $_GET['password'] ?? null;
}

if (!$username || !$newPass) {
    respond('Usage (CLI): php database/reset-user-password.php <username> <new_password>');
    respond('Usage (Web): /database/reset-user-password.php?username=<username>&password=<new_password>');
    exit(1);
}

try {
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?');
    $stmt->execute([$hash, $username]);

    if ($stmt->rowCount() > 0) {
        respond("✓ Password updated for user: {$username}");
    } else {
        respond("✗ No user updated. Check if username exists: {$username}");
    }
} catch (Throwable $e) {
    respond('Error: ' . $e->getMessage());
    exit(1);
}
