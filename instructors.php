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

        /* Instructors Section */
        .instructors-section {
            padding: 5rem 0;
        }

        .instructors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .instructor-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .instructor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 0, 0, 0.1);
        }

        .instructor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--bg-light);
            border: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
        }

        .instructor-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .instructor-card .role {
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .instructor-card p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
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

            .instructors-grid {
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
                <li><a href="courses.php">Courses</a></li>
                <li><a href="pages.php">Pages</a></li>
                <li><a href="instructors.php" class="active">Instructors</a></li>
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
            <h1>Our Instructors</h1>
            <p>Experienced educators dedicated to your success</p>
        </div>
    </section>

    <!-- Instructors Section -->
    <section class="instructors-section">
        <div class="container">
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
                                <?php echo strtoupper(substr($instructor['name'], 0, 1)); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($instructor['name']); ?></h3>
                            <div class="role">Instructor</div>
                            <p><?php echo htmlspecialchars($instructor['email']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
