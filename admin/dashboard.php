<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/Auth.php';
require_once '../includes/helpers.php';

Auth::requireRole('admin');
$user = Auth::getCurrentUser();

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stmt->execute();
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $total_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $stmt->execute();
    $total_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'");
    $stmt->execute();
    $total_enrollments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $total_students = $total_teachers = $total_courses = $total_enrollments = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School LMS</title>
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
            --bg-sidebar: #ffffff;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --primary-color: #ff6b35;
            --primary-dark: #e55a28;
        }

        html.dark-mode {
            --bg-primary: #1f2937;
            --bg-secondary: #111827;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --border-color: #374151;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--bg-sidebar);
            color: var(--text-primary);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }

        .sidebar-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h1 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .sidebar-header p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .sidebar-menu {
            list-style: none;
            flex: 1;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sidebar-menu a svg {
            width: 20px;
            height: 20px;
            stroke: var(--text-secondary);
            fill: none;
            stroke-width: 2;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .sidebar-menu a:hover svg {
            stroke: var(--primary-color);
        }

        .sidebar-menu a.active {
            background: rgba(255, 107, 53, 0.1);
            color: var(--primary-color);
        }

        .sidebar-menu a.active svg {
            stroke: var(--primary-color);
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
            margin-top: auto;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h2 {
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .content {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .action-section {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .action-section h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .action-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .action-link {
            padding: 0.75rem 1rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-link:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h1>School LMS</h1>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </a></li>
            <li><a href="#users">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Users
            </a></li>
            <li><a href="#courses">
                <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                Courses
            </a></li>
            <li><a href="#terms">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Terms
            </a></li>
            <li><a href="#reports">
                <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Reports
            </a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-info" style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1rem;">
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            </div>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>

    <div class="main-content">
        <div class="topbar">
            <h2>Admin Dashboard</h2>
            <div class="user-info">
                <span><?php echo htmlspecialchars($user['first_name']); ?></span>
            </div>
        </div>

        <div class="content">
            <div class="dashboard-header">
                <h1>Welcome back!</h1>
                <p>Here's an overview of your system performance.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_teachers; ?></div>
                    <div class="stat-label">Total Teachers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_courses; ?></div>
                    <div class="stat-label">Active Courses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_enrollments; ?></div>
                    <div class="stat-label">Active Enrollments</div>
                </div>
            </div>

            <div class="action-section">
                <h2>Quick Actions</h2>
                <div class="action-links">
                    <a href="#" class="action-link">Manage Users</a>
                    <a href="#" class="action-link">Manage Courses</a>
                    <a href="#" class="action-link">View Reports</a>
                    <a href="../index.php" class="action-link">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
