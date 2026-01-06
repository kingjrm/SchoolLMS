<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - School LMS</title>
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
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --border-color: #e5e5e5;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.08);
            --primary-color: #ff6b35;
            --primary-dark: #e55a28;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header Section */
        .header-section {
            background: var(--bg-primary);
            padding: 4rem 0;
            text-align: center;
        }

        .header-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .header-section h1 .highlight {
            color: var(--primary-color);
        }

        .header-section p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: #ffffff;
        }

        .features-grid {
            display: flex;
            flex-direction: row;
            gap: 2rem;
            padding: 0;
            flex-wrap: nowrap;
            justify-content: flex-start;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .features-grid::-webkit-scrollbar {
            display: none;
        }

        .feature-card {
            background: white;
            padding: 2.5rem 2rem;
            border-radius: 1.25rem;
            width: 260px;
            min-width: 260px;
            max-width: 260px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            flex-grow: 0;
        }

        .feature-card:nth-child(1),
        .feature-card:nth-child(2),
        .feature-card:nth-child(3),
        .feature-card:nth-child(4),
        .feature-card:nth-child(5) {
            margin: 0;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:nth-child(1) .feature-icon {
            background: linear-gradient(135deg, #ff6b35 0%, #f093fb 100%);
        }

        .feature-card:nth-child(2) .feature-icon {
            background: linear-gradient(135deg, #f093fb 0%, #a855f7 100%);
        }

        .feature-card:nth-child(3) .feature-icon {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        }

        .feature-card:nth-child(4) .feature-icon {
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
        }

        .feature-card:nth-child(5) .feature-icon {
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .feature-icon svg {
            width: 45px;
            height: 45px;
            stroke: white;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-card h3 {
            font-size: 1.15rem;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
            margin: 0;
        }

        @media (max-width: 768px) {
            .feature-card {
                min-width: 220px;
                max-width: 220px;
            }
        }

        /* Animation */
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

        .feature-card {
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }

        /* Footer */
        .footer {
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            padding: 3rem 0 1.5rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .footer-section p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .header-section h1 {
                font-size: 2rem;
            }

            .features-section {
                padding: 3rem 0;
            }

            .feature-icon {
                height: 160px;
            }

            .feature-icon svg {
                width: 60px;
                height: 60px;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container" style="padding: 0 2rem; max-width: 1200px; margin: 0 auto;">
            <div class="nav-brand">
                School<span>SKILLS</span>
            </div>
            <ul class="nav-center">
                <li><a href="index.php">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="nav-right">
                <a href="login.php" class="btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="header-section">
        <div class="container">
            <h1>Powerful <span class="highlight">Features</span> For Learning</h1>
            <p>Everything you need to create an engaging and effective learning experience. Designed for educators and students alike.</p>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="12" y1="13" x2="8" y2="13"></line>
                            <line x1="12" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <h3>Assignment Management</h3>
                    <p>Create, assign, and grade assignments with ease. Track student progress in real-time.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>Student Collaboration</h3>
                    <p>Foster collaboration with discussion forums, group projects, and peer feedback tools.</p>
                </div>

                <!-- Feature 3 -->
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

                <!-- Feature 4 -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <h3>Performance Analytics</h3>
                    <p>Comprehensive analytics and reporting tools to track student performance.</p>
                </div>

                <!-- Feature 5 -->
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

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>School LMS</h3>
                    <p>A comprehensive learning management system designed for modern education.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <p>
                        <a href="index.php" style="color: var(--text-secondary); text-decoration: none;">Home</a><br>
                        <a href="#features" style="color: var(--text-secondary); text-decoration: none;">Features</a><br>
                        <a href="contact.php" style="color: var(--text-secondary); text-decoration: none;">Contact</a>
                    </p>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>info@schoollms.com<br>+1 (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 School LMS. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
