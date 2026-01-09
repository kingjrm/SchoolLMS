<?php
session_start();
require_once 'includes/config.php';

// Fetch all courses from database
try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC");
    $courses = $stmt->fetchAll();
} catch(PDOException $e) {
    $courses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - School LMS</title>
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

        /* Courses Section */
        .courses-section {
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

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .course-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(255, 107, 53, 0.2);
        }

        .course-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .course-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .course-image svg {
            width: 80px;
            height: 80px;
            stroke: white;
            fill: none;
            stroke-width: 1.5;
            position: relative;
            z-index: 1;
        }

        .course-content {
            padding: 2rem;
        }

        .course-category {
            display: inline-block;
            background: var(--bg-secondary);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .course-content h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 600;
            line-height: 1.4;
        }

        .course-content p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .course-meta-item svg {
            width: 16px;
            height: 16px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .course-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.125rem;
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

            .courses-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

            .courses-section {
                padding: 4rem 0;
            }

            .section-header h2 {
                font-size: 2rem;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .course-content {
                padding: 1.5rem;
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
                <li><a href="courses.php" class="active">Courses</a></li>
                <li><a href="instructors.php">Instructors</a></li>
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
                    <div class="hero-subtitle">EXPLORE OUR COURSES</div>
                    <h1>Learn from <span class="highlight">Expert</span> Instructors</h1>
                    <p>Discover a wide range of courses designed to help you master new skills and advance your career. From programming to design, we have something for everyone.</p>
                </div>
                <div class="hero-image">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        <circle cx="10" cy="8" r="1"></circle>
                        <circle cx="14" cy="8" r="1"></circle>
                        <path d="M8 12h8"></path>
                        <path d="M8 16h8"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses-section">
        <div class="container">
            <div class="section-header">
                <h4>OUR COURSES</h4>
                <h2>Choose Your Learning Path</h2>
                <p>Browse through our comprehensive collection of courses and find the perfect match for your learning goals.</p>
            </div>

            <?php if (empty($courses)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <h3>No Courses Available Yet</h3>
                    <p>We're working on adding exciting courses. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-image">
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                            </div>
                            <div class="course-content">
                                <span class="course-category"><?php echo htmlspecialchars($course['code'] ?? 'COURSE'); ?></span>
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?></p>
                                <div class="course-meta">
                                    <div class="course-meta-item">
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <?php echo $course['credits'] ?? '3'; ?> Credits
                                    </div>
                                    <div class="course-price">Free</div>
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
