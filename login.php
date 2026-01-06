<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/helpers.php';

$error = '';
$success = '';

if (Auth::isLoggedIn()) {
    if (Auth::hasRole('admin')) {
        header('Location: admin/dashboard.php', true, 302);
    } elseif (Auth::hasRole('teacher')) {
        header('Location: teacher/dashboard.php', true, 302);
    } else {
        header('Location: student/dashboard.php', true, 302);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = sanitize($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_or_email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $auth = new Auth();
        $result = $auth->login($username_or_email, $password);
        
        if ($result['success']) {
            if (Auth::hasRole('admin')) {
                header('Location: admin/dashboard.php', true, 302);
            } elseif (Auth::hasRole('teacher')) {
                header('Location: teacher/dashboard.php', true, 302);
            } else {
                header('Location: student/dashboard.php', true, 302);
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f3f4f6;
            --bg-tertiary: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-tertiary: #9ca3af;
            --border-color: #d1d5db;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --primary-color: #ff6b35;
            --primary-dark: #d63d1a;
            --accent-color: #3b82f6;
            --success-color: #22c55e;
            --danger-color: #ef4444;
        }

        html.dark-mode {
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-tertiary: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-tertiary: #9ca3af;
            --border-color: #374151;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        nav {
            background-color: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-brand svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
            list-style: none;
        }

        .nav-menu a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            cursor: pointer;
        }

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--primary-color);
        }

        .nav-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle svg {
            width: 20px;
            height: 20px;
            stroke: var(--text-primary);
            fill: none;
            stroke-width: 2;
        }

        /* Main Container */
        .login-container {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            max-width: 100%;
            width: 100%;
            height: calc(100vh - 80px);
            margin: 0;
            padding: 0;
            align-items: stretch;
        }

        /* Left Side - Form */
        .login-form-section {
            padding: 1.5rem 2.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: var(--bg-primary);
        }

        .form-header {
            margin-bottom: 1rem;
        }

        .form-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .form-logo svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
        }

        .form-header h1 {
            font-size: 1.75rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-group input::placeholder {
            color: var(--text-tertiary);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .form-options {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .form-options a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        .form-options a:hover {
            text-decoration: underline;
        }

        .btn-signin {
            width: 100%;
            padding: 0.875rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-signin:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.25rem 0;
            color: var(--text-tertiary);
            font-size: 0.8rem;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            border-color: var(--primary-color);
            background-color: var(--bg-secondary);
        }

        .social-btn svg {
            width: 20px;
            height: 20px;
            stroke: var(--text-primary);
            fill: none;
            stroke-width: 2;
        }

        .form-footer {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Right Side - Image */
        .login-image-section {
            position: relative;
            overflow: hidden;
            background-color: var(--bg-secondary);
        }

        .login-image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .badge {
            position: absolute;
            bottom: 2.5rem;
            left: 2.5rem;
            transform: none;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .badge-icon {
            width: 50px;
            height: 50px;
            background-color: var(--accent-color);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-icon svg {
            width: 28px;
            height: 28px;
            stroke: white;
            fill: none;
            stroke-width: 2;
        }

        .badge-content h3 {
            color: #111827;
            font-size: 1.25rem;
            margin: 0;
        }

        .badge-content p {
            color: #6b7280;
            font-size: 0.75rem;
            margin: 0;
        }

        /* Alerts */
        .alert {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .login-image-section {
                display: none;
            }

            .login-form-section {
                max-width: 500px;
            }
        }

        @media (max-width: 768px) {
            nav {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 1rem;
                width: 100%;
                order: 3;
            }

            .login-form-section {
                padding: 1.5rem;
            }

            .form-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-brand">
            <svg viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            School LMS
        </div>
        <ul class="nav-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#features">Features</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="register.php">Sign Up</a></li>
        </ul>
        <div class="nav-controls">
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                <svg id="sunIcon" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
                <svg id="moonIcon" viewBox="0 0 24 24" style="display: none;">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>
        </div>
    </nav>



    <!-- Main Content -->
    <div class="login-container">
        <!-- Left Side -->
        <div class="login-form-section">
            <div class="form-header">
                <div class="form-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                        <path d="M12 6v6l4.25 2.55"/>
                    </svg>
                    Your logo
                </div>
                <h1>Login</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username_or_email">Email or Username</label>
                    <input type="text" id="username_or_email" name="username_or_email" placeholder="your@email.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-options">
                    <a href="#forgot">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-signin">Sign In</button>
            </form>

            <div class="divider">
                <div class="divider-line"></div>
                <span>or continue with</span>
                <div class="divider-line"></div>
            </div>

            <div class="social-login">
                <button class="social-btn" type="button" title="Sign in with Google">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 7v10M7 12h10"></path>
                    </svg>
                </button>
                <button class="social-btn" type="button" title="Sign in with GitHub">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 2c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path>
                        <path d="M8 8h8M8 14h8"></path>
                    </svg>
                </button>
                <button class="social-btn" type="button" title="Sign in with Microsoft">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </button>
            </div>

            <div class="form-footer">
                Don't have an account? <a href="register.php">Register for free</a>
            </div>
        </div>

        <!-- Right Side -->
        <div class="login-image-section">
            <img src="assets/images/StudentsBSU.png" alt="Students">
            <div class="badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </div>
                <div class="badge-content">
                    <h3>250k</h3>
                    <p>Assisted Students</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        const html = document.documentElement;

        // Check for saved theme preference or system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

        if (initialTheme === 'dark') {
            html.classList.add('dark-mode');
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        }

        themeToggle.addEventListener('click', () => {
            const isDarkMode = html.classList.toggle('dark-mode');
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            
            if (isDarkMode) {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        });
    </script>
</body>
</html>
