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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b35;
            --primary-dark: #e55a28;
            --bg-primary: #ffffff;
            --bg-secondary: #fef8f5;
            --bg-light: #f9fafb;
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --border-color: #e5e5e5;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
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

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header Section */
        .page-header {
            background: var(--bg-primary);
            padding: 3rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        /* Courses Section */
        .courses-section {
            padding: 5rem 0;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .course-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 0, 0, 0.1);
        }

        .course-image {
            width: 100%;
            height: 180px;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .course-image svg {
            width: 60px;
            height: 60px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .course-content {
            padding: 1.5rem;
        }

        .course-content h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .course-content p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .course-meta span {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .course-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.125rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state svg {
            width: 120px;
            height: 120px;
            stroke: var(--text-secondary);
            fill: none;
            stroke-width: 1.5;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .nav-center {
                display: none;
            }

            .courses-grid {
                grid-template-columns: 1fr;
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>All Courses</h1>
            <p>Browse our collection of courses</p>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses-section">
        <div class="container">
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
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                <div class="course-meta">
                                    <span>📚 <?php echo $course['duration'] ?? '8 weeks'; ?></span>
                                    <span class="course-price">Free</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
