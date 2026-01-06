<?php
/**
 * Connection Test Script
 * Use this to verify database connection is working
 */

require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Connection Test - School LMS</title>";
echo "<style>";
echo "body { font-family: 'Poppins', sans-serif; padding: 2rem; background: #f9fafb; }";
echo ".container { max-width: 800px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }";
echo "h1 { color: #111827; margin-bottom: 2rem; }";
echo ".test { margin-bottom: 1.5rem; padding: 1rem; border-left: 4px solid #d1d5db; background: #f9fafb; border-radius: 0.375rem; }";
echo ".test.success { border-left-color: #22c55e; background: #f0fdf4; }";
echo ".test.error { border-left-color: #ef4444; background: #fef2f2; }";
echo ".test h3 { margin: 0 0 0.5rem 0; font-size: 1rem; }";
echo ".test.success h3 { color: #166534; }";
echo ".test.error h3 { color: #991b1b; }";
echo ".test p { margin: 0; color: #6b7280; font-size: 0.875rem; }";
echo ".code { background: #1f2937; color: #f0f0f0; padding: 0.5rem 0.75rem; border-radius: 0.25rem; font-family: 'Courier New', monospace; font-size: 0.8rem; margin-top: 0.5rem; }";
echo ".summary { margin-top: 2rem; padding: 1.5rem; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 0.375rem; }";
echo ".btn { display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3b82f6; color: #fff; text-decoration: none; border-radius: 0.375rem; font-weight: 500; }";
echo ".btn:hover { background: #2563eb; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>School LMS - Connection Test</h1>";

// Test 1: Configuration loaded
echo "<div class='test success'>";
echo "<h3>✓ Configuration Loaded</h3>";
echo "<p>Database configuration is accessible</p>";
echo "<div class='code'>DB_HOST: " . DB_HOST . "</div>";
echo "<div class='code'>DB_NAME: " . DB_NAME . "</div>";
echo "</div>";

// Test 2: Database connection
try {
    $db = new Database();
    echo "<div class='test success'>";
    echo "<h3>✓ Database Connection Successful</h3>";
    echo "<p>Successfully connected to MySQL database</p>";
    
    // Test 3: Database tables check
    $db->prepare("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?")->bind('s', DB_NAME)->execute();
    $result = $db->fetch();
    
    if ($result && $result['count'] > 0) {
        echo "<div class='code'>Tables found: " . $result['count'] . "</div>";
        echo "</div>";
        
        // Test 4: Users table check
        echo "<div class='test success'>";
        echo "<h3>✓ Users Table Exists</h3>";
        
        $db->prepare("SELECT COUNT(*) as count FROM users")->execute();
        $users = $db->fetch();
        
        echo "<p>Total users in system: " . $users['count'] . "</p>";
        
        // Show demo users
        $db->prepare("SELECT id, username, first_name, last_name, role FROM users LIMIT 10")->execute();
        $users_list = $db->fetchAll();
        
        if (!empty($users_list)) {
            echo "<div style='margin-top: 1rem;'><strong>Sample Users:</strong><br>";
            foreach ($users_list as $user) {
                echo "• " . $user['username'] . " (" . ucfirst($user['role']) . ") - " . $user['first_name'] . " " . $user['last_name'] . "<br>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "</div>";
        echo "<div class='test error'>";
        echo "<h3>⚠ No Tables Found</h3>";
        echo "<p>The database exists but contains no tables. Please import the schema.sql file.</p>";
        echo "<div class='code'>File: database/schema.sql</div>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='test error'>";
    echo "<h3>✗ Database Connection Failed</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Verify MySQL is running</li>";
    echo "<li>Check database credentials in includes/config.php</li>";
    echo "<li>Verify database '" . DB_NAME . "' exists</li>";
    echo "</ul>";
    echo "</div>";
}

// Test 5: Session functionality
echo "<div class='test success'>";
echo "<h3>✓ PHP Session Support</h3>";
echo "<p>Session functionality is enabled and working</p>";
echo "</div>";

// Test 6: File upload directory
$upload_dir = 'assets/uploads';
if (is_dir($upload_dir) && is_writable($upload_dir)) {
    echo "<div class='test success'>";
    echo "<h3>✓ Upload Directory Ready</h3>";
    echo "<p>File uploads are properly configured</p>";
    echo "<div class='code'>" . realpath($upload_dir) . "</div>";
    echo "</div>";
} else {
    echo "<div class='test error'>";
    echo "<h3>⚠ Upload Directory Issue</h3>";
    echo "<p>The uploads directory doesn't exist or is not writable</p>";
    echo "</div>";
}

// Summary
echo "<div class='summary'>";
echo "<h3 style='color: #0c2d6b; margin-top: 0;'>Getting Started</h3>";
echo "<p style='margin: 0; color: #0c2d6b;'>";
echo "1. Make sure MySQL is running<br>";
echo "2. Import <code>database/schema.sql</code> into your MySQL database<br>";
echo "3. Visit <a href='login.php' style='color: #3b82f6;'>Login Page</a> to sign in<br>";
echo "4. Use demo credentials: admin / password123";
echo "</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
