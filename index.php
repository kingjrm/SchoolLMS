<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Auth.php';

// Fetch latest news and announcements
try {
    // Get latest 3 news items
    $news_query = $pdo->prepare("
        SELECT id, title, summary, content, image_url, published_at, author
        FROM news 
        WHERE status = 'published' AND published_at <= NOW()
        ORDER BY published_at DESC 
        LIMIT 3
    ");
    $news_query->execute();
    $news_items = $news_query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $news_items = [];
}

try {
    // Get latest 5 announcements
    $announcements_query = $pdo->prepare("
        SELECT id, title, content, posted_at, posted_by, priority
        FROM system_announcements 
        WHERE status = 'active' AND posted_at <= NOW()
        ORDER BY priority DESC, posted_at DESC 
        LIMIT 5
    ");
    $announcements_query->execute();
    $announcements = $announcements_query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Pixelify+Sans&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #fef8f5;
            --bg-tertiary: #f5f5f5;
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --text-tertiary: #999999;
            --border-color: #e5e5e5;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.08);
            --primary-color: #ff6b35;
            --primary-dark: #e55a28;
            --primary-light: #ff8c5a;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Loading Screen / Introduction */
        .intro-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }

        .intro-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .intro-logo {
            max-width: 120px;
            width: 25%;
            animation: introFade 2s ease-in-out;
        }

        .intro-text {
            color: #111827;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 1rem;
            font-family: 'Pixelify Sans', sans-serif;
            min-height: 1.5rem;
        }

        .intro-text::after {
            content: '|';
            animation: blink 0.7s infinite;
            margin-left: 2px;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        .intro-loader {
            display: none;
        }

        @keyframes introFade {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Main Content */
        .main-content {
            opacity: 0;
            transition: opacity 0.8s ease;
        }

        .main-content.visible {
            opacity: 1;
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
            max-width: 1200px;
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

        .nav-center a:hover {
            color: var(--primary-color);
        }

        .nav-center a:hover::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .theme-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .theme-toggle:hover {
            background-color: var(--bg-secondary);
        }

        .theme-toggle svg {
            width: 20px;
            height: 20px;
            stroke: var(--text-secondary);
            fill: none;
            stroke-width: 2;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-primary);
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Hero Section */
        .hero {
            background: var(--bg-primary);
            padding: 2rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text {
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .hero-text h1 .highlight {
            color: var(--primary-color);
        }

        .hero-text p {
            font-size: 1.05rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .hero-image {
            position: relative;
            animation: fadeInRight 0.8s ease-out 0.3s backwards;
        }

        .hero-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Feature Badges */
        .feature-badges {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 3rem;
            border-top: 1px solid var(--border-color);
        }

        .feature-badge {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .feature-badge-icon {
            width: 45px;
            height: 45px;
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-badge-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .feature-badge-text h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .feature-badge-text p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Partner Logos */
        .partners {
            background: var(--bg-primary);
            padding: 3rem 0;
            border-top: 1px solid var(--border-color);
        }

        .partners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            align-items: center;
        }

        .partner-logo {
            text-align: center;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.9rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .partner-logo:hover {
            opacity: 1;
        }

        /* Stats Section */
        .stats {
            display: none;
        }

        /* About Section */
        .about-section {
            padding: 6rem 0;
            background: var(--bg-primary);
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h4 {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }

        .about-text h2 {
            font-size: 2.5rem;
            line-height: 1.3;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .about-text p {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .about-buttons {
            display: flex;
            gap: 1rem;
        }

        .about-images {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .about-image {
            width: 100%;
            height: 250px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--shadow-lg);
        }

        .about-images .about-image:nth-child(1) {
            margin-top: 0;
        }

        .about-images .about-image:nth-child(2) {
            margin-top: 3rem;
        }

        .about-images .about-image:nth-child(3) {
            margin-top: 1.5rem;
        }

        .about-images .about-image:nth-child(4) {
            margin-top: 4.5rem;
        }

        /* Features Section */
        .features-section {
            padding: 0;
            display: none;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.25rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .section-header p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background-color: var(--bg-primary);
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fadeInUp 0.8s ease-out backwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(255,107,53,0.15);
            border-color: var(--primary-color);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            background-color: var(--primary-color);
        }

        .feature-card:hover .feature-icon svg {
            stroke: white;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 107, 53, 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .feature-icon svg {
            width: 32px;
            height: 32px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* News Section */
        .news-section {
            padding: 4rem 0;
            background-color: var(--bg-primary);
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .news-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .news-image {
            width: 100%;
            height: 200px;
            background-color: var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .news-image svg {
            width: 60px;
            height: 60px;
            stroke: var(--text-tertiary);
            fill: none;
            stroke-width: 1.5;
        }

        .news-content {
            padding: 1.5rem;
        }

        .news-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--text-tertiary);
            font-size: 0.85rem;
        }

        .news-meta svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .news-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .news-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .news-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        .news-link:hover {
            text-decoration: underline;
        }

        .news-link svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* Announcements Section */
        .announcements-section {
            padding: 4rem 0;
        }

        .announcements-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        .announcement-card {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            gap: 1rem;
        }

        .announcement-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow);
        }

        .announcement-icon {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            background-color: rgba(59, 130, 246, 0.1);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .announcement-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--accent-color);
            fill: none;
            stroke-width: 2;
        }

        .announcement-icon.priority-high {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .announcement-icon.priority-high svg {
            stroke: var(--danger-color);
        }

        .announcement-content {
            flex: 1;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .announcement-card h3 {
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .announcement-date {
            color: var(--text-tertiary);
            font-size: 0.85rem;
        }

        .announcement-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .priority-medium {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .priority-normal {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
        }

        /* Footer */
        .footer {
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1.125rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .footer-section p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background-color: var(--bg-secondary);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background-color: var(--primary-color);
        }

        .social-links svg {
            width: 20px;
            height: 20px;
            stroke: var(--text-secondary);
            fill: none;
            stroke-width: 2;
        }

        .social-links a:hover svg {
            stroke: white;
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .contact-item svg {
            width: 18px;
            height: 18px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            stroke: var(--text-tertiary);
            fill: none;
            stroke-width: 1.5;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .hero-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                text-align: center;
            }

            .feature-badges {
                flex-direction: column;
                gap: 1rem;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .about-images {
                order: -1;
            }

            .partners-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Introduction Screen -->
    <div class="intro-screen" id="introScreen">
        <img src="assets/images/redspartan.png" alt="School LMS" class="intro-logo">
        <h2 class="intro-text">School LMS</h2>
        <div class="intro-loader"></div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Navigation -->
        <nav>
            <div class="container">
                <div class="nav-brand">
                    School<span>SKILLS</span>
                </div>
                <ul class="nav-center">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Courses</a></li>
                    <li><a href="#">Pages</a></li>
                    <li><a href="#">Instructors</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                <div class="nav-right">
                    <a href="login.php" class="btn-primary">Get Started</a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <p class="hero-subtitle">Anywhere Across Easy Learning</p>
                        <h1>The Best <span class="highlight">Platform</span> For Enhancing Skills</h1>
                        <p>We have developed a comprehensive learning management platform designed to make education more accessible, interactive, and effective for both students and instructors.</p>
                        
                        <div class="hero-buttons">
                            <a href="register.php" class="btn-primary">Get Started</a>
                            <a href="#about" class="btn-secondary">Learn More</a>
                        </div>

                        <div class="feature-badges">
                            <div class="feature-badge">
                                <div class="feature-badge-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                                <div class="feature-badge-text">
                                    <h3>Learn Anywhere</h3>
                                    <p>Access from any device</p>
                                </div>
                            </div>
                            <div class="feature-badge">
                                <div class="feature-badge-icon">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <div class="feature-badge-text">
                                    <h3>Lifetime access</h3>
                                    <p>Learn at your own pace</p>
                                </div>
                            </div>
                            <div class="feature-badge">
                                <div class="feature-badge-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <div class="feature-badge-text">
                                    <h3>Expert Instructor</h3>
                                    <p>Learn from the best</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="assets/images/redspartan.png" alt="Learning Platform">
                    </div>
                </div>
            </div>
        </section>

        <!-- Partner Logos -->
        <section class="partners">
            <div class="container">
                <div class="partners-grid">
                    <div class="partner-logo">🎓 DIGITAL MARKETING</div>
                    <div class="partner-logo">📚 STUDENT PROGRAMS</div>
                    <div class="partner-logo">🎯 eFEDUCATION</div>
                    <div class="partner-logo">💼 DIGITAL LEARNING</div>
                    <div class="partner-logo">🌐 ONLINE LEARNING</div>
                    <div class="partner-logo">📖 EDUCATION HUB</div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="about-section">
            <div class="container">
                <div class="about-content">
                    <div class="about-text">
                        <h4>About Us</h4>
                        <h2>The Qualified And Highly Equipped Instructors</h2>
                        <p>Our platform brings together highly qualified educators with extensive experience in their fields. Each instructor is dedicated to delivering high-quality content and supporting students throughout their learning journey.</p>
                        <div class="about-buttons">
                            <a href="about.php" class="btn-primary">Read More</a>
                            <a href="#instructors" class="btn-secondary">Meet Instructors</a>
                        </div>
                    </div>
                    <div class="about-images">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&h=300&fit=crop" alt="Instructor 1" class="about-image">
                        <img src="https://images.unsplash.com/photo-1556157382-97eda2d62296?w=300&h=300&fit=crop" alt="Instructor 2" class="about-image">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop" alt="Instructor 3" class="about-image">
                        <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=300&h=300&fit=crop" alt="Instructor 4" class="about-image">
                    </div>
                </div>
            </div>
        </section>

        <!-- News Section -->
        <section class="news-section">

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path>
                        </svg>
                    </div>
                    <h3>Assignment Management</h3>
                    <p>Create, assign, and grade assignments with ease. Track student progress in real-time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>Student Collaboration</h3>
                    <p>Foster collaboration with discussion forums, group projects, and peer feedback tools.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3>Course Scheduling</h3>
                    <p>Efficient course scheduling with calendar integration. Never miss a class or deadline.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 3v18h18"></path>
                            <path d="M18 17V9M13 17V5M8 17v-3"></path>
                        </svg>
                    </div>
                    <h3>Performance Analytics</h3>
                    <p>Comprehensive analytics and reporting tools to track and improve student performance.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12" y2="18"></line>
                        </svg>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access your courses anywhere, anytime. Fully responsive design works on all devices.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <div class="section-header">
                <h2>Latest News</h2>
                <p>Stay updated with the latest happenings in our learning community</p>
            </div>
            
            <?php if (empty($news_items)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
                    </svg>
                    <p>No news articles available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($news_items as $news): ?>
                        <div class="news-card">
                            <div class="news-image">
                                <?php if (!empty($news['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="news-content">
                                <div class="news-meta">
                                    <svg viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span><?php echo date('M d, Y', strtotime($news['published_at'])); ?></span>
                                    <?php if (!empty($news['author'])): ?>
                                        <span>•</span>
                                        <span><?php echo htmlspecialchars($news['author']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                                <p><?php echo htmlspecialchars($news['summary']); ?></p>
                                <a href="news.php?id=<?php echo $news['id']; ?>" class="news-link">
                                    Read More
                                    <svg viewBox="0 0 24 24">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="announcements-section">
        <div class="container">
            <div class="section-header">
                <h2>Announcements</h2>
                <p>Important updates and notices from the administration</p>
            </div>
            
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <p>No announcements at this time.</p>
                </div>
            <?php else: ?>
                <div class="announcements-container">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-card">
                            <div class="announcement-icon <?php echo $announcement['priority'] === 'high' ? 'priority-high' : ''; ?>">
                                <svg viewBox="0 0 24 24">
                                    <?php if ($announcement['priority'] === 'high'): ?>
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12" y2="16"></line>
                                    <?php else: ?>
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <div class="announcement-content">
                                <div class="announcement-header">
                                    <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <?php if ($announcement['priority'] !== 'normal'): ?>
                                        <span class="priority-badge priority-<?php echo htmlspecialchars($announcement['priority']); ?>">
                                            <?php echo strtoupper($announcement['priority']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="announcement-date">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($announcement['posted_at'])); ?>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>School LMS</h3>
                    <p>Empowering education through innovative technology. A comprehensive learning management system designed for students, teachers, and administrators.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">
                            <svg viewBox="0 0 24 24">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <svg viewBox="0 0 24 24">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path>
                            </svg>
                        </a>
                        <a href="#" aria-label="LinkedIn">
                            <svg viewBox="0 0 24 24">
                                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"></path>
                                <circle cx="4" cy="4" r="2"></circle>
                            </svg>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <svg viewBox="0 0 24 24">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.5" y2="6.5"></line>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="login.php">Sign In</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>123 Education St, Learning City</span>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <span>info@schoollms.com</span>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>+1 (555) 123-4567</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> School LMS. All rights reserved. Built with ❤️ for education.</p>
            </div>
        </div>
    </footer>

    </div>

    <script>
        // Typing animation for intro text
        function typeText(element, text, speed = 100) {
            element.textContent = '';
            let index = 0;
            
            function type() {
                if (index < text.length) {
                    element.textContent += text.charAt(index);
                    index++;
                    setTimeout(type, speed);
                }
            }
            
            type();
        }

        // Introduction screen logic
        window.addEventListener('load', () => {
            const introScreen = document.getElementById('introScreen');
            const introText = document.querySelector('.intro-text');
            const mainContent = document.getElementById('mainContent');
            
            // Start typing after 0.5 seconds
            setTimeout(() => {
                typeText(introText, 'School LMS', 150);
            }, 500);
            
            setTimeout(() => {
                introScreen.classList.add('hidden');
                setTimeout(() => {
                    mainContent.classList.add('visible');
                }, 300);
            }, 2500); // Show intro for 2.5 seconds
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
