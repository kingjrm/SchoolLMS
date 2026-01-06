<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - School LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        html.dark-mode {
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --bg-tertiary: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-tertiary: #9ca3af;
            --border-color: #374151;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
            line-height: 1.6;
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
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .nav-brand svg {
            width: 28px;
            height: 28px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .nav-center {
            display: flex;
            gap: 2rem;
            align-items: center;
            list-style: none;
        }

        .nav-center a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-center a:hover, .nav-center a.active {
            color: var(--primary-color);
        }

        .nav-right {
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

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        /* Page Header */
        .page-header {
            background-color: var(--bg-primary);
            padding: 5rem 2rem 4rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .page-header h1 {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .page-header p {
            font-size: 1.125rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            background-color: var(--bg-secondary);
        }

        /* About Section */
        .about-section {
            margin-bottom: 5rem;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            margin-top: 2rem;
        }

        .about-content h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .about-content p {
            color: var(--text-secondary);
            margin-bottom: 1.25rem;
            font-size: 1rem;
            line-height: 1.7;
        }

        .about-image {
            width: 100%;
            height: 400px;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .about-image svg {
            width: 120px;
            height: 120px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 1.5;
            opacity: 0.2;
        }

        /* Values Grid */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .value-card {
            padding: 2rem;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .value-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .value-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 107, 53, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .value-icon svg {
            width: 28px;
            height: 28px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 2;
        }

        .value-card h3 {
            font-size: 1.125rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .value-card p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .team-card {
            padding: 2rem;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            overflow: hidden;
            text-align: center;
            transition: all 0.3s ease;
        }

        .team-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .team-avatar {
            width: 100%;
            height: 250px;
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }

        .team-avatar svg {
            width: 80px;
            height: 80px;
            stroke: var(--primary-color);
            fill: none;
            stroke-width: 1.5;
            opacity: 0.4;
        }

        .team-info {
            padding: 1.5rem;
        }

        .team-info h3 {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }

        .team-info .role {
            color: var(--primary-color);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .team-info p {
            color: var(--text-secondary);
            font-size: 0.872rem;
        }

        .section-title h2 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .section-title p {
            color: var(--text-secondary);
            font-size: 1.00;
            margin-bottom: 0.5rem;
        }

        .section-title p {
            color: var(--text-secondary);
            font-size: 1.125rem;
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

        /* Responsive */
        @media (max-width: 768px) {
            .about-grid {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .nav-center {
                display: none;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="index.php" class="nav-brand">
            <svg viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            School LMS
        </a>
        <ul class="nav-center">
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#features">Features</a></li>
            <li><a href="about.php" class="active">About</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
        <div class="nav-right">
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
            <a href="login.php" class="btn-primary">Sign In</a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <h1>About School LMS</h1>
        <p>Empowering education through innovative technology and dedicated support</p>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- About Section -->
        <div class="about-section">
            <div class="about-grid">
                <div class="about-content">
                    <h2>Our Mission</h2>
                    <p>At School LMS, we're committed to revolutionizing education through technology. Our platform bridges the gap between traditional learning and modern digital solutions.</p>
                    <p>We believe that every student deserves access to quality education, and every teacher should have the tools to inspire and guide their students effectively.</p>
                    <p>With over 250,000 students assisted and 50 institutions trusting our platform, we're making a real difference in education worldwide.</p>
                </div>
                <div class="about-image">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        <path d="M8 7h8M8 11h5"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="section-title">
            <h2>Our Core Values</h2>
            <p>The principles that guide everything we do</p>
        </div>

        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                    </svg>
                </div>
                <h3>Innovation</h3>
                <p>Constantly evolving to meet the changing needs of modern education</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                    </svg>
                </div>
                <h3>Dedication</h3>
                <p>Committed to providing the best learning experience for every user</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"></path>
                    </svg>
                </div>
                <h3>Community</h3>
                <p>Building a supportive network of learners and educators worldwide</p>
            </div>

            <div class="value-card">
                <div class="value-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"></path>
                    </svg>
                </div>
                <h3>Security</h3>
                <p>Protecting your data and privacy with enterprise-grade security measures</p>
            </div>
        </div>

        <!-- Team Section -->
        <div class="section-title" style="margin-top: 5rem;">
            <h2>Meet Our Team</h2>
            <p>Passionate professionals dedicated to your success</p>
        </div>

        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                    </svg>
                </div>
                <div class="team-info">
                    <h3>Dr. Sarah Johnson</h3>
                    <div class="role">Chief Education Officer</div>
                    <p>15+ years experience in educational technology and curriculum development</p>
                </div>
            </div>

            <div class="team-card">
                <div class="team-avatar">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                    </svg>
                </div>
                <div class="team-info">
                    <h3>Michael Chen</h3>
                    <div class="role">Lead Developer</div>
                    <p>Expert in building scalable learning management systems</p>
                </div>
            </div>

            <div class="team-card">
                <div class="team-avatar">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                    </svg>
                </div>
                <div class="team-info">
                    <h3>Emily Rodriguez</h3>
                    <div class="role">Customer Success Manager</div>
                    <p>Dedicated to ensuring exceptional user experience and support</p>
                </div>
            </div>

            <div class="team-card">
                <div class="team-avatar">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                    </svg>
                </div>
                <div class="team-info">
                    <h3>David Kim</h3>
                    <div class="role">Product Designer</div>
                    <p>Creating intuitive and beautiful learning experiences</p>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        const html = document.documentElement;

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
