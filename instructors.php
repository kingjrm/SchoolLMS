<?php
session_start();
require_once 'includes/config.php';

// Fetch all teachers/instructors from database
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY created_at DESC");
    $instructors = $stmt->fetchAll();
} catch(PDOException $e) {
    $instructors = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructors - School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Hero Section */
        .hero {
            background: var(--bg-primary);
            padding: 4rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
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
            margin-bottom: 0.25rem;
            letter-spacing: 0.5px;
        }

        .hero-text h1 {
            font-size: 3rem;
            line-height: 1.2;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .hero-text h1 .highlight {
            color: var(--primary-color);
        }

        .hero-text p {
            font-size: 1.05rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .hero-image {
            position: relative;
            animation: fadeInRight 0.8s ease-out 0.3s backwards;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-image svg {
            width: 100%;
            max-width: 300px;
            height: auto;
            opacity: 0.8;
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

        /* Instructors Section */
        .instructors-section {
            padding: 6rem 0;
            background: var(--bg-primary);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h4 {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            line-height: 1.3;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .section-header p {
            font-size: 1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .instructors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .instructor-card {
            background: white;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .instructor-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 107, 53, 0.2);
        }

        .instructor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            border: 4px solid white;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .instructor-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .instructor-avatar span {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            position: relative;
            z-index: 1;
        }

        .instructor-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .instructor-role {
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .instructor-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .instructor-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .empty-state svg {
            width: 120px;
            height: 120px;
            stroke: var(--text-tertiary);
            fill: none;
            stroke-width: 1.5;
            margin-bottom: 2rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        /* CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .cta-content p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-secondary:hover {
            background-color: white;
            color: var(--primary-color);
            border-color: white;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 2.5rem;
            }

            .instructors-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .hero {
                padding: 3rem 0 2rem;
            }

            .hero-text h1 {
                font-size: 2rem;
            }

            .instructors-section {
                padding: 4rem 0;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .instructor-card {
                padding: 2rem 1.5rem;
            }

            .instructor-avatar {
                width: 100px;
                height: 100px;
            }

            .instructor-avatar span {
                font-size: 2rem;
            }

            .instructor-stats {
                gap: 1rem;
            }

            .cta-section {
                padding: 4rem 0;
            }

            .cta-content h2 {
                font-size: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="index.php" class="nav-brand">
                School<span>SKILLS</span>
            </a>
            <ul class="nav-center">
                <li><a href="index.php">Home</a></li>
                <li><a href="courses.php">Courses</a></li>
                <li><a href="instructors.php" class="active">Instructors</a></li>
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
                    <div class="hero-subtitle">MEET OUR EXPERTS</div>
                    <h1>Learn from <span class="highlight">Industry</span> Leaders</h1>
                    <p>Our experienced instructors bring real-world expertise and passion for teaching. Get personalized guidance from professionals who are leaders in their fields.</p>
                </div>
                <div class="hero-image">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Instructors Section -->
    <section class="instructors-section">
        <div class="container">
            <div class="section-header">
                <h4>OUR INSTRUCTORS</h4>
                <h2>Expert Educators & Mentors</h2>
                <p>Meet our team of dedicated professionals who are committed to helping you succeed in your learning journey.</p>
            </div>

            <?php if (empty($instructors)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <h3>No Instructors Yet</h3>
                    <p>Our team of expert instructors will be added soon!</p>
                </div>
            <?php else: ?>
                <div class="instructors-grid">
                    <?php foreach ($instructors as $instructor): ?>
                        <div class="instructor-card">
                            <div class="instructor-avatar">
                                <span><?php echo strtoupper(substr($instructor['first_name'], 0, 1) . substr($instructor['last_name'], 0, 1)); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?></h3>
                            <div class="instructor-role">Instructor</div>
                            <p><?php echo htmlspecialchars($instructor['email']); ?></p>
                            <div class="instructor-stats">
                                <div class="stat-item">
                                    <span class="stat-number">50+</span>
                                    <span class="stat-label">Students</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">5</span>
                                    <span class="stat-label">Courses</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">4.9</span>
                                    <span class="stat-label">Rating</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Learning?</h2>
                <p>Join thousands of students who are already learning with us. Create your account today and begin your journey.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn-secondary">Sign Up Free</a>
                    <a href="login.php" class="btn-primary">Sign In</a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
