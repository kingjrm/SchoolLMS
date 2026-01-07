<?php
require_once __DIR__ . '/Auth.php';

class ActivityLogger {
    public static function ensureTable(PDO $pdo): void {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_name VARCHAR(150) NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            role ENUM('admin','teacher','student') NOT NULL,
            action TEXT NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_role (role),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        try { $pdo->exec($sql); } catch (Exception $e) { /* ignore */ }
    }

    public static function log(PDO $pdo, string $action, array $details = []): void {
        self::ensureTable($pdo);
        $user = Auth::getCurrentUser();
        $userId = $user['id'] ?? null;
        $name = $user['full_name'] ?? ($user['username'] ?? 'Unknown');
        $email = $user['email'] ?? '';
        $role = $user['role'] ?? 'admin';
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $detailsText = '';
        if (!empty($details)) {
            $detailsText = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_name, user_email, role, action, details, ip_address, user_agent, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$userId, $name, $email, $role, $action, $detailsText, $ip, $ua]);
        } catch (Exception $e) {
            // ignore logging errors
        }
    }
}
