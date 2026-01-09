<?php
/**
 * Auth Helper Class
 * Handles authentication and user management
 */

require_once __DIR__ . '/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_set_cookie_params([
                'secure' => false, // Set to true if using HTTPS
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    /**
     * Login user
     */
    public function login($username_or_email, $password) {
        global $pdo;
        try {
            $query = "SELECT id, username, email, password, role, status, first_name, last_name, is_verified 
                     FROM users WHERE username = ? OR email = ? LIMIT 1";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$username_or_email, $username_or_email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return ['success' => false, 'message' => 'Invalid username or email'];
            }

            if ($result['status'] !== 'active') {
                return ['success' => false, 'message' => 'Account is inactive'];
            }

            // Block login if email not verified
            if ((int)$result['is_verified'] !== 1) {
                $_SESSION['pending_verify_email'] = $result['email'];
                return ['success' => false, 'message' => 'Your email is not verified yet. Please check your inbox or verify now.'];
            }

            if (!password_verify($password, $result['password'])) {
                return ['success' => false, 'message' => 'Incorrect password'];
            }

            // Set session variables
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['email'] = $result['email'];
            $_SESSION['role'] = $result['role'];
            $_SESSION['first_name'] = $result['first_name'];
            $_SESSION['last_name'] = $result['last_name'];
            $_SESSION['login_time'] = time();

            return ['success' => true, 'message' => 'Login successful', 'role' => $result['role']];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }

    /**
     * Register new user
     */
    public function register($username, $email, $password, $confirm_password, $first_name, $last_name) {
        global $pdo;
        try {
            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
                return ['success' => false, 'message' => 'All fields are required'];
            }

            if (strlen($username) < 3 || strlen($username) > 50) {
                return ['success' => false, 'message' => 'Username must be between 3-50 characters'];
            }

            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }

            if ($password !== $confirm_password) {
                return ['success' => false, 'message' => 'Passwords do not match'];
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Check if user exists
            $checkQuery = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert user with is_verified = 0
            $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, is_verified, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, 'student', 'inactive', 0, NOW(), NOW())";
            
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name]);

            // Create OTP and send email
            require_once __DIR__ . '/OTP.php';
            require_once __DIR__ . '/Mailer.php';
            $otp = otp_create($email, 'register', 10);

                $mailer = new Mailer();
            $subject = 'Verify your email - School LMS';
            $html = '<p>Hello ' . htmlspecialchars($first_name) . ',</p>' .
                    '<p>Your verification code is:</p>' .
                    '<div style="font-size:24px;font-weight:bold;letter-spacing:4px;color:#ff6b35;">' . htmlspecialchars($otp['code']) . '</div>' .
                    '<p>This code will expire at ' . htmlspecialchars($otp['expires_at']) . ' (UTC).</p>' .
                    '<p>If you did not request this, please ignore this email.</p>';
                $sent = $mailer->send($email, $first_name . ' ' . $last_name, $subject, $html);
                // Log outcome for troubleshooting
                $logDir = __DIR__ . '/../logs';
                if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
                $line = date('c') . " | register | $email | SEND " . ($sent ? 'OK' : ('FAILED: ' . $mailer->getLastError())) . "\n";
                @file_put_contents($logDir . '/logs_otp.txt', $line, FILE_APPEND);

            return ['success' => true, 'message' => 'Registration successful. We sent a verification code to your email.', 'email' => $email];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']);
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }

    /**
     * Check if user has admin access
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }

    /**
     * Check if user is teacher
     */
    public static function isTeacher() {
        return self::hasRole('teacher');
    }

    /**
     * Check if user is student
     */
    public static function isStudent() {
        return self::hasRole('student');
    }

    /**
     * Logout user
     */
    public static function logout() {
        session_destroy();
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'full_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
        ];
    }

    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireLogin();
        if (!self::hasRole($role)) {
            header('Location: ' . APP_URL . '/401.php');
            exit;
        }
    }

    /**
     * Request password reset - send OTP to email
     */
    public function requestPasswordReset($email) {
        global $pdo;
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, username, first_name, last_name FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'No account found with this email address'];
            }

            // Generate OTP
            require_once __DIR__ . '/OTP.php';
            $otpResult = otp_create($email, 'password_reset', 15); // 15 minutes expiry

            // Send email
            require_once __DIR__ . '/Mailer.php';
            $mailer = new Mailer();

            $subject = 'Password Reset - School LMS';
            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff6b35;'>Password Reset Request</h2>
                <p>Hello {$user['first_name']} {$user['last_name']},</p>
                <p>You requested a password reset for your School LMS account.</p>
                <p>Your verification code is: <strong style='font-size: 24px; color: #ff6b35;'>{$otpResult['code']}</strong></p>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <br>
                <p>Best regards,<br>School LMS Team</p>
            </div>
            ";

            $altBody = "Hello {$user['first_name']} {$user['last_name']},\n\nYou requested a password reset for your School LMS account.\n\nYour verification code is: {$otpResult['code']}\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this reset, please ignore this email.\n\nBest regards,\nSchool LMS Team";

            $mailResult = $mailer->send($email, $user['first_name'] . ' ' . $user['last_name'], $subject, $htmlBody, $altBody);

            if (!$mailResult) {
                return ['success' => false, 'message' => 'Failed to send email: ' . $mailer->getLastError()];
            }

            return ['success' => true, 'message' => 'Password reset code sent to your email'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Reset password using OTP
     */
    public function resetPassword($email, $otp, $newPassword) {
        global $pdo;
        try {
            // If OTP is provided, verify it first
            if (!empty($otp)) {
                require_once __DIR__ . '/OTP.php';
                $otpVerify = otp_verify($email, $otp, 'password_reset');

                if (!$otpVerify['success']) {
                    return ['success' => false, 'message' => $otpVerify['message'] ?? 'Invalid or expired code'];
                }
            }

            // Validate new password
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Failed to update password'];
            }

            return ['success' => true, 'message' => 'Password reset successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

Auth::startSession();
