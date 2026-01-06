<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('admin');
    $user = Auth::getCurrentUser();
    $db = new Database();
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link">Users</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="terms.php" class="nav-link">Academic Terms</a></li>
                <li class="nav-item"><a href="enrollments.php" class="nav-link">Enrollments</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link active">Reports</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>System Reports</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleDropdown()">Menu</button>
                        <div class="dropdown-menu" id="dropdown">
                            <a href="../logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">User Statistics</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role")->execute();
                        $roles = $db->fetchAll();
                        ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo ucfirst($role['role']); ?></td>
                                    <td><?php echo $role['count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Course Statistics</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db->prepare("
                            SELECT 
                                COUNT(*) as total_courses,
                                (SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled') as total_enrollments,
                                (SELECT AVG(max_students) FROM courses) as avg_capacity
                            FROM courses WHERE status = 'active'
                        ")->execute();
                        $stats = $db->fetch();
                        ?>
                        <p><strong>Active Courses:</strong> <?php echo $stats['total_courses']; ?></p>
                        <p><strong>Total Enrollments:</strong> <?php echo $stats['total_enrollments']; ?></p>
                        <p><strong>Avg Capacity:</strong> <?php echo round($stats['avg_capacity'] ?? 0); ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Course Enrollment Report</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT 
                            c.code, c.title, 
                            u.first_name, u.last_name,
                            COUNT(e.id) as enrolled_count
                        FROM courses c
                        LEFT JOIN users u ON c.teacher_id = u.id
                        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
                        WHERE c.status = 'active'
                        GROUP BY c.id
                        ORDER BY enrolled_count DESC
                    ")->execute();
                    $report = $db->fetchAll();
                    ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Teacher</th>
                                <th>Enrolled Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['code']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo $row['enrolled_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($report)): ?>
                        <p style="text-align: center; color: #9ca3af;">No data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assignment Submission Report</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT 
                            a.title, c.title as course_title,
                            COUNT(s.id) as total_submissions,
                            SUM(CASE WHEN g.score IS NOT NULL THEN 1 ELSE 0 END) as graded_count
                        FROM assignments a
                        LEFT JOIN courses c ON a.course_id = c.id
                        LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
                        LEFT JOIN grades g ON a.id = g.assignment_id
                        WHERE c.status = 'active'
                        GROUP BY a.id
                        ORDER BY a.created_at DESC
                        LIMIT 10
                    ")->execute();
                    $submissions = $db->fetchAll();
                    ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Course</th>
                                <th>Submissions</th>
                                <th>Graded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['title']); ?></td>
                                <td><?php echo htmlspecialchars($sub['course_title']); ?></td>
                                <td><?php echo $sub['total_submissions']; ?></td>
                                <td><?php echo $sub['graded_count'] ?? 0; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($submissions)): ?>
                        <p style="text-align: center; color: #9ca3af;">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById('dropdown').classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdown');
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
