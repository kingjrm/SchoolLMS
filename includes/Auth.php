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
            $query = "SELECT id, username, email, password, role, status, first_name, last_name 
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

            // Insert user
            $insertQuery = "INSERT INTO users (username, email, password, first_name, last_name, role, status, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, 'student', 'active', NOW(), NOW())";
            
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([$username, $email, $hashedPassword, $first_name, $last_name]);

            return ['success' => true, 'message' => 'Registration successful. Please login.'];
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
}

Auth::startSession();
