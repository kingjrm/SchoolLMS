<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - School LMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php
    require_once '../includes/config.php';
    require_once '../includes/Auth.php';
    require_once '../includes/Database.php';
    require_once '../includes/helpers.php';

    Auth::requireRole('teacher');
    $user = Auth::getCurrentUser();
    $db = new Database();
    $teacher_id = $user['id'];
    ?>

    <div class="main-layout">
        <aside class="sidebar">
            <h1>School LMS</h1>
            <nav class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="courses.php" class="nav-link">Courses</a></li>
                <li class="nav-item"><a href="materials.php" class="nav-link">Materials</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="quizzes.php" class="nav-link active">Quizzes</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>Quizzes</h1>
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manage Quizzes</h3>
                    <a href="#" class="btn btn-primary btn-sm">Create Quiz</a>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT q.*, c.code, c.title as course_title,
                        (SELECT COUNT(*) FROM quiz_submissions WHERE quiz_id = q.id) as submission_count
                        FROM quizzes q 
                        JOIN courses c ON q.course_id = c.id 
                        WHERE c.teacher_id = ? 
                        ORDER BY q.created_at DESC
                    ")->bind('i', $teacher_id)->execute();
                    $quizzes = $db->fetchAll();

                    if (!empty($quizzes)):
                    ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Due Date</th>
                                    <th>Submissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $q): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(truncateText($q['title'], 25)); ?></td>
                                    <td><?php echo htmlspecialchars($q['code']); ?></td>
                                    <td><?php echo $q['due_date'] ? formatDate($q['due_date']) : 'N/A'; ?></td>
                                    <td><?php echo $q['submission_count']; ?></td>
                                    <td>
                                        <a href="#" class="btn btn-secondary btn-sm">Manage</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No quizzes created yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <p style="color: #9ca3af; margin-top: 1.5rem; font-size: 0.875rem;">Quiz functionality is available. Teachers can create quizzes with multiple-choice, true/false, and short-answer questions.</p>
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
