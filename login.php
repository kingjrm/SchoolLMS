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

// Show success message after registration
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
} elseif (isset($_GET['registered'])) {
    $success = 'Your account has been created. Please sign in.';
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
            if (strpos(strtolower($error), 'not verified') !== false && empty($_SESSION['pending_verify_email'])) {
                if (filter_var($username_or_email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['pending_verify_email'] = $username_or_email;
                }
            }
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
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        nav .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-primary);
            text-decoration: none;
        }

        .nav-brand span {
            color: var(--primary-color);
        }

        .nav-center {
            display: flex;
            gap: 2.5rem;
            list-style: none;
        }

        .nav-center a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-center a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .nav-center a:hover, .nav-center a.active {
            color: var(--primary-color);
        }

        .nav-center a:hover::after, .nav-center a.active::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
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
            .nav-center {
                display: none;
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
        <div class="container">
            <a href="index.php" class="nav-brand">School<span>SKILLS</span></a>
            <ul class="nav-center">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="instructors.php">Instructors</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="nav-right">
                <a href="register.php" class="btn-primary">Sign Up</a>
            </div>
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

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

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

            <?php if ($error && isset($_SESSION['pending_verify_email']) && $_SESSION['pending_verify_email']): ?>
                <div class="alert alert-info" style="background-color: rgba(59,130,246,0.1); border-left: 4px solid #3b82f6; color: #1d4ed8; margin-top:12px;">
                    <p class="font-medium">Your email is not verified yet.</p>
                    <div style="margin-top:8px; display:flex; gap:12px; align-items:center; flex-wrap: wrap;">
                        <input type="hidden" id="pending-email" value="<?php echo htmlspecialchars($_SESSION['pending_verify_email']); ?>" />
                        <a href="verify-otp.php" style="color:#1d4ed8; text-decoration:underline;">Verify now</a>
                        <a href="#" onclick="resendFromLogin(event)" style="color:#1d4ed8; text-decoration:underline;">Resend code</a>
                    </div>
                    <span id="resend-note" style="font-size:12px; color:#047857; font-weight: 600; display: none; margin-top: 8px;"></span>
                </div>
            <?php endif; ?>

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
        async function resendFromLogin(e){
            e.preventDefault();
            const emailEl = document.getElementById('pending-email');
            if(!emailEl){ return; }
            const email = emailEl.value;
            const note = document.getElementById('resend-note');
            note.style.display = 'block';
            note.style.color = '#3b82f6';
            note.textContent = 'Sending...';
            try{
                const resp = await fetch('verify-otp.php?resend=1', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'email='+encodeURIComponent(email)});
                const msg = await resp.text();
                if (resp.status === 204) {
                    note.style.color = '#047857';
                    note.textContent = '✓ A new code has been sent to your email!';
                } else if (resp.status === 429) {
                    note.style.color = '#b91c1c';
                    note.textContent = msg || 'Please wait before requesting another code.';
                } else {
                    note.style.color = '#b91c1c';
                    note.textContent = msg || 'Failed to send. Please try again.';
                }
            }catch(err){ 
                note.style.color = '#b91c1c';
                note.textContent = 'Network error. Please try again.';
            }
        }
    </script>
</body>
</html>
