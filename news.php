<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Auth.php';

// Get news ID from URL
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$news_id) {
    header('Location: index.php');
    exit;
}

// Fetch news article
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.first_name, u.last_name
        FROM news n
        LEFT JOIN users u ON n.posted_by = u.id
        WHERE n.id = ? AND n.status = 'published'
    ");
    $stmt->execute([$news_id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        header('Location: index.php');
        exit;
    }

    // Increment view count
    $update_stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
    $update_stmt->execute([$news_id]);

} catch (Exception $e) {
    header('Location: index.php');
    exit;
}

// Fetch related news (other published articles)
try {
    $related_stmt = $pdo->prepare("
        SELECT id, title, summary, image_url, published_at
        FROM news
        WHERE status = 'published' AND id != ?
        ORDER BY published_at DESC
        LIMIT 3
    ");
    $related_stmt->execute([$news_id]);
    $related_news = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $related_news = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-tertiary: #9ca3af;
            --border-color: #e5e7eb;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
            --primary-color: #ff6b35;
            --primary-dark: #e55a28;
            --accent-color: #3b82f6;
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

        .nav-center a:hover {
            color: var(--primary-color);
        }

        .nav-center a.active {
            color: var(--primary-color);
        }

        .nav-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-secondary);
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: var(--bg-tertiary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* News Article */
        .news-article {
            padding: 4rem 0;
        }

        .news-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .news-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .news-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .news-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .news-meta-item svg {
            width: 16px;
            height: 16px;
            stroke: var(--text-tertiary);
        }

        .news-image {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 2rem;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .news-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .news-content {
            max-width: 800px;
            margin: 0 auto;
            background: var(--bg-primary);
            padding: 3rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
        }

        .news-content p {
            margin-bottom: 1.5rem;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        .news-content h2,
        .news-content h3,
        .news-content h4 {
            margin: 2rem 0 1rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .news-content h2 {
            font-size: 1.5rem;
        }

        .news-content h3 {
            font-size: 1.25rem;
        }

        .news-content h4 {
            font-size: 1.1rem;
        }

        /* Related News */
        .related-news {
            padding: 4rem 0;
            background: var(--bg-primary);
        }

        .related-news h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .related-news p {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            font-size: 1.05rem;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .related-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .related-image {
            width: 100%;
            height: 180px;
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-image svg {
            width: 48px;
            height: 48px;
            stroke: var(--text-tertiary);
        }

        .related-content {
            padding: 1.5rem;
        }

        .related-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
        }

        .related-meta svg {
            width: 14px;
            height: 14px;
            stroke: var(--text-tertiary);
        }

        .related-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            line-height: 1.4;
        }

        .related-content p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .related-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .related-link:hover {
            color: var(--primary-dark);
        }

        .related-link svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }

        .back-btn:hover {
            color: var(--primary-color);
        }

        .back-btn svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
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

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .footer-bottom p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .news-header h1 {
                font-size: 2rem;
            }

            .news-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .news-content {
                padding: 2rem 1.5rem;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .news-image {
                margin-bottom: 1.5rem;
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
                <li><a href="instructors.php">Instructors</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="nav-right">
                <?php if (Auth::isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn-primary">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- News Article -->
    <section class="news-article">
        <div class="container">
            <a href="index.php" class="back-btn">
                <svg viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Home
            </a>

            <div class="news-header">
                <h1><?php echo htmlspecialchars($news['title']); ?></h1>
                <div class="news-meta">
                    <div class="news-meta-item">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span><?php echo date('F d, Y', strtotime($news['published_at'])); ?></span>
                    </div>
                    <?php if (!empty($news['author'])): ?>
                        <div class="news-meta-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span><?php echo htmlspecialchars($news['author']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="news-meta-item">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span><?php echo $news['views']; ?> views</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($news['image_url'])): ?>
                <div class="news-image">
                    <img src="<?php echo htmlspecialchars($news['image_url']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                </div>
            <?php endif; ?>

            <div class="news-content">
                <?php echo nl2br(htmlspecialchars($news['content'])); ?>
            </div>
        </div>
    </section>

    <!-- Related News -->
    <?php if (!empty($related_news)): ?>
    <section class="related-news">
        <div class="container">
            <h2>Related News</h2>
            <p>Check out more articles from our news feed</p>

            <div class="related-grid">
                <?php foreach ($related_news as $related): ?>
                    <div class="related-card">
                        <div class="related-image">
                            <?php if (!empty($related['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($related['image_url']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24">
                                    <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="related-content">
                            <div class="related-meta">
                                <svg viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span><?php echo date('M d, Y', strtotime($related['published_at'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($related['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($related['summary'], 0, 100)) . (strlen($related['summary']) > 100 ? '...' : ''); ?></p>
                            <a href="news.php?id=<?php echo $related['id']; ?>" class="related-link">
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
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>School LMS</h3>
                    <p>Empowering education through innovative learning management solutions. Join thousands of students and educators in their journey to success.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p><a href="index.php" style="color: var(--text-secondary); text-decoration: none;">Home</a></p>
                    <p><a href="courses.php" style="color: var(--text-secondary); text-decoration: none;">Courses</a></p>
                    <p><a href="instructors.php" style="color: var(--text-secondary); text-decoration: none;">Instructors</a></p>
                    <p><a href="contact.php" style="color: var(--text-secondary); text-decoration: none;">Contact</a></p>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📧 info@schoollms.com</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>📍 123 Education St, Learning City</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> School LMS. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>