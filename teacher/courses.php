<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - School LMS</title>
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
                <li class="nav-item"><a href="courses.php" class="nav-link active">Courses</a></li>
                <li class="nav-item"><a href="materials.php" class="nav-link">Materials</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link">Assignments</a></li>
                <li class="nav-item"><a href="quizzes.php" class="nav-link">Quizzes</a></li>
                <li class="nav-item"><a href="grades.php" class="nav-link">Grades</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link">Announcements</a></li>
            </nav>
        </aside>

        <main class="main-content">
            <div class="topbar">
                <h1>My Courses</h1>
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
                    <h3 class="card-title">Assigned Courses</h3>
                </div>
                <div class="card-body">
                    <?php
                    $db->prepare("
                        SELECT c.*, t.name as term_name,
                        (SELECT COUNT(DISTINCT student_id) FROM enrollments WHERE course_id = c.id AND status = 'enrolled') as enrolled_count,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignment_count
                        FROM courses c 
                        JOIN academic_terms t ON c.term_id = t.id 
                        WHERE c.teacher_id = ? AND c.status = 'active' 
                        ORDER BY c.created_at DESC
                    ")->bind('i', $teacher_id)->execute();
                    $courses = $db->fetchAll();

                    if (!empty($courses)):
                    ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Term</th>
                                    <th>Students</th>
                                    <th>Assignments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo htmlspecialchars($course['term_name']); ?></td>
                                    <td><?php echo $course['enrolled_count']; ?></td>
                                    <td><?php echo $course['assignment_count']; ?></td>
                                    <td>
                                        <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #9ca3af;">No courses assigned</p>
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
