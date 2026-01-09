<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Auth.php';
require_once 'includes/helpers.php';

$error = '';
$success = '';
$step = 'email'; // 'email', 'otp', or 'password'

// Handle retry - reset to email step
if (isset($_GET['retry'])) {
    $step = 'email';
    unset($_SESSION['reset_email']);
    unset($_SESSION['otp_verified']);
}

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

// Handle email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $auth = new Auth();
        $result = $auth->requestPasswordReset($email);

        if ($result['success']) {
            $success = $result['message'];
            $step = 'otp';
            $_SESSION['reset_email'] = $email;
        } else {
            $error = $result['message'];
        }
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $otp = sanitize($_POST['otp'] ?? '');
    $email = $_SESSION['reset_email'] ?? '';

    if (empty($email)) {
        $error = 'Session expired. Please start over.';
        $step = 'email';
    } elseif (empty($otp)) {
        $error = 'Please enter the verification code';
    } else {
        require_once __DIR__ . '/includes/OTP.php';
        $otpVerify = otp_verify($email, $otp, 'password_reset');

        if ($otpVerify['success']) {
            $success = 'Code verified successfully. Please set your new password.';
            $step = 'password';
            $_SESSION['otp_verified'] = true;
        } else {
            $error = $otpVerify['message'] ?? 'Invalid or expired code';
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = $_SESSION['reset_email'] ?? '';

    if (empty($email) || !isset($_SESSION['otp_verified'])) {
        $error = 'Session expired. Please start over.';
        $step = 'email';
    } elseif (empty($newPassword)) {
        $error = 'Please enter a new password';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $auth = new Auth();
        $result = $auth->resetPassword($email, '', $newPassword); // OTP already verified

        if ($result['success']) {
            $success = $result['message'];
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            // Redirect to login after 3 seconds
            header("refresh:3;url=login.php");
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
    <title>Forgot Password - School LMS</title>
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
        .forgot-container {
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
        .forgot-form-section {
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
            height: 3rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input::placeholder {
            color: var(--text-tertiary);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .btn-submit {
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

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
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
        .forgot-image-section {
            position: relative;
            overflow: hidden;
            background-color: var(--bg-secondary);
        }

        .forgot-image-section img {
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
            .forgot-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .forgot-image-section {
                display: none;
            }

            .forgot-form-section {
                max-width: 500px;
            }
        }

        @media (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .forgot-form-section {
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
                <a href="login.php" class="btn-primary">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="forgot-container">
        <!-- Left Side -->
        <div class="forgot-form-section">
            <div class="form-header">
                <div class="form-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                        <path d="M12 6v6l4.25 2.55"/>
                    </svg>
                    Your logo
                </div>
                <h1><?php 
                    if ($step === 'email') echo 'Forgot Password';
                    elseif ($step === 'otp') echo 'Verify Code';
                    else echo 'Set New Password';
                ?></h1>
                <p><?php 
                    if ($step === 'email') echo 'Enter your email to receive a reset code';
                    elseif ($step === 'otp') echo 'Enter the verification code sent to your email';
                    else echo 'Enter your new password';
                ?></p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php if ($step === 'email'): ?>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="your@email.com" required autofocus>
                    </div>
                    <button type="submit" class="btn-submit">Send Reset Code</button>
                <?php elseif ($step === 'otp'): ?>
                    <div class="form-group">
                        <label for="otp">Verification Code</label>
                        <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" required autofocus maxlength="6">
                    </div>
                    <button type="submit" name="verify_otp" class="btn-submit">Verify Code</button>
                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="?retry=1" style="color: var(--accent-color); text-decoration: none; font-size: 0.875rem;">Didn't receive code? Try again</a>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Min. 6 characters" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                    <button type="submit" class="btn-submit">Reset Password</button>
                <?php endif; ?>
            </form>

            <div class="form-footer">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>

        <!-- Right Side -->
        <div class="forgot-image-section">
            <img src="assets/images/StudentsBSU.png" alt="Students">
            <div class="badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </div>
                <div class="badge-content">
                    <h3>Secure Reset</h3>
                    <p>Password recovery with email verification</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>